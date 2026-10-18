<?php
/**
 * Vereinssystem 2.3.1 – Modul: kasse beleg.
 * Pfad: kasse_beleg.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Private Belege: Dateitypen/MIME und Hash werden verifiziert; nie direkt ausliefern. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/cash.php';
vk_require($user,'kasse.belege');
$booking=(int)($_GET['buchung']??$_POST['buchung']??0);
$entry=vf_one('SELECT b.*,k.name AS kontoname FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id WHERE b.id=?',[$booking]);
if(!$entry){http_response_code(404);exit('Buchung nicht vorhanden.');}
/** Nur Verzeichnis außerhalb des DOCUMENT_ROOT verwenden. */
function vk_private_dir(): string
{
    $path=getenv('VS_PRIVATE_STORAGE_DIR');
    // Unix (/srv/private) und Windows (C:\private) werden unterstützt.
    $absolute = is_string($path) && ($path !== '') && (
        str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
    );
    if (!$absolute) throw new DomainException('VS_PRIVATE_STORAGE_DIR muss ein absoluter Pfad außerhalb des Webroots sein.');
    if(!is_dir($path) && !mkdir($path,0700,true) && !is_dir($path))throw new RuntimeException('Belegspeicher kann nicht angelegt werden.');
    $real=realpath($path);$root=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
    if ($real === false) throw new DomainException('Privater Belegspeicher nicht erreichbar.');
    $normalize = static fn(string $v): string => strtolower(str_replace('\\', '/', rtrim($v, '/\\')));
    if ($root !== false) {
        $safeRoot = $normalize($root);
        $safeReal = $normalize($real);
        if ($safeReal === $safeRoot || str_starts_with($safeReal, $safeRoot . '/')) {
            throw new DomainException('Belegspeicher darf nicht im öffentlich erreichbaren Webroot liegen.');
        }
    }
    return $real;
}
if(isset($_GET['download'])){
    $file=vf_one('SELECT * FROM vs_belege WHERE id=? AND buchung_id=?',[(int)$_GET['download'],$booking]);
    if(!$file){http_response_code(404);exit('Datei nicht gefunden.');}
    try{$path=vk_private_dir().'/'.$file['speichername'];}catch(Throwable){http_response_code(503);exit('Privater Speicher nicht konfiguriert.');}
    if(!is_file($path)||!hash_equals($file['sha256'],hash_file('sha256',$path))){http_response_code(404);exit('Beleg nicht lesbar / Prüfsumme ungültig.');}
    header('X-Content-Type-Options: nosniff');header('Content-Type: '.$file['mime']);
    header('Content-Disposition: attachment; filename="beleg-'.(int)$file['id'].'"');
    header('Content-Length: '.filesize($path));readfile($path);exit;
}
$error='';$notice='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
    try{
        if(!isset($_FILES['datei'])||$_FILES['datei']['error']!==UPLOAD_ERR_OK)throw new DomainException('Datei fehlt oder Upload fehlgeschlagen.');
        $file=$_FILES['datei'];if($file['size']<1 || $file['size']>5*1024*1024)throw new DomainException('Belege maximal 5 MB.');
        $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name']);
        if(!in_array($mime,['application/pdf','image/jpeg','image/png','image/webp'],true))throw new DomainException('Nur PDF/JPEG/PNG/WebP erlaubt.');
        $dir=vk_private_dir();$key=bin2hex(random_bytes(32));$dest=$dir.'/'.$key;
        if(!move_uploaded_file($file['tmp_name'],$dest))throw new RuntimeException('Beleg konnte nicht gespeichert werden.');
        chmod($dest,0600);
        $pdo=vf_db();$pdo->beginTransaction();
        try {
            $name=basename((string)$file['name']);$name=vk_text($name,180,true);
            vf_exec('INSERT INTO vs_belege (buchung_id,dateiname,speichername,mime,groesse,sha256,hochgeladen_von) VALUES (?,?,?,?,?,?,?)',[
                $booking,$name,$key,$mime,(int)$file['size'],hash_file('sha256',$dest),(int)$user['mitglieder_id']]);
            $id=(int)$pdo->lastInsertId();vk_audit((int)$user['mitglieder_id'],'beleg.hochgeladen','beleg',$id,['buchung'=>$booking,'sha256'=>hash_file('sha256',$dest)]);
            $pdo->commit();$notice='Beleg erfolgreich gespeichert.';
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();unlink($dest);throw $e;}
    }catch(Throwable $e){error_log('[Vereinssystem Beleg] '.$e->getMessage());$error=$e instanceof DomainException?$e->getMessage():'Belegspeicherung fehlgeschlagen.';}
}
$files=vf_all('SELECT * FROM vs_belege WHERE buchung_id=? ORDER BY id DESC',[$booking]);
$title='Belege zur Kassenbuchung';require __DIR__.'/inc/head.inc.php';
?>
<div id="content"><section class="vf vk"><p><a href="kasse.php">← Kassenbuch</a></p><h1>Belege zur Buchung #<?=$booking?></h1><p><?=vf_escape($entry['beschreibung'])?> · <?=vf_money((int)$entry['betrag_cent'])?> · <?=vf_escape($entry['kontoname'])?></p>
<?php if($error):?><p class="alert error"><?=vf_escape($error)?></p><?php endif;?><?php if($notice):?><p class="alert"><?=vf_escape($notice)?></p><?php endif;?>
<div class="panel"><h2>Datei hochladen</h2><p>Maximal 5 MB. Belege dürfen nicht im Webroot liegen; die Umgebungsvariable <code>VS_PRIVATE_STORAGE_DIR</code> muss einen privaten Serverpfad angeben.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="buchung" value="<?=$booking?>"><input type="file" name="datei" accept=".pdf,.jpg,.jpeg,.png,.webp" required><button>Beleg hochladen</button></form></div>
<div class="panel"><h2>Gespeicherte Belege</h2><ul><?php foreach($files as $file):?><li><a href="kasse_beleg.php?buchung=<?=$booking?>&amp;download=<?=(int)$file['id']?>"><?=vf_escape($file['dateiname'])?></a> · <?=number_format((int)$file['groesse']/1024,1,',','.')?> KB</li><?php endforeach;?></ul><?php if(!$files):?><p>Keine Dateien vorhanden.</p><?php endif;?></div>
</section></div><?php require __DIR__.'/inc/footer.inc.php'; ?>
