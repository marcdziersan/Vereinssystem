<?php
/**
 * Vereinssystem 2.3.1 – Modul: upgrade.
 * Pfad: upgrade.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/**
 * Versionierte Schema-Erweiterung von 2.0.x/2.1.x auf 2.3.1.
 * Bestehende Vereins- und Zahlungsdatensätze werden nicht gelöscht.
 * MySQL-DDL autocommittet: vorab Backup anlegen und bei Fehlern erneut ausführen.
 */
require_once __DIR__ . '/inc/connect.inc.php';
require_once __DIR__ . '/inc/checkuser.inc.php';
require_once __DIR__ . '/inc/cash.php';
if ((int)$user['rights'] < 5) {http_response_code(403);exit('Nur für Administratoren.');}

$status='';$error='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
    try {
        foreach (['finance_2_1_0.sql'=>3,'finance_2_3_0.sql'=>7] as $file=>$expect) {
            $path=__DIR__.'/sql/'.$file;
            $schema=file_get_contents($path);
            if($schema===false)throw new RuntimeException('Migrationsdatei fehlt: '.$file);
            preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[a-z0-9_]+`\s*\(.*?\)\s*ENGINE=InnoDB.*?;/si',$schema,$statements);
            if(count($statements[0])!==$expect)throw new RuntimeException('Migrationsschema unvollständig: '.$file);
            foreach($statements[0] as $statement)vf_db()->exec($statement);
        }
        if(!vk_ready())throw new RuntimeException('Prüfung der neu angelegten Tabellen fehlgeschlagen.');
        vf_exec('INSERT INTO vs_schema_version (version) VALUES (?) ON DUPLICATE KEY UPDATE version=VALUES(version)',['2.3.1']);
        vf_exec('INSERT INTO einstellungen (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',['version','2.3.1']);
        vk_audit((int)$user['mitglieder_id'],'system.migration','schema',0,['version'=>'2.3.1']);
        $status='Migration erfolgreich: alle 18 Tabellen vorhanden. Vorhandene Daten blieben erhalten.';
    }catch(Throwable $e){error_log('[VS Upgrade 2.3] '.$e->getMessage());$error='Migration gescheitert. Bitte PHP-Fehlerprotokoll prüfen; nach Behebung erneut ausführen.';}
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vereinssystem 2.3.1 – Upgrade</title>
<style>body{font-family:system-ui,Arial,sans-serif;background:#edf1f7;color:#1b2939;padding:2rem}main{max-width:770px;margin:auto;padding:2rem;background:#fff;border:1px solid #cbd5e1;border-radius:12px}p{line-height:1.6}button{background:#15568d;color:white;border:0;border-radius:6px;padding:12px 18px;cursor:pointer}.ok{color:green}.error{color:#a22}</style></head><body><main>
<h1>Update auf Vereinssystem 2.3.1</h1><p>Ergänzt bei Bedarf drei Tabellen aus 2.1.0 und sieben Tabellen aus 2.3.1. Bestehende Mitglieder, Forderungen und Zahlungen werden nicht gelöscht. Die Migration ist wiederholbar.</p>
<p><strong>Vorher vollständige Datenbank- und Dateisicherung anlegen!</strong></p>
<p>Status: <strong><?=vk_ready()?'Kassenverwaltung installiert':'Kassenverwaltung noch nicht vollständig installiert'?></strong></p>
<?php if($status):?><p class="ok"><?=vf_escape($status)?></p><?php endif;?>
<?php if($error):?><p class="error"><?=vf_escape($error)?></p><?php endif;?>
<form method="post"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><button onclick="return confirm('Backup vorhanden? Neue Datenbanktabellen anlegen?')">Datenbankschema installieren / prüfen</button></form>
<p><a href="kasse.php">Zur Kassenverwaltung</a> · <a href="finanzen.php">Zu den Mitgliedsbeiträgen</a></p>
<p><strong>Nach erfolgreicher Migration upgrade.php vom öffentlich erreichbaren Webspace entfernen.</strong> Neue Finanzkonten müssen einmalig angelegt werden; bestehende Zahlungen werden absichtlich nicht fälschlich einem Konto zugewiesen.</p>
</main></body></html>