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
    // Abort on incompatible legacy columns/keys before running additive CREATEs.
    $inspector=new \Vereinssystem\Install\SchemaInspector($root);
    $before=$inspector->inspect($pdo,false);
    if($before['errors'])throw new RuntimeException('Vorhandenes Schema abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($before['errors']));
    $schema=file_get_contents($root.'/database/migrations/005_finance.sql');
    if(!is_string($schema))throw new RuntimeException('Migrationsdatei fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$schema,$matches);
    if(count($matches[0])!==4)throw new RuntimeException('Vier Finanztabellen erwartet.');
    foreach($matches[0] as $statement)$pdo->exec($statement);
    $after=$inspector->inspect($pdo,false);
    if($after['errors'])throw new RuntimeException('Schema nach Migration abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($after['errors']));
    echo "Beitragsverwaltung bereit; vorhandene Finanzbuchungen bleiben erhalten.\n";
}catch(Throwable $e){error_log('[MVC/finance migration] '.$e->getMessage());fwrite(STDERR,"Finanzmigration fehlgeschlagen; Fehlerlog prüfen.\n");exit(1);}
