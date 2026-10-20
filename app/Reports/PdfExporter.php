<?php
/**
 * Tiny standalone multi-page PDF/1.4 exporter (A4, Helvetica/WinAnsi).
 * No Composer, external binaries or remote service. The report is a textual
 * account statement, not a legally certified receipt or a signed document.
 */
declare(strict_types=1);
namespace Vereinssystem\Reports;
use Vereinssystem\Finance\Money;
use Vereinssystem\Models\CashAnnualReport;
final class PdfExporter
{
    private static function encode(string $text):string
    {
        $converted=function_exists('iconv')?@iconv('UTF-8','Windows-1252//TRANSLIT',$text):false;
        $value=$converted===false?(preg_replace('/[^\x20-\x7E]/','?', $text)??'?'):$converted;
        return str_replace(["\r","\n"],[' ',' '],$value);
    }
    /** @return list<string> */
    private static function wrap(string $text,int $columns=100):array
    {
        // Split after transcoding: one byte represents one printable WinAnsi glyph.
        $encoded=self::encode(preg_replace('/\s+/u',' ',trim($text))??trim($text));
        $lines=[];
        foreach(explode("\n",wordwrap($encoded,$columns,"\n",true)) as $line){
            $lines[]=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$line);
        }
        return $lines;
    }
    public function export(CashAnnualReport $report):string
    {
        $fmt=static fn(int $c):string=>Money::format($c);
        $lines=['Jahresbericht '.$report->year.($report->accountId===null?' - Alle Konten':' - Konto #'.$report->accountId),'',
            'Anfangssaldo (01.01.): '.$fmt($report->totals['start']),
            'Kontoeroeffnungen im Jahr: '.$fmt($report->totals['new_opening']),
            'Einnahmen: '.$fmt($report->totals['income']).'     Ausgaben: '.$fmt($report->totals['expense']),
            'Umbuchungen eingehend: '.$fmt($report->totals['transfer_in']).'     ausgehend: '.$fmt($report->totals['transfer_out']),
            'Endbestand (31.12.): '.$fmt($report->totals['end']),
            'Stornierte Buchungen: '.$report->totals['void_count'].' (nicht saldowirksam)','','Kontenuebersicht:'];
        foreach($report->accounts as $a)$lines[]=$a['name'].' - '.$a['type'].': '.$fmt($a['end']);
        $lines[]='';$lines[]='Monatsbewegungen (Einnahme / Ausgabe / Umb. Eingang / Ausgang):';
        foreach($report->monthly as $m){
            if($m['income']==0&&$m['expense']==0&&$m['transfer_in']==0&&$m['transfer_out']==0)continue;
            $lines[]=sprintf('%02d',$m['month']).': '.$fmt($m['income']).' / '.$fmt($m['expense']).' / '.$fmt($m['transfer_in']).' / '.$fmt($m['transfer_out']);
        }
        $lines[]='';$lines[]='Einnahmen und Ausgaben nach Kategorie:';
        foreach($report->categories as $cat)$lines[]=$cat['category'].': +'.$fmt($cat['income']).' / -'.$fmt($cat['expense']);
        $lines[]='';$lines[]='Journal (inkl. historischer Stornos):';
        foreach($report->entries as $row){
            $sign=in_array($row['art'],['ausgabe','umbuchung_aus'],true)?'-':'+';
            $lines[]=(string)$row['datum'].'  #'.$row['id'].'  '.(string)$row['konto'].'  '.$sign.$fmt((int)$row['betrag_cent']).
                '  '.(string)$row['beschreibung'].($row['storniert_am']!==null?' [STORNIERT]':'');
        }
        // ISO A4 page layout: 40pt margins, 12pt line-height, 54 text lines.
        $pages=[];$current=[];
        foreach($lines as $text)foreach(self::wrap((string)$text,95) as $line){
            if(count($current)===54){$pages[]=$current;$current=[];}
            $current[]=$line;
        }
        if($current||!$pages)$pages[]=$current;
        $objects=['<< /Type /Catalog /Pages 2 0 R >>','',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'];
        $pageRefs=[];
        foreach($pages as $index=>$rows){
            $pageId=count($objects)+1;$streamId=$pageId+1;$pageRefs[]=$pageId.' 0 R';
            $objects[]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $cmd="BT /F1 9 Tf 39 798 Td 12 TL\n";
            foreach($rows as $row)$cmd.='('.$row.") Tj T*\n";
            $cmd.="ET";
            $objects[]='<< /Length '.strlen($cmd)." >>\nstream\n".$cmd."\nendstream";
        }
        $objects[1]='<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ',$pageRefs).'] >>';
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];
        foreach($objects as $index=>$body){$offsets[]=strlen($pdf);$pdf.=($index+1)." 0 obj\n".$body."\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        for($i=1;$i<=count($objects);$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
        return $pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
