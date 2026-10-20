<?php
/**
 * Annual cashbook reconciliation. Opening balances and movement are deliberately
 * separate: an account created during the year contributes an opening-in-year,
 * not imaginary January revenue. Voided entries remain visible but count zero.
 */
declare(strict_types=1);
namespace Vereinssystem\Services;
use Vereinssystem\Models\CashAnnualReport;
use Vereinssystem\Reports\CashReportRepository;
final class CashReportService
{
    public function __construct(private readonly CashReportRepository $repository) {}

    public function annual(int $year,?int $accountId=null):CashAnnualReport
    {
        if($year<2000||$year>2099)throw new \DomainException('Jahr muss zwischen 2000 und 2099 liegen.');
        if($accountId!==null&&$accountId<1)throw new \DomainException('Ungültige Kontonummer.');
        $start=sprintf('%04d-01-01',$year);
        $snapshot=$this->repository->snapshot($year,$accountId);
        $accounts=[];
        $totals=['start'=>0,'new_opening'=>0,'income'=>0,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0,'end'=>0,'void_count'=>0,'rows'=>0];
        foreach($snapshot['accounts'] as $row){
            $id=(int)$row['id'];
            $opening=(int)$row['anfangsbestand_cent'];
            $startCent=(int)$row['vorjahre_cent']+((string)$row['anfangsdatum']<$start?$opening:0);
            $newOpening=(string)$row['anfangsdatum']>=$start?$opening:0;
            $accounts[$id]=[
                'id'=>$id,'name'=>(string)$row['name'],'type'=>(string)$row['typ'],
                'active'=>(bool)$row['aktiv'],'start'=>$startCent,'new_opening'=>$newOpening,
                'income'=>0,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0,
                'end'=>$startCent+$newOpening,
            ];
            $totals['start']+=$startCent;
            $totals['new_opening']+=$newOpening;
        }
        if($accountId!==null && !$accounts)throw new \DomainException('Konto nicht gefunden.');
        $months=[];
        for($m=1;$m<=12;$m++)$months[$m]=['month'=>$m,'income'=>0,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0];
        $entries=$snapshot['entries'];
        $categories=[];
        foreach($entries as $row){
            $id=(int)$row['konto_id'];
            if(!isset($accounts[$id]))throw new \DomainException('Kassenkonto fehlt im Jahresabschluss.');
            if($row['storniert_am']!==null){$totals['void_count']++;continue;}
            $name=match($row['art']){'einnahme'=>'income','ausgabe'=>'expense','umbuchung_ein'=>'transfer_in','umbuchung_aus'=>'transfer_out',default=>throw new \DomainException('Unbekannte Buchungsart im Journal.')};
            $cents=(int)$row['betrag_cent'];
            if($cents<=0)throw new \DomainException('Ungültiger Buchungsbetrag.');
            $month=(int)substr((string)$row['datum'],5,2);
            if($month<1||$month>12)throw new \DomainException('Ungültiges Buchungsdatum.');
            if(in_array($name,['income','expense'],true)){
                $category=trim((string)$row['kategorie']);
                $category=$category!==''?$category:'Ohne Kategorie';
                if(!isset($categories[$category]))$categories[$category]=['category'=>$category,'income'=>0,'expense'=>0];
                $categories[$category][$name]+=$cents;
            }
            $accounts[$id][$name]+=$cents;
            $totals[$name]+=$cents;
            $months[$month][$name]+=$cents;
        }
        foreach($accounts as &$account){
            $account['end']=$account['start']+$account['new_opening']+$account['income']-$account['expense']+$account['transfer_in']-$account['transfer_out'];
            $totals['end']+=$account['end'];
        }
        unset($account);
        $totals['rows']=count($entries);
        ksort($categories,SORT_NATURAL|SORT_FLAG_CASE);
        return new CashAnnualReport($year,$accountId,array_values($accounts),$entries,array_values($months),array_values($categories),$totals);
    }
}
