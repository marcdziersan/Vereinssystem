<?php
/** SQL reporting adapter: read-only, bound PDO parameters, includes closed accounts. */
declare(strict_types=1);
namespace Vereinssystem\Reports;
use PDO;
use Vereinssystem\Core\Database;
final class PdoCashReportRepository implements CashReportRepository
{
    public function __construct(private readonly Database $database) {}

    /** A consistent snapshot for cross-table balance calculations. */
    public function snapshot(int $year,?int $accountId):array
    {
        $pdo=$this->database->connection();
        // Explicit REPEATABLE READ: account opening and journal share one snapshot.
        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->beginTransaction();
        try{
            $data=['accounts'=>$this->accountsForYear($year,$accountId),
                'entries'=>$this->entriesForYear($year,$accountId)];
            $pdo->commit();
            return $data;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    private function accountsForYear(int $year, ?int $accountId): array
    {
        $start=sprintf('%04d-01-01',$year);
        $end=sprintf('%04d-12-31',$year);
        $sql="SELECT k.id,k.name,k.typ,k.anfangsbestand_cent,k.anfangsdatum,k.aktiv,
            COALESCE((SELECT SUM(CASE WHEN b.art IN ('einnahme','umbuchung_ein') THEN b.betrag_cent ELSE -b.betrag_cent END)
                FROM vs_kassenbuch b WHERE b.konto_id=k.id AND b.storniert_am IS NULL AND b.datum < ?),0) AS vorjahre_cent
            FROM vs_konten k WHERE k.anfangsdatum <= ?";
        $params=[$start,$end];
        if($accountId!==null){$sql.=' AND k.id=?';$params[]=$accountId;}
        $sql.=' ORDER BY k.name,k.id';
        $query=$this->database->connection()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    private function entriesForYear(int $year, ?int $accountId): array
    {
        $sql='SELECT b.id,b.konto_id,k.name AS konto,b.datum,b.art,b.betrag_cent,b.kategorie,
              b.beschreibung,b.belegnummer,b.zahlung_id,b.storniert_am
              FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id
              WHERE b.datum BETWEEN ? AND ?';
        $params=[sprintf('%04d-01-01',$year),sprintf('%04d-12-31',$year)];
        if($accountId!==null){$sql.=' AND b.konto_id=?';$params[]=$accountId;}
        $sql.=' ORDER BY b.datum,b.id';
        $query=$this->database->connection()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
