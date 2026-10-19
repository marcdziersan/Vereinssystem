<?php
/** CLI-Migration für Veranstaltungen und frei definierbare Aufgaben. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__);$file=$root.'/config/database.local.php';
if(!is_file($file)){fwrite(STDERR,"Erst config/database.local.php anlegen.\n");exit(2);}
try{
    $settings=require $file;
    if(!is_array($settings))throw new RuntimeException('Ungültige Datenbankkonfiguration.');
    $db=(new Vereinssystem\Core\Database($settings))->connection();
    $schema=file_get_contents($root.'/database/migrations/003_events_tasks.sql');
    if(!is_string($schema))throw new RuntimeException('Migration fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$schema,$statements);
    if(count($statements[0])!==3)throw new RuntimeException('Drei Tabellen erwartet.');
    foreach($statements[0] as $statement)$db->exec($statement);
    fwrite(STDOUT,"Veranstaltungen und flexible Aufgabenplanung bereit. Vorhandene Daten bleiben unverändert.\n");
}catch(Throwable $e){error_log('[MVC/event-migration] '.$e->getMessage());fwrite(STDERR,"Migration fehlgeschlagen (PHP-Fehlerlog prüfen).\n");exit(1);}
