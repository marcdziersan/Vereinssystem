<?php
/** CLI-only additive finance migration (four master-compatible ledger tables). */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__);
try {
    $config=$root.'/config/database.local.php';
    if(!is_file($config))throw new RuntimeException('Datenbankkonfiguration fehlt.');
    $settings=require $config;
    if(!is_array($settings))throw new RuntimeException('Ungültige Datenbankkonfiguration.');
    $pdo=(new Vereinssystem\Core\Database($settings))->connection();
    $schema=file_get_contents($root.'/database/migrations/005_finance.sql');
    if(!is_string($schema))throw new RuntimeException('Migrationsdatei fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$schema,$matches);
    if(count($matches[0])!==4)throw new RuntimeException('Vier Finanztabellen erwartet.');
    foreach($matches[0] as $statement)$pdo->exec($statement);
    echo "Beitragsverwaltung bereit; vorhandene Finanzbuchungen bleiben erhalten.\n";
}catch(Throwable $e){error_log('[MVC/finance migration] '.$e->getMessage());fwrite(STDERR,"Finanzmigration fehlgeschlagen; Fehlerlog prüfen.\n");exit(1);}
