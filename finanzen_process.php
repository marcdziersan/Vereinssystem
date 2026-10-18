<?php
/**
 * Vereinssystem 2.3.0 – Modul: finanzen process.
 * Pfad: finanzen_process.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/finance.php';
require_once __DIR__.'/inc/cash.php';
if((int)$user['rights']<4){http_response_code(403);exit('Keine Berechtigung für Finanzbuchungen.');}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit('Nur POST.');}
// Anti-Doppelbuchung zusätzlich zur CSRF-Prüfung in checkuser.
$nonce=(string)($_POST['nonce']??'');
if(empty($_SESSION['vf_nonce']) || $nonce==='' || !hash_equals($_SESSION['vf_nonce'],$nonce)){
 http_response_code(409);exit('Dieses Formular wurde bereits verarbeitet. Bitte neu laden.');
}
unset($_SESSION['vf_nonce']);
$action=(string)($_POST['action']??'');
$member=max(0,(int)($_POST['mitglied']??0));
$year=(string)($_POST['jahr']??date('Y'));
try{$year=(string)vf_year($year);}catch(DomainException $e){$year=date('Y');}
try {
 vf_require_ready();
 if($action==='generate'){
   if((int)$user['rights']<5)throw new DomainException('Sollstellungen dürfen nur Administratoren erzeugen.');
   $r=vf_generate_month((string)($_POST['periode']??''),(int)$user['mitglieder_id']);
   $notice='Monat angelegt: '.$r['neu'].' neue Forderungen ('.vf_money($r['cent']).'), '.$r['vorhanden'].' bereits vorhanden.';
 } elseif($action==='payment') {
   if($member<1)throw new DomainException('Mitglied auswählen.');
   $preferred=filter_var($_POST['forderung']??0,FILTER_VALIDATE_INT);
   if (vk_ready() && (int)($_POST['konto']??0)<1) throw new DomainException('Bitte tatsächliches Zahlungskonto auswählen.');
   $r=vf_add_payment($member,(string)($_POST['betrag']??''),(string)($_POST['datum']??''),(string)($_POST['weg']??''),trim((string)($_POST['zahler']??'')),trim((string)($_POST['referenz']??'')),trim((string)($_POST['bemerkung']??'')),(int)$user['mitglieder_id'],$preferred>0?$preferred:null,vk_ready()?max(0,(int)($_POST['konto']??0)):null);
   $notice='Zahlung #'.$r['id'].' verbucht; '.vf_money($r['zugeordnet']).' zugeordnet; '.vf_money($r['guthaben']).' Guthaben.';
 }elseif($action==='reconcile'){
   if($member<1)throw new DomainException('Mitglied auswählen.');
   $notice='Guthaben verrechnet: '.vf_money(vf_reconcile($member)).'.';
 }elseif($action==='void_payment'){
   vf_void_payment((int)($_POST['id']??0),trim((string)($_POST['grund']??'')),(int)$user['mitglieder_id']);
   $notice='Zahlung storniert (im Protokoll erhalten).';
 }elseif($action==='void_charge'){
   if((int)$user['rights']<5)throw new DomainException('Nur Administratoren können Forderungen stornieren.');
   vf_void_charge((int)($_POST['id']??0),trim((string)($_POST['grund']??'')),(int)$user['mitglieder_id']);
   $notice='Forderung storniert (im Protokoll erhalten).';
 }else throw new DomainException('Ungültige Aktion.');
 $_SESSION['vf_flash']=['ok',$notice];
}catch(Throwable $e){
 error_log('[Vereinssystem Finance] '.$e->getMessage());
 $_SESSION['vf_flash']=['error',$e instanceof DomainException?$e->getMessage():'Buchung fehlgeschlagen. Bitte Protokoll prüfen.'];
}
$target=$member>0?'finanzkonto.php?mitglied='.$member.'&jahr='.$year:'finanzen.php?jahr='.$year;
header('Location: '.$target,true,303);exit;