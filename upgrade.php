<?php
/** Einmalige, wiederholbare Migration bestehender Installationen von 2.0.x nach 2.1.0. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/finance.php';
if((int)$user['rights']<5){http_response_code(403);exit('Nur für Administratoren.');}
$result='';$failure='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 try{
   $file=__DIR__.'/sql/finance_2_1_0.sql';
   $schema=file_get_contents($file);
   if($schema===false)throw new RuntimeException('Migrationsdatei fehlt.');
   preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[a-z0-9_]+`\s*\(.*?\)\s*ENGINE=InnoDB.*?;/si',$schema,$statements);
   if(count($statements[0])!==3)throw new RuntimeException('Unerwarteter Migrationsinhalt.');
   foreach($statements[0] as $sql)vf_db()->exec($sql);
   if(!vf_ready())throw new RuntimeException('Tabellen konnten nicht vollständig angelegt werden.');
   vf_exec('INSERT INTO einstellungen (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',['version','2.1.0']);
   $result='Die drei Finanz-Tabellen sind bereit. Vorhandene Mitgliederdaten wurden nicht verändert.';
 }catch(Throwable $e){error_log('[VS Upgrade] '.$e->getMessage());$failure='Migration fehlgeschlagen. Details im PHP-Fehlerprotokoll.';}
}
function uh(mixed $x): string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vereinssystem 2.1.0 – Upgrade</title>
<style>body{font-family:system-ui,Arial,sans-serif;background:#edf1f7;color:#18293b;padding:20px}main{max-width:700px;background:white;padding:25px;margin:auto;border-radius:12px}button{background:#15568d;border:0;color:white;padding:12px 20px;border-radius:6px;cursor:pointer}p{line-height:1.6}.alert{padding:12px;background:#f2f4f8}</style></head><body><main><h1>Update auf 2.1.0</h1>
<p>Ergänzt ausschließlich <code>vs_forderungen</code>, <code>vs_zahlungen</code> und <code>vs_zuordnungen</code>. Keine bestehenden Buchungen oder Mitgliederdaten werden gelöscht.</p>
<p><strong>Vorher bitte vollständiges Datenbankbackup erstellen.</strong> MySQL-DDL ist nicht vollständig transaktional; ein fehlgeschlagener Schritt muss ggf. wiederholt werden.</p>
<p>Aktueller Zustand: <strong><?= vf_ready()?'Finanztabellen vorhanden':'Finanztabellen fehlen' ?></strong></p>
<?php if($result): ?><p class="alert"><?=uh($result)?></p><?php endif; ?>
<?php if($failure): ?><p class="alert"><?=uh($failure)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="vs_csrf" value="<?=uh($_SESSION['vs_csrf'])?>"><button type="submit">Datenbankschema installieren / prüfen</button></form>
<p><a href="finanzen.php">Zur Beitragsverwaltung</a></p>
<p>Nach erfolgreichem Update <code>upgrade.php</code> vom öffentlichen Server entfernen.</p>
</main></body></html>