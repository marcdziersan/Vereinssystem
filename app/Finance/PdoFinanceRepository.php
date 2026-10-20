<?php
/**
 * Transactional InnoDB contribution ledger.
 * All identifiers are bound values. Member row locks serialize ledger writes per member.
 * Storno preserves original records; immutable tariff dates protect historical amounts.
 */
declare(strict_types=1);
namespace Vereinssystem\Finance;
use DateTimeImmutable;
use PDO;
use PDOException;
use Vereinssystem\Core\Database;
final class PdoFinanceRepository implements FinanceRepository
{
    public function __construct(private readonly Database $database) {}
    private function db(): PDO {return $this->database->connection();}
    /** @param list<mixed> $params */
    private function one(string $sql,array $params=[]): ?array
    { $s=$this->db()->prepare($sql);$s->execute($params);return $s->fetch(PDO::FETCH_ASSOC)?:null;}
    /** @param list<mixed> $params */
    private function all(string $sql,array $params=[]): array
    { $s=$this->db()->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);}
    /** @param list<mixed> $params */
    private function execute(string $sql,array $params=[]): int
    { $s=$this->db()->prepare($sql);$s->execute($params);return $s->rowCount();}
    /** Every mutation participates in a single transaction, including allocation. */
    private function transaction(callable $callback): mixed
    {
        $pdo=$this->db();$pdo->beginTransaction();
        try{$result=$callback();$pdo->commit();return $result;}
        catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    private function member(int $id,bool $lock=false): array
    {
        $row=$this->one('SELECT mitglieder_id,vorname,nachname,status,mitgliedschaft,geburtstag,beitritt FROM mitglieder WHERE mitglieder_id=?'.($lock?' FOR UPDATE':''),[$id]);
        if(!$row)throw new \DomainException('Mitglied nicht gefunden.');
        return $row;
    }
    public function statement(int $memberId,int $year): array
    {
        $person=$this->member($memberId);$start=$year.'-01';$end=$year.'-12';
        $claims=$this->all('SELECT f.id,f.mitglied_id,f.periode,f.art,f.abteilung_id,f.beschreibung,f.betrag_cent,f.faellig_am,f.storniert_am,
          COALESCE((SELECT SUM(a.betrag_cent) FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE a.forderung_id=f.id AND z.storniert_am IS NULL),0) AS bezahlt_cent
          FROM vs_forderungen f WHERE f.mitglied_id=? AND f.periode BETWEEN ? AND ? ORDER BY f.faellig_am,f.id',[$memberId,$start,$end]);
        $payments=$this->all('SELECT z.id,z.mitglied_id,z.betrag_cent,z.gezahlt_am,z.zahlungsweg,z.referenz,z.bemerkung,z.storniert_am,
          COALESCE((SELECT SUM(a.betrag_cent) FROM vs_zuordnungen a WHERE a.zahlung_id=z.id),0) AS zugeordnet_cent
          FROM vs_zahlungen z WHERE z.mitglied_id=? AND z.gezahlt_am BETWEEN ? AND ? ORDER BY z.gezahlt_am DESC,z.id DESC',[$memberId,$year.'-01-01',$year.'-12-31']);
        $soll=0;$ist=0;foreach($claims as $f){if($f['storniert_am']===null){$soll+=(int)$f['betrag_cent'];$ist+=(int)$f['bezahlt_cent'];}}
        // Unallocated credits are lifetime values, not constrained to the selected year.
        $credit=$this->one('SELECT COALESCE((SELECT SUM(betrag_cent) FROM vs_zahlungen WHERE mitglied_id=? AND storniert_am IS NULL),0) AS eingang,
          COALESCE((SELECT SUM(a.betrag_cent) FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id
          WHERE z.mitglied_id=? AND z.storniert_am IS NULL),0) AS verwendet',[$memberId,$memberId]);
        return ['member'=>$person,'claims'=>$claims,'payments'=>$payments,
            'totals'=>['soll'=>$soll,'ist'=>$ist,'offen'=>max(0,$soll-$ist),
              'guthaben'=>max(0,(int)$credit['eingang']-(int)$credit['verwendet'])]];
    }
    public function rates(): array
    {return $this->all('SELECT t.id,t.art,t.abteilung_id,t.gueltig_ab,t.betrag_cent,a.name AS abteilung_name
       FROM vs_tarife t LEFT JOIN abteilungen a ON a.abteilungs_id=t.abteilung_id
       ORDER BY t.art,t.abteilung_id,t.gueltig_ab DESC,t.id DESC');}
    public function setRate(string $kind,int $departmentId,string $date,int $cents,int $actor): void
    {
        try{$this->execute('INSERT INTO vs_tarife(art,abteilung_id,gueltig_ab,betrag_cent,erstellt_von) VALUES (?,?,?,?,?)',[$kind,$departmentId,$date,$cents,$actor]);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new \DomainException('Für diesen Beitragstyp und Gültigkeitstag existiert bereits ein Tarif.');throw $e;}
    }
    public function createClaim(int $member,string $period,string $kind,int $department,string $description,int $cents,string $due,int $actor): int
    {
        return $this->transaction(function()use($member,$period,$kind,$department,$description,$cents,$due,$actor):int{
            $row=$this->member($member,true);if((int)$row['status']!==1)throw new \DomainException('Mitglied ist nicht aktiv.');
            try{$this->execute('INSERT INTO vs_forderungen(mitglied_id,periode,art,abteilung_id,beschreibung,betrag_cent,faellig_am,erstellt_von) VALUES(?,?,?,?,?,?,?,?)',
                  [$member,$period,$kind,$department,$description,$cents,$due,$actor]);}
            catch(PDOException $e){if($e->getCode()==='23000')throw new \DomainException('Für diesen Monat und Beitragstyp existiert bereits eine Forderung.');throw $e;}
            return (int)$this->db()->lastInsertId();
        });
    }
    private function rateAt(string $kind,int $dept,string $date): ?int
    {
        $row=$this->one('SELECT betrag_cent FROM vs_tarife WHERE art=? AND abteilung_id=? AND gueltig_ab<=? ORDER BY gueltig_ab DESC,id DESC LIMIT 1',[$kind,$dept,$date]);
        return $row===null?null:(int)$row['betrag_cent'];
    }
    public function generateMonth(string $period,int $actor): array
    {
        $date=$period.'-01';$last=(new DateTimeImmutable($date))->format('Y-m-t');
        return $this->transaction(function()use($period,$date,$last,$actor):array{
            $members=$this->all('SELECT mitglieder_id FROM mitglieder WHERE status=1 AND mitgliedschaft<>2 AND beitritt<=? ORDER BY mitglieder_id',[$last]);
            $added=0;$existing=0;$sum=0;
            $insert=$this->db()->prepare('INSERT INTO vs_forderungen(mitglied_id,periode,art,abteilung_id,beschreibung,betrag_cent,faellig_am,erstellt_von) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id');
            foreach($members as $m){
                $id=(int)$m['mitglieder_id'];$person=$this->member($id,true);
                if((int)$person['status']!==1||(int)$person['mitgliedschaft']===2)continue;
                $birthday=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$person['geburtstag']);
                $age=$birthday ? $birthday->diff(new DateTimeImmutable($date))->y : 99;
                $kind=$age<18?'kind':'erwachsen';$base=$this->rateAt($kind,0,$date);
                $candidates=$base>0?[['grundbeitrag',0,($kind==='kind'?'Kinderbeitrag':'Mitgliedsbeitrag'),$base]]:[];
                $departments=$this->all('SELECT a.abteilungs_id,a.name,a.aktivenumlage FROM abteilungszugehoerigkeit z JOIN abteilungen a ON a.abteilungs_id=z.abteilung
                    WHERE z.mitglied=? AND z.aktiv=1 AND a.status=1 AND z.beitrittdate<=? ORDER BY a.abteilungs_id',[$id,$last]);
                foreach($departments as $d){$dept=(int)$d['abteilungs_id'];$rate=$this->rateAt('abteilung',$dept,$date);
                    if($rate===null)$rate=(int)$d['aktivenumlage'];
                    if($rate>0)$candidates[]=['abteilung',$dept,'Aktivenumlage: '.substr((string)$d['name'],0,165),$rate];
                }
                foreach($candidates as [$art,$dep,$desc,$cents]){
                    if($cents>100000000)throw new \DomainException('Beitragssatz überschreitet das zulässige Maximum.');
                    $insert->execute([$id,$period,$art,$dep,$desc,$cents,$last,$actor]);
                    if($insert->rowCount()===1){$added++;$sum+=$cents;}else{$existing++;}
                }
            }
            return ['new'=>$added,'existing'=>$existing,'cent'=>$sum];
        });
    }
    /** Called inside a transaction with the member locked. */
    private function allocate(int $member,int $paymentId,?int $preferred=null): int
    {
        $p=$this->one('SELECT id,betrag_cent FROM vs_zahlungen WHERE id=? AND mitglied_id=? AND storniert_am IS NULL FOR UPDATE',[$paymentId,$member]);
        if(!$p)throw new \DomainException('Zahlung fehlt oder wurde storniert.');
        $used=$this->one('SELECT COALESCE(SUM(betrag_cent),0) AS cent FROM vs_zuordnungen WHERE zahlung_id=?',[$paymentId]);
        $remain=(int)$p['betrag_cent']-(int)$used['cent'];if($remain<=0)return 0;
        $claims=$this->all('SELECT f.id,f.betrag_cent,COALESCE((SELECT SUM(a.betrag_cent) FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id
                    WHERE a.forderung_id=f.id AND z.storniert_am IS NULL),0) AS paid FROM vs_forderungen f
                    WHERE f.mitglied_id=? AND f.storniert_am IS NULL ORDER BY f.faellig_am,f.id',[$member]);
        if($preferred!==null){
            if(!array_filter($claims,static fn(array $r):bool=>(int)$r['id']===$preferred))throw new \DomainException('Forderung gehört nicht zum Mitglied.');
            usort($claims,static fn(array $a,array $b):int=>((int)$a['id']===$preferred?-1:((int)$b['id']===$preferred?1:0)));
        }
        $stmt=$this->db()->prepare('INSERT INTO vs_zuordnungen(zahlung_id,forderung_id,betrag_cent) VALUES(?,?,?) ON DUPLICATE KEY UPDATE betrag_cent=betrag_cent+VALUES(betrag_cent)');
        $allocated=0;
        foreach($claims as $f){$open=max(0,(int)$f['betrag_cent']-(int)$f['paid']);$part=min($remain,$open);
            if($part<=0)continue;$stmt->execute([$paymentId,(int)$f['id'],$part]);$allocated+=$part;$remain-=$part;
            if($remain===0)break;
        }
        return $allocated;
    }
    public function receivePayment(int $member,int $cents,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred): array
    {
        return $this->transaction(function()use($member,$cents,$date,$channel,$reference,$note,$actor,$preferred):array{
            $person=$this->member($member,true);if((int)$person['status']!==1)throw new \DomainException('Mitglied ist nicht aktiv.');
            $this->execute('INSERT INTO vs_zahlungen(mitglied_id,betrag_cent,gezahlt_am,zahlungsweg,zahler_name,referenz,bemerkung,erstellt_von) VALUES(?,?,?,?,?,?,?,?)',
                [$member,$cents,$date,$channel,'',$reference,$note,$actor]);
            $id=(int)$this->db()->lastInsertId();$allocated=$this->allocate($member,$id,$preferred);
            return ['id'=>$id,'allocated'=>$allocated,'credit'=>$cents-$allocated];
        });
    }
    public function reconcile(int $member): int
    {
        return $this->transaction(function()use($member):int{
            $this->member($member,true);
            $payments=$this->all('SELECT z.id FROM vs_zahlungen z WHERE z.mitglied_id=? AND z.storniert_am IS NULL ORDER BY z.gezahlt_am,z.id',[$member]);
            $sum=0;foreach($payments as $p)$sum+=$this->allocate($member,(int)$p['id']);
            return $sum;
        });
    }
    public function voidPayment(int $id,string $reason,int $actor): void
    {
        $this->transaction(function()use($id,$reason,$actor):void{
            $p=$this->one('SELECT mitglied_id,storniert_am FROM vs_zahlungen WHERE id=?',[$id]);if(!$p||$p['storniert_am']!==null)throw new \DomainException('Zahlung fehlt oder ist bereits storniert.');
            $this->member((int)$p['mitglied_id'],true);
            // Financial alpha.7 does not implement cashbook reversals: refuse to leave a stale linked bank entry.
            $cashExists=$this->one('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',['vs_kassenbuch']);
            if((int)$cashExists['n']>0){$linked=$this->one('SELECT id FROM vs_kassenbuch WHERE zahlung_id=? LIMIT 1',[$id]);
                if($linked)throw new \DomainException('Zahlung ist mit der Kasse verknüpft und muss später gemeinsam storniert werden.');}
            if($this->execute('UPDATE vs_zahlungen SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=? AND storniert_am IS NULL',[$actor,$reason,$id])!==1)
                throw new \DomainException('Zahlung bereits storniert.');
        });
    }
    public function voidClaim(int $id,string $reason,int $actor): void
    {
        $this->transaction(function()use($id,$reason,$actor):void{
            $f=$this->one('SELECT mitglied_id,storniert_am FROM vs_forderungen WHERE id=?',[$id]);if(!$f||$f['storniert_am']!==null)throw new \DomainException('Forderung fehlt oder ist bereits storniert.');
            $this->member((int)$f['mitglied_id'],true);
            $paid=$this->one('SELECT COALESCE(SUM(a.betrag_cent),0) AS cents FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE a.forderung_id=? AND z.storniert_am IS NULL',[$id]);
            if((int)$paid['cents']>0)throw new \DomainException('Zuerst zugeordnete Zahlungen stornieren.');
            if($this->execute('UPDATE vs_forderungen SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=? AND storniert_am IS NULL',[$actor,$reason,$id])!==1)
                throw new \DomainException('Forderung bereits storniert.');
        });
    }
}
