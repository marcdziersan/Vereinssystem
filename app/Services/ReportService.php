<?php
/** Business-level annual totals and downloadable representations of a reconciled cash journal. */
declare(strict_types=1);
namespace Vereinssystem\Services;

use Vereinssystem\Finance\Money;
use Vereinssystem\Models\CashReport;
use Vereinssystem\Reports\{ReportRepository,PdfWriter};

final class ReportService
{
    public function __construct(private readonly ReportRepository $repository) {}

    public function annual(int $year,?int $accountId=null):CashReport
    {
        if($year<2000||$year>2099)throw new \DomainException('Jahr muss zwischen 2000 und 2099 liegen.');
        if($accountId!==null&&$accountId<1)throw new \DomainException('Ungültiges Konto.');
        return $this->repository->annual($year,$accountId);
    }

    /** CSV output for Excel/LibreOffice; prefix potential formulas before quoting. */
    public function csv(CashReport $report):string
    {
        $stream=fopen('php://temp','w+');
        if($stream===false)throw new \RuntimeException('CSV-Puffer nicht verfügbar.');
        fwrite($stream,"\xEF\xBB\xBF");
        $put=static function(array $fields)use($stream):void{
            $safe=array_map(static function(mixed $value):string{
                $text=str_replace(["\r","\n","\t"],' ',(string)$value);
                return preg_match('/^\s*[=+@\-]/u',$text)==1?"'".$text:$text;
            },$fields);
            fputcsv($stream,$safe,';','"','');
        };
        $put(['Kassenjournal','Jahr',$report->year]);
        $put(['Datum','Buchung','Konto','Art','Betrag EUR','Kategorie','Beschreibung','Belegnummer','Beitragszahlung','Status']);
        foreach($report->entries as $entry){
            $put([$entry['date'],$entry['id'],$entry['account'],$entry['type'],Money::format($entry['amount']),
                $entry['category'],$entry['description'],$entry['receipt'],(string)($entry['payment_id']??''),
                $entry['void']?'storniert':'gebucht']);
        }
        $put([]);$put(['Kontenauswertung']);
        $put(['Konto','Anfang 01.01.','Eröffnung im Jahr','Einnahmen','Ausgaben','Umbuchungen ein','Umbuchungen aus','Ende 31.12.']);
        foreach($report->accounts as $a)$put([$a['name'],Money::format($a['opening']),Money::format($a['new_opening']),
            Money::format($a['income']),Money::format($a['expense']),Money::format($a['transfer_in']),Money::format($a['transfer_out']),Money::format($a['closing'])]);
        $t=$report->totals;
        $put(['Gesamt',Money::format($t['opening']),Money::format($t['new_opening']),Money::format($t['income']),
            Money::format($t['expense']),Money::format($t['transfer_in']),Money::format($t['transfer_out']),Money::format($t['closing'])]);
        rewind($stream);$data=stream_get_contents($stream);fclose($stream);
        if($data===false)throw new \RuntimeException('CSV-Ausgabe fehlgeschlagen.');
        return $data;
    }

    /** Build a real, multi-page PDF, not an HTML document renamed to .pdf. */
    public function pdf(CashReport $report):string
    {
        $pdf=new PdfWriter();
        $pdf->line('Vereinssystem - Jahreskassenbericht '.$report->year,17,40,25);
        $pdf->line('Erstellt am '.date('d.m.Y H:i').' - stornierte Buchungen separat ausgewiesen',9);
        $pdf->space(10);
        foreach($report->accounts as $a){
            $pdf->line($a['name'].' ('.$a['type'].')',12,40,20);
            $pdf->line('Anfang: '.Money::format($a['opening']).'  /  Eröffnung: '.Money::format($a['new_opening']));
            $pdf->line('Einnahmen: '.Money::format($a['income']).'  |  Ausgaben: '.Money::format($a['expense']));
            $pdf->line('Umbuchung ein: '.Money::format($a['transfer_in']).'  |  aus: '.Money::format($a['transfer_out']));
            $pdf->line('Endbestand: '.Money::format($a['closing']));
            $pdf->space(8);
        }
        $t=$report->totals;$pdf->line('GESAMTBESTAND 01.01.: '.Money::format($t['opening']),11);
        $pdf->line('Anfangsbestände neuer Konten: '.Money::format($t['new_opening']));
        $pdf->line('Einnahmen: '.Money::format($t['income']).'  |  Ausgaben: '.Money::format($t['expense']));
        $pdf->line('GESAMTBESTAND 31.12.: '.Money::format($t['closing']),11);
        $pdf->page();$pdf->line('Kassenjournal '.$report->year.' - Buchungsdetails',14,40,24);
        foreach($report->entries as $e){
            $pdf->line($e['date'].'  #'.$e['id'].'  '.$e['account'].'  '.$e['type'].'  '.Money::format($e['amount']).($e['void']?' [STORNIERT]':''),9);
            $pdf->line($e['description'].'  /  '.$e['category'].($e['receipt']!==''?'  Beleg: '.$e['receipt']:''),9,52,17);
        }
        if(!$report->entries)$pdf->line('Keine Buchungen im ausgewählten Jahr.');
        return $pdf->finish();
    }
}
