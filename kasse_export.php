<?php
/**
 * Vereinssystem 2.3.1 – Modul: kasse export.
 * Pfad: kasse_export.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Rechtegeschützter CSV- und PDF-Export der Kassenverwaltung. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/cash.php';
require_once __DIR__.'/inc/pdf.php';
vk_require($user,'kasse.export');
if (!vk_ready()) {http_response_code(409);exit('Kassenschema fehlt.');}
$format=(string)($_GET['format']??'csv');
try {$year=vf_year((string)($_GET['jahr']??date('Y')));}catch(DomainException){$year=(int)date('Y');}
$all=vk_entries($year,null,10000);$balances=vk_balances($year);
if ($format==='receipt') {
    $id=(int)($_GET['id']??0);
    $entry=vf_one('SELECT b.*,k.name AS konto_name FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id WHERE b.id=?',[$id]);
    if(!$entry){http_response_code(404);exit('Buchung nicht gefunden.');}
    $content=vk_pdf_bytes('Vereinssystem – Buchungsbeleg #'.$id, [
        'Buchungsdatum: '.$entry['datum'], 'Konto: '.$entry['konto_name'],
        'Art: '.$entry['art'],'Betrag: '.vf_money((int)$entry['betrag_cent']),
        'Kategorie: '.$entry['kategorie'],'Beschreibung: '.$entry['beschreibung'],
        'Belegnummer: '.$entry['belegnummer'], 'Erfasst von Mitglied-ID: #'.$entry['erstellt_von'],
        'Status: '.($entry['storniert_am']?'Storniert am '.$entry['storniert_am']:'Aktiv'),
        'Erstellt am: '.$entry['erstellt_am'],
        'Dieser automatisch erzeugte Buchungsbeleg ist keine steuerliche Rechnung und keine Bankbestätigung.',
    ]);
    header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="Kassenbeleg-'.$id.'.pdf"');
    header('X-Content-Type-Options: nosniff');echo $content;exit;
}
if($format==='csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Vereinskasse-'.$year.'.csv"');
    header('X-Content-Type-Options: nosniff');
    $stream=fopen('php://output','wb');fwrite($stream,"\xEF\xBB\xBF");
    fputcsv($stream,['ID','Datum','Konto','Art','Betrag EUR','Kategorie','Beschreibung','Belegnummer','Beitragszahlung-ID','Storniert am'], ';');
    foreach($all as $entry){
        // Spreadsheet Formula Injection: alle extern befüllbaren Felder neutralisieren.
        $safe=static function($value):string {$v=(string)$value;return preg_match('/^[\s]*[=+@\-]/u',$v)?"'".$v:$v;};
        fputcsv($stream,[(int)$entry['id'],$entry['datum'],$safe($entry['kontoname']),$entry['art'],
            number_format((int)$entry['betrag_cent']/100,2,',',''),$safe($entry['kategorie']),$safe($entry['beschreibung']),
            $safe($entry['belegnummer']),$entry['zahlung_id']??'', $entry['storniert_am']??''], ';');
    }
    fclose($stream);exit;
}
if($format==='pdf') {
    $lines=['Berichtsjahr: '.$year, 'Erzeugt am: '.date('d.m.Y H:i'), ''];
    $total=0;
    foreach($balances as $account){
        $total+=$account['saldo_cent'];
        $lines[]='Konto: '.$account['name'].' ('.$account['typ'].')';
        $lines[]='Einnahmen: '.vf_money($account['einnahmen_cent']).'  Ausgaben: '.vf_money($account['ausgaben_cent']);
        $lines[]='Umbuchungen ein: '.vf_money($account['umbuchung_ein_cent']).'  aus: '.vf_money($account['umbuchung_aus_cent']);
        $lines[]='Endbestand: '.vf_money($account['saldo_cent']);$lines[]='';
    }
    $lines[]='Gesamter Kassenbestand (Auswahl aller Konten): '.vf_money($total);
    $lines[]='';$lines[]='Externe Einnahmen / Ausgaben je Kategorie:';
    $categories=[];
    foreach($all as $entry){
        if($entry['storniert_am']!==null || !in_array($entry['art'],['einnahme','ausgabe'],true))continue;
        $key=($entry['kategorie']?:'Ohne Kategorie');
        if(!isset($categories[$key]))$categories[$key]=['einnahme'=>0,'ausgabe'=>0];
        $categories[$key][$entry['art']]+=(int)$entry['betrag_cent'];
    }
    ksort($categories);
    foreach($categories as $category=>$amounts){
        $lines[]=$category.' | plus '.vf_money($amounts['einnahme']).' | minus '.vf_money($amounts['ausgabe']);
    }
    $lines[]='';
    $lines[]='Stornierte Buchungen sind in Salden nicht enthalten; im CSV einzeln nachvollziehbar.';
    $lines[]='Vollständige Einzelbuchungen: CSV-Export. Bankabgleich ist gesondert erforderlich.';
    $pdf=vk_pdf_bytes('Vereinssystem – Kassenjahresbericht '.$year,$lines);
    header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="Kassenbericht-'.$year.'.pdf"');
    header('X-Content-Type-Options: nosniff');echo $pdf;exit;
}
http_response_code(400);exit('Format unbekannt.');