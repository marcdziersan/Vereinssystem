<?php
/**
 * Receipt metadata and audit persistence. All association checks happen inside
 * a transaction with a cash row lock; a voided booking cannot gain new receipts.
 */
declare(strict_types=1);
namespace Vereinssystem\Receipts;
use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\CashReceipt;
final class PdoReceiptRepository implements ReceiptRepository
{
    public function __construct(private readonly Database $database) {}
    private static function map(array $r):CashReceipt
    {
        return new CashReceipt((int)$r['id'],(int)$r['buchung_id'],(string)$r['originalname'],
            (string)$r['mime'],(int)$r['size_bytes'],(string)$r['sha256'],(string)$r['dateiname']);
    }
    public function forEntries(array $entryIds):array
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$entryIds),static fn(int $v):bool=>$v>0)));
        if(!$ids)return [];
        // The journal UI is limited to 300 entries; bound each ID (never interpolate).
        $sql='SELECT * FROM vs_kassenbelege WHERE buchung_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY id';
        $q=$this->database->connection()->prepare($sql);$q->execute($ids);
        $found=[];
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$found[(int)$r['buchung_id']][]=self::map($r);
        return $found;
    }
    public function find(int $id):?CashReceipt
    {
        $q=$this->database->connection()->prepare('SELECT * FROM vs_kassenbelege WHERE id=?');
        $q->execute([$id]);$row=$q->fetch(PDO::FETCH_ASSOC);
        return $row?self::map($row):null;
    }
    public function attach(int $entryId,string $original,string $mime,int $size,string $sha,string $stored,int $actor):int
    {
        $pdo=$this->database->connection();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT id,storniert_am FROM vs_kassenbuch WHERE id=? FOR UPDATE');
            $q->execute([$entryId]);$entry=$q->fetch(PDO::FETCH_ASSOC);
            if(!$entry||$entry['storniert_am']!==null)throw new \DomainException('Buchung fehlt oder ist storniert.');
            $q=$pdo->prepare('INSERT INTO vs_kassenbelege (buchung_id,originalname,mime,size_bytes,sha256,dateiname,erstellt_von) VALUES (?,?,?,?,?,?,?)');
            $q->execute([$entryId,$original,$mime,$size,$sha,$stored,$actor]);
            $id=(int)$pdo->lastInsertId();
            $q=$pdo->prepare('INSERT INTO vs_audit(akteur,aktion,objekt,objekt_id,details) VALUES (?,?,?,?,?)');
            $q->execute([$actor,'kasse.beleg.hochgeladen','beleg',$id,json_encode(['buchung'=>$entryId,'sha256'=>$sha],JSON_THROW_ON_ERROR)]);
            $pdo->commit();return $id;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
