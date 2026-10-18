<?php
/**
 * Vereinssystem 2.3.0 – Modul: pdf.
 * Pfad: inc/pdf.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/**
 * Kleiner, dependency-freier PDF-Ausgabebaustein für Textquittungen und Berichte.
 * Keine HTML-Interpretation, keine externen Fonts oder Bibliotheken erforderlich.
 * Einfache Helvetica-Druckausgabe im PDF-1.4-Format, keine signierten Belege.
 */
function vk_pdf_escape(string $text): string
{
    // Helvetica im PDF verwendet WinAnsiEncoding. Unbekannte Zeichen ersetzen.
    $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
    $text = $converted === false ? preg_replace('/[^\x20-\x7e]/', '?', $text) : $converted;
    return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', ' ', ' '], $text);
}

/** Erstellt ein valides mehrseitiges PDF als Byte-String. */
function vk_pdf_bytes(string $heading, array $lines): string
{
    $pages = [];
    $current = [];
    foreach (array_merge([$heading, str_repeat('-', 70)], $lines) as $text) {
        $text = preg_replace('/\s+/u', ' ', (string)$text) ?? (string)$text;
        // Zeichenorientierter Umbruch; schützt vor zu langen PDF-Zeilen.
        $chunks = mb_strlen_safe_pdf($text) > 95 ? str_split(vk_pdf_escape($text), 95) : [vk_pdf_escape($text)];
        foreach ($chunks as $chunk) {
            if (count($current) >= 52) { $pages[] = $current; $current = []; }
            $current[] = $chunk;
        }
    }
    if ($current || !$pages) $pages[] = $current;
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = ''; // Pages-Knoten wird ergänzt, sobald alle Pages-Objekte bekannt sind.
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $kids = [];
    foreach ($pages as $i=>$rows) {
        $pageId = count($objects) + 1;
        $streamId = $pageId + 1;
        $kids[] = $pageId . ' 0 R';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$streamId.' 0 R >>';
        $commands = "BT /F1 10 Tf 40 795 Td 13 TL\n";
        foreach ($rows as $row) $commands .= '(' . $row . ") Tj T*\n";
        $commands .= 'ET';
        $objects[] = '<< /Length ' . strlen($commands) . " >>\nstream\n".$commands."\nendstream";
    }
    $objects[1] = '<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ',$kids).'] >>';
    $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
    $offsets = [0];
    foreach ($objects as $index=>$body) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index+1) . " 0 obj\n".$body."\nendobj\n";
    }
    $startXref = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
    for($i=1;$i<=count($objects);$i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
    return $pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$startXref."\n%%EOF\n";
}
function mb_strlen_safe_pdf(string $v): int {return function_exists('mb_strlen') ? mb_strlen($v,'UTF-8') : strlen($v);}