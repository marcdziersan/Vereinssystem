<?php
/**
 * PDO/InnoDB cash repository. All mutations are atomic and recorded in vs_audit.
 * Account and member rows are locked before financial mutations; transfers lock
 * both accounts in ascending order to reduce the risk of a database deadlock.
 */
declare(strict_types=1);
namespace Vereinssystem\Cash;

use PDO;
use PDOException;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\{CashAccount,CashEntry};
use Vereinssystem\Finance\PdoFinanceRepository;

final class PdoCashRepository implements CashRepository
{
    public function __construct(private readonly Database $database) {}
    private function db():PDO{return $this->database->connection();}
    /** @param list<mixed> $args */
    private function one(string $sql,array $args=[]):?array
    {$s=$this->db()->prepare($sql);$s->execute($args);return $s->fetch(PDO::FETCH_ASSOC)?:null;}
    /** @param list<mixed> $args */
    private function all(string $sql,array $args=[]):array
    {$s=$this->db()->prepare($sql);$s->execute($args);return $s->fetchAll(PDO::FETCH_ASSOC);}
    /** @param list<mixed> $args */
    private function run(string $sql,array $args=[]):int
    {$s=$this->db()->prepare($sql);$s->execute($args);return $s->rowCount();}
    private function atomic(callable $action):mixed
    {
        $pdo=$this->db();$pdo->beginTransaction();
        try{$result=$action();$pdo->commit();return $result;}
        catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    private function audit(int $actor,string $action,string $object,int $id,array $extra=[]):void
    {
        $this->run('INSERT INTO vs_audit (akteur,aktion,objekt,objekt_id,details) VALUES(?,?,?,?,?)',
            [$actor,$action,$object,$id,json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
    private function account(int $id,bool $lock=false,bool $active=false):array
    {
        $row=$this->one('SELECT * FROM vs_konten WHERE id=?'.($lock?' FOR UPDATE':''),[$id]);
        if(!$row||($active&&(int)$row['aktiv']!==1))throw new \DomainException('Konto fehlt oder ist deaktiviert.');
        return $row;
    }
    public function accounts(bool $includeInactive=true):array
    {
        $rows=$this->all('SELECT k.id,k.name,k.typ,k.anfangsbestand_cent,k.anfangsdatum,k.aktiv,
            COALESCE((SELECT SUM(CASE WHEN b.art IN (\'einnahme\',\'umbuchung_ein\') THEN b.betrag_cent ELSE -b.betrag_cent END)
              FROM vs_kassenbuch b WHERE b.konto_id=k.id AND b.storniert_am IS NULL),0) AS delta
            FROM vs_konten k '.($includeInactive?'':'WHERE k.aktiv=1 ').'ORDER BY k.aktiv DESC,k.name');
        return array_map(static fn(array $a):CashAccount=>new CashAccount(
            (int)$a['id'],(string)$a['name'],(string)$a['typ'],(int)$a['anfangsbestand_cent'],
            (string)$a['anfangsdatum'],(bool)$a['aktiv'],(int)$a['anfangsbestand_cent']+(int)$a['delta']),$rows);
    }
    public function entries(int $year,?int $accountId=null):array
    {
        $start=$year.'-01-01';$end=$year.'-12-31';
        $sql='SELECT b.*,k.name AS kontoname FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id
           WHERE b.datum BETWEEN ? AND ?';$args=[$start,$end];
        if($accountId!==null){$sql.=' AND b.konto_id=?';$args[]=$accountId;}
        $sql.=' ORDER BY b.datum DESC,b.id DESC LIMIT 300';
        return array_map(static fn(array $r):CashEntry=>new CashEntry(
            (int)$r['id'],(int)$r['konto_id'],(string)$r['kontoname'],(string)$r['art'],(int)$r['betrag_cent'],
            (string)$r['datum'],(string)$r['beschreibung'],(string)$r['kategorie'],(string)$r['belegnummer'],
            $r['zahlung_id']===null?null:(int)$r['zahlung_id'],$r['umbuchung_key']===null?null:(string)$r['umbuchung_key'],
            $r['storniert_am']!==null),$this->all($sql,$args));
    }
    public function createAccount(string $name,string $type,int $opening,string $date,int $actor):int
    {
        return $this->atomic(function()use($name,$type,$opening,$date,$actor):int{
            try{$this->run('INSERT INTO vs_konten(name,typ,anfangsbestand_cent,anfangsdatum,erstellt_von) VALUES(?,?,?,?,?)',[$name,$type,$opening,$date,$actor]);}
            catch(PDOException $e){if($e->getCode()==='23000')throw new \DomainException('Ein Konto mit diesem Namen existiert bereits.');throw $e;}
            $id=(int)$this->db()->lastInsertId();$this->audit($actor,'konto.erstellt','konto',$id,['name'=>$name,'anfang_cent'=>$opening]);return $id;
        });
    }
    public function setAccount(int $id,string $name,string $type,bool $active,int $actor):void
    {
        $this->atomic(function()use($id,$name,$type,$active,$actor):void{
            $this->account($id,true);
            try{$this->run('UPDATE vs_konten SET name=?,typ=?,aktiv=? WHERE id=?',[$name,$type,(int)$active,$id]);}
            catch(PDOException $e){if($e->getCode()==='23000')throw new \DomainException('Kontoname bereits vergeben.');throw $e;}
            $this->audit($actor,'konto.geaendert','konto',$id,['aktiv'=>$active]);
        });
    }
    /**
     * Insert one positive entry inside an existing transaction. Never call for a
     * payment before it is created, because of the foreign key on zahlung_id.
     */
    private function insert(int $account,string $type,int $cents,string $date,string $category,string $description,string $receipt,int $actor,?int $payment=null,?string $transfer=null):int
    {
        $a=$this->account($account,false,true);
        if($date<(string)$a['anfangsdatum'])throw new \DomainException('Buchungsdatum liegt vor Kontoeröffnung.');
        $this->run('INSERT INTO vs_kassenbuch (konto_id,art,datum,betrag_cent,kategorie,beschreibung,belegnummer,zahlung_id,umbuchung_key,erstellt_von) VALUES(?,?,?,?,?,?,?,?,?,?)',
            [$account,$type,$date,$cents,$category,$description,$receipt,$payment,$transfer,$actor]);
        return (int)$this->db()->lastInsertId();
    }
    public function book(int $account,string $type,int $cents,string $date,string $category,string $description,string $receipt,int $actor):int
    {
        return $this->atomic(function()use($account,$type,$cents,$date,$category,$description,$receipt,$actor):int{
            $this->account($account,true,true);
            $id=$this->insert($account,$type,$cents,$date,$category,$description,$receipt,$actor);
            $this->audit($actor,'kasse.'.$type,'buchung',$id,['cent'=>$cents,'konto'=>$account]);return $id;
        });
    }
    public function transfer(int $from,int $to,int $cents,string $date,string $description,int $actor):array
    {
        return $this->atomic(function()use($from,$to,$cents,$date,$description,$actor):array{
            foreach([min($from,$to),max($from,$to)] as $id)$this->account($id,true,true);
            $key=bin2hex(random_bytes(16));
            $out=$this->insert($from,'umbuchung_aus',$cents,$date,'Umbuchung',$description,'',$actor,null,$key);
            $in=$this->insert($to,'umbuchung_ein',$cents,$date,'Umbuchung',$description,'',$actor,null,$key);
            $this->audit($actor,'kasse.umbuchung','buchung',$out,['gegenbuchung'=>$in,'cent'=>$cents]);
            return [$out,$in];
        });
    }
    public function voidEntry(int $id,string $reason,int $actor):void
    {
        $this->atomic(function()use($id,$reason,$actor):void{
            $entry=$this->one('SELECT * FROM vs_kassenbuch WHERE id=?',[$id]);
            if(!$entry||$entry['storniert_am']!==null)throw new \DomainException('Buchung nicht gefunden oder storniert.');
            if($entry['zahlung_id']!==null)throw new \DomainException('Beitragszahlung nur über das Beitragskonto stornieren.');
            // Serialize changes on the account, then re-read the selected entry.
            $this->account((int)$entry['konto_id'],true);
            $entry=$this->one('SELECT * FROM vs_kassenbuch WHERE id=? FOR UPDATE',[$id]);
            if(!$entry||$entry['storniert_am']!==null)throw new \DomainException('Buchung bereits storniert.');
            $rows=[$entry];
            if($entry['umbuchung_key']!==null){
                $rows=$this->all('SELECT * FROM vs_kassenbuch WHERE umbuchung_key=? ORDER BY id FOR UPDATE',[$entry['umbuchung_key']]);
                if(count($rows)!==2)throw new \DomainException('Umbuchung unvollständig – Prüfung erforderlich.');
                $types=array_column($rows,'art');sort($types);
                if($types!==['umbuchung_aus','umbuchung_ein'])throw new \DomainException('Umbuchungspaar ungültig.');
                foreach($rows as $row){if($row['storniert_am']!==null)throw new \DomainException('Umbuchung bereits teilweise storniert.');}
            }
            foreach($rows as $row){
                $this->run('UPDATE vs_kassenbuch SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=? AND storniert_am IS NULL',[$actor,$reason,$row['id']]);
                $this->audit($actor,'kasse.storno','buchung',(int)$row['id'],['grund'=>$reason]);
            }
        });
    }
    /**
     * Both records and claim allocations are committed together. A rollback on
     * failed cash insertion also removes the payment and all new allocations.
     */
    public function contribution(int $account,int $member,int $cents,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred):array
    {
        return $this->atomic(function()use($account,$member,$cents,$date,$channel,$reference,$note,$actor,$preferred):array{
            $finance=new PdoFinanceRepository($this->database);
            $payment=$finance->receivePaymentInsideCurrentTransaction($member,$cents,$date,$channel,$reference,$note,$actor,$preferred);
            $this->account($account,true,true);
            $entry=$this->insert($account,'einnahme',$cents,$date,'Mitgliedsbeitrag','Mitgliedsbeitrag Mitglied #'.$member,
                'BEITRAG-'.$payment['id'],$actor,$payment['id']);
            $this->audit($actor,'beitrag.kasse','buchung',$entry,['zahlung_id'=>$payment['id'],'cent'=>$cents]);
            return $payment+['cash_id'=>$entry];
        });
    }
    public function voidContribution(int $paymentId,string $reason,int $actor):void
    {
        $this->atomic(function()use($paymentId,$reason,$actor):void{
            $initial=$this->one('SELECT mitglied_id FROM vs_zahlungen WHERE id=?',[$paymentId]);
            if(!$initial)throw new \DomainException('Zahlung nicht gefunden.');
            $this->one('SELECT mitglieder_id FROM mitglieder WHERE mitglieder_id=? FOR UPDATE',[(int)$initial['mitglied_id']]);
            $payment=$this->one('SELECT id,mitglied_id,storniert_am FROM vs_zahlungen WHERE id=? FOR UPDATE',[$paymentId]);
            if(!$payment||$payment['storniert_am']!==null)throw new \DomainException('Zahlung bereits storniert.');
            $entry=$this->one('SELECT id,konto_id,storniert_am FROM vs_kassenbuch WHERE zahlung_id=?',[$paymentId]);
            if($entry!==null){
                $this->account((int)$entry['konto_id'],true);
                $entry=$this->one('SELECT id,storniert_am FROM vs_kassenbuch WHERE zahlung_id=? FOR UPDATE',[$paymentId]);
                if($entry['storniert_am']!==null)throw new \DomainException('Kassenbuchung ist bereits storniert; bitte prüfen.');
                $this->run('UPDATE vs_kassenbuch SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=?',[$actor,$reason,$entry['id']]);
                $this->audit($actor,'beitrag.kasse.storno','buchung',(int)$entry['id'],['zahlung_id'=>$paymentId]);
            }
            $this->run('UPDATE vs_zahlungen SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=?',[$actor,$reason,$paymentId]);
            $this->audit($actor,'beitrag.storno','zahlung',$paymentId,['grund'=>$reason]);
        });
    }
}
