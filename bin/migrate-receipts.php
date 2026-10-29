<?php
/**
 * CLI-only additive receipt metadata migration. Existing master 2.x rows are preserved.
 * Each bundled CREATE TABLE IF NOT EXISTS statement is executed exactly once.
 */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__);
try{
    $file=$root.'/config/database.local.php';
    if(!is_file($file))throw new RuntimeException('Datenbankkonfiguration fehlt.');
    $settings=require $file;
    if(!is_array($settings))throw new RuntimeException('Ungültige Datenbankkonfiguration.');
    $pdo=(new Vereinssystem\Core\Database($settings))->connection();
    // Abort on incompatible legacy columns/keys before running additive CREATEs.
    $inspector=new \Vereinssystem\Install\SchemaInspector($root);
    $before=$inspector->inspect($pdo,false);
    if($before['errors'])throw new RuntimeException('Vorhandenes Schema abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($before['errors']));
    $schema=file_get_contents($root.'/database/migrations/007_cash_receipts.sql');
    if(!is_string($schema))throw new RuntimeException('Belegschema fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$schema,$m);
    if(count($m[0])!==1)throw new RuntimeException('Eine Belegtabelle erwartet.');
    foreach($m[0] as $statement)$pdo->exec($statement);
    $after=$inspector->inspect($pdo,false);
    if($after['errors'])throw new RuntimeException('Schema nach Migration abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($after['errors']));
    echo "Belegverwaltung bereit; vorhandene Buchungen und Belege bleiben erhalten.\n";
}catch(Throwable $e){error_log('[MVC/receipt migration] '.$e->getMessage());fwrite(STDERR,"Belegmigration fehlgeschlagen. Fehlerlog prüfen.\n");exit(1);}
