<?php
/**
 * Reconcile each cash account from opening balance through the reporting year.
 * Voided journal rows remain listed but never contribute to account totals.
 * New accounts opened during the year contribute their starting cash exactly once.
 */
declare(strict_types=1);
namespace Vereinssystem\Reports;

use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\CashReport;

final class PdoReportRepository implements ReportRepository
{
    public function __construct(private readonly Database $database) {}

    /** @param list<mixed> $params @return list<array<string,mixed>> */
    private function rows(string $query,array $params=[]):array
    {
        $statement=$this->database->connection()->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function annual(int $year,?int $accountId=null):CashReport
    {
        // One consistent InnoDB snapshot across account metadata, prior years and journal.
        // This prevents mid-export postings from changing the result of the report.
        $pdo=$this->database->connection();
        if($pdo->inTransaction())throw new \LogicException('Bericht darf nicht in laufender Schreibtransaktion starten.');
        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->beginTransaction();
        try{
            $report=$this->readSnapshot($year,$accountId);
            $pdo->commit();
            return $report;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    private function readSnapshot(int $year,?int $accountId):CashReport
    {
        $start=sprintf('%04d-01-01',$year);
        $end=sprintf('%04d-12-31',$year);
        // A report must also include inactive accounts and every previous-year booking.
        $params=[$end];
        $select='SELECT id,name,typ,anfangsbestand_cent,anfangsdatum,aktiv FROM vs_konten WHERE anfangsdatum<=?';
        if($accountId!==null){$select.=' AND id=?';$params[]=$accountId;}
        $select.=' ORDER BY name,id';
        $accountRows=$this->rows($select,$params);
        if($accountId!==null && $accountRows===[])throw new \DomainException('Konto im gewählten Jahr nicht vorhanden.');
        $accounts=[];
        foreach($accountRows as $row){
            $id=(int)$row['id'];
            $created=(string)$row['anfangsdatum'];
            $opening=(int)$row['anfangsbestand_cent'];
            $accounts[$id]=[
                'id'=>$id,'name'=>(string)$row['name'],'type'=>(string)$row['typ'],
                'active'=>(bool)$row['aktiv'],
                // For accounts created this year, their opening cash is a distinct column.
                'opening'=>$created<$start?$opening:0,
                'new_opening'=>$created>=$start?$opening:0,
                'income'=>0,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0,
                'closing'=>0,
            ];
        }
        // Only non-voided prior entries change the balance at 1 January.
        $prior=$this->rows(
            'SELECT konto_id,art,SUM(betrag_cent) AS cent FROM vs_kassenbuch '.
            'WHERE datum<? AND storniert_am IS NULL GROUP BY konto_id,art',[$start]);
        foreach($prior as $row){
            $id=(int)$row['konto_id'];
            if(!isset($accounts[$id]))continue;
            $sign=in_array($row['art'],['einnahme','umbuchung_ein'],true)?1:-1;
            $accounts[$id]['opening'] += $sign*(int)$row['cent'];
        }
        // No LIMIT: a report must not silently exclude the 301st booking.
        $entryRows=$this->rows(
            'SELECT b.id,b.konto_id,k.name AS kontoname,b.datum,b.art,b.betrag_cent,'.
            'b.kategorie,b.beschreibung,b.belegnummer,b.zahlung_id,b.storniert_am '.
            'FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id '.
            'WHERE b.datum BETWEEN ? AND ?'.($accountId!==null?' AND b.konto_id=?':'').
            ' ORDER BY b.datum,b.id',
            $accountId!==null?[$start,$end,$accountId]:[$start,$end]
        );
        $entries=[];
        $map=['einnahme'=>'income','ausgabe'=>'expense','umbuchung_ein'=>'transfer_in','umbuchung_aus'=>'transfer_out'];
        foreach($entryRows as $row){
            $id=(int)$row['konto_id'];
            if(!isset($accounts[$id]))continue;
            $type=(string)$row['art'];
            $cents=(int)$row['betrag_cent'];
            $void=$row['storniert_am']!==null;
            if(!$void && isset($map[$type]))$accounts[$id][$map[$type]]+=$cents;
            $entries[]=[
                'id'=>(int)$row['id'],'account_id'=>$id,'account'=>(string)$row['kontoname'],
                'date'=>(string)$row['datum'],'type'=>$type,'amount'=>$cents,
                'category'=>(string)$row['kategorie'],'description'=>(string)$row['beschreibung'],
                'receipt'=>(string)$row['belegnummer'],'void'=>$void,
                'payment_id'=>$row['zahlung_id']===null?null:(int)$row['zahlung_id'],
            ];
        }
        $totals=array_fill_keys(['opening','new_opening','income','expense','transfer_in','transfer_out','closing'],0);
        foreach($accounts as &$account){
            $account['closing']=$account['opening']+$account['new_opening']+$account['income']
                -$account['expense']+$account['transfer_in']-$account['transfer_out'];
            foreach($totals as $field=>$_)$totals[$field]+=$account[$field];
        }
        unset($account);
        return new CashReport($year,$accountId,array_values($accounts),$entries,$totals);
    }
}
