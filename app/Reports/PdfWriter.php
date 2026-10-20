<?php
/**
 * Dependency-free, multipage PDF 1.4 writer for plain-text tabular documents.
 *
 * Coordinates are PDF points; text is encoded as WinAnsi (Windows-1252), and
 * control characters, parentheses and backslashes are escaped before writing.
 * No HTML, shell command, external network request or user-provided PDF is used.
 */
declare(strict_types=1);
namespace Vereinssystem\Reports;

final class PdfWriter
{
    /** @var list<list<string>> */
    private array $pages=[[]];
    private int $current=0;
    private int $y=802;

    public function page():void
    {
        $this->pages[]=[];
        $this->current=count($this->pages)-1;
        $this->y=802;
    }

    public function line(string $text,int $size=10,int $indent=40,int $spacing=16):void
    {
        // Preserve every character of long descriptions by wrapping, never by truncation.
        // Approximate a conservative fixed visual width for Helvetica glyphs.
        $limit=max(18,(int)(92*(10/max($size,1)) - ($indent-40)/5));
        $normalized=preg_replace('/\s+/u',' ',$text)??$text;
        $letters=preg_split('//u',$normalized,-1,PREG_SPLIT_NO_EMPTY);
        $chunks=is_array($letters)?array_chunk($letters,$limit):[[$normalized]];
        foreach($chunks?:[[]] as $chars){
            if($this->y<52)$this->page();
            $fragment=implode('',$chars);
            $converted=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$fragment);
            if($converted===false)$converted='';
            $converted=preg_replace('/[\x00-\x1f\x7f]/',' ', $converted)??'';
            $converted=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$converted);
            $this->pages[$this->current][]=sprintf('BT /F1 %d Tf %d %d Td (%s) Tj ET',$size,$indent,$this->y,$converted);
            $this->y-=$spacing;
        }
    }

    public function space(int $points=8):void {$this->y-=$points;}

    public function finish():string
    {
        $pages=count($this->pages);
        $objects=[];
        $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $kids=[];
        for($page=0;$page<$pages;$page++){
            $pageObject=4+2*$page;
            $streamObject=$pageObject+1;
            $kids[]=$pageObject.' 0 R';
            $stream=implode("\n",$this->pages[$page]);
            // Display page numbering even for documents exceeding one page.
            $stream.="\nBT /F1 9 Tf 475 28 Td (Seite ".($page+1)." / $pages) Tj ET\n";
            $objects[$pageObject]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$streamObject.' 0 R >>';
            $objects[$streamObject]='<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream";
        }
        $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.$pages.' >>';
        ksort($objects,SORT_NUMERIC);
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offset=[0];
        foreach($objects as $id=>$content){$offset[$id]=strlen($pdf);$pdf.="$id 0 obj\n$content\nendobj\n";}
        $xref=strlen($pdf);
        $pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        for($i=1;$i<=count($objects);$i++)$pdf.=sprintf('%010d 00000 n ', $offset[$i])."\n";
        $pdf.="trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
        return $pdf;
    }
}
