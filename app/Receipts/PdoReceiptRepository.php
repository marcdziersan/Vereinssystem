<?php
/** PDO receipt metadata adapter, using the original vs_belege schema from master. */
declare(strict_types=1);
namespace Vereinssystem\Receipts;
use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\Receipt;

final class PdoReceiptRepository implements ReceiptRepository
{
    public function __construct(private readonly Database $db) {}
    private static function receipt(array $row):Receipt
    {
        return new Receipt((int)$row['id'],(int)$row['buchung_id'],(string)$row['dateiname'],
            (string)$row['speichername'],(string)$row['mime'],(int)$row['groesse'],
            (string)$row['sha256'],(int)$row['hochgeladen_von'],(string)$row['erstellt_am']);
    }
    public function byId(int $id):?Receipt
    {
        $st=$this->db->connection()->prepare('SELECT * FROM vs_belege WHERE id=?');
        $st->execute([$id]);$row=$st->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?self::receipt($row):null;
    }
    public function forEntries(array $entryIds):array
    {
        $ids=array_values(array_unique(array_filter($entryIds,static fn($n)=>is_int($n)&&$n>0)));
        if(!$ids)return [];
        // Placeholders are generated from the array length, never from request text.
        $sql='SELECT * FROM vs_belege WHERE buchung_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY id';
        $st=$this->db->connection()->prepare($sql);$st->execute($ids);
        $result=[];
        foreach($st->fetchAll(PDO::FETCH_ASSOC) as $row){$r=self::receipt($row);$result[$r->entryId][]=$r;}
        return $result;
    }
    public function add(int $entryId,string $original,string $stored,string $mime,int $size,string $checksum,int $actor):int
    {
        $pdo=$this->db->connection();
        $pdo->beginTransaction();
        try{
            $exists=$pdo->prepare('SELECT id FROM vs_kassenbuch WHERE id=? FOR UPDATE');
            $exists->execute([$entryId]);
            if(!$exists->fetchColumn())throw new \DomainException('Kassenbuchung nicht vorhanden.');
            $insert=$pdo->prepare('INSERT INTO vs_belege (buchung_id,dateiname,speichername,mime,groesse,sha256,hochgeladen_von) VALUES (?,?,?,?,?,?,?)');
            $insert->execute([$entryId,$original,$stored,$mime,$size,$checksum,$actor]);
            $id=(int)$pdo->lastInsertId();
            $audit=$pdo->prepare('INSERT INTO vs_audit (akteur,aktion,objekt,objekt_id,details) VALUES (?,?,?,?,?)');
            $audit->execute([$actor,'beleg.hochgeladen','beleg',$id,json_encode(['buchung_id'=>$entryId],JSON_THROW_ON_ERROR)]);
            $pdo->commit();return $id;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
