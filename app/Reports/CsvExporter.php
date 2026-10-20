<?php
/** Safe UTF-8/Excel-compatible semicolon CSV with formula-injection protection. */
declare(strict_types=1);
namespace Vereinssystem\Reports;
use Vereinssystem\Models\CashAnnualReport;
final class CsvExporter
{
    /** Escapes formulas in untrusted human-readable fields, not numeric totals. */
    public static function safeCell(string $value):string
    {
        // Excel treats leading whitespace/control characters followed by =+-@ as formulas.
        if(preg_match('/^[\x00-\x20]*[=+@-]/u',$value))return "'".$value;
        return $value;
    }
    public function export(CashAnnualReport $report):string
    {
        $stream=fopen('php://temp','w+');
        if($stream===false)throw new \RuntimeException('CSV konnte nicht geöffnet werden.');
        fwrite($stream,"\xEF\xBB\xBF"); // UTF-8 BOM for Excel (German locale).
        fputcsv($stream,['ID','Datum','Konto','Art','Betrag EUR','Kategorie','Beschreibung','Belegnummer','Beitragszahlung','Status'],';', '"', '');
        foreach($report->entries as $row){
            $cents=(int)$row['betrag_cent'];
            $signed=in_array($row['art'],['ausgabe','umbuchung_aus'],true)?-$cents:$cents;
            $euros=number_format($signed/100,2,',','');
            fputcsv($stream,[(string)$row['id'],(string)$row['datum'],self::safeCell((string)$row['konto']),
                (string)$row['art'],$euros,self::safeCell((string)$row['kategorie']),
                self::safeCell((string)$row['beschreibung']),self::safeCell((string)$row['belegnummer']),
                $row['zahlung_id']===null?'':(string)$row['zahlung_id'],$row['storniert_am']===null?'Aktiv':'Storniert'], ';','"','');
        }
        rewind($stream);
        $result=stream_get_contents($stream);
        fclose($stream);
        if($result===false)throw new \RuntimeException('CSV konnte nicht gelesen werden.');
        return $result;
    }
}
