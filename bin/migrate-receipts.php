<?php
/** Repeatable additive alpha.9 migration. Run once after deploying the new files. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__);
try{
    if(!is_file($root.'/config/database.local.php'))throw new \RuntimeException('Datenbankkonfiguration fehlt.');
    $settings=require $root.'/config/database.local.php';
    if(!is_array($settings))throw new \RuntimeException('Ungültige Konfiguration.');
    $pdo=(new \Vereinssystem\Core\Database($settings))->connection();
    $sql=file_get_contents($root.'/database/migrations/007_receipts.sql');
    if(!is_string($sql)||!preg_match('/CREATE TABLE IF NOT EXISTS.*?ENGINE=InnoDB[^;]*;/si',$sql,$matches))
        throw new \RuntimeException('Belegschema fehlt.');
    $pdo->exec($matches[0]);
    echo "Belegverwaltung aktiviert. Bestehende Kassenbuchungen unverändert.\n";
}catch(\Throwable $e){error_log('[MVC/receipt migration] '.$e->getMessage());fwrite(STDERR,"Belegmigration fehlgeschlagen. Fehlerlog prüfen.\n");exit(1);}
