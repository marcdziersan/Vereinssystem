<?php
/** Add places/departments tables to an existing alpha.3 installation (CLI only). */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI');}
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__);
$file=$root.'/config/database.local.php';
if(!is_file($file)){fwrite(STDERR,"Erst config/database.local.php anlegen.\n");exit(2);}
try{
    $config=require $file;
    if(!is_array($config))throw new RuntimeException('Konfiguration fehlt.');
    $db=(new Vereinssystem\Core\Database($config))->connection();
    // Abort on incompatible legacy columns/keys before running additive CREATEs.
    $inspector=new \Vereinssystem\Install\SchemaInspector($root);
    $before=$inspector->inspect($db,false);
    if($before['errors'])throw new RuntimeException('Vorhandenes Schema abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($before['errors']));
    $sql=file_get_contents($root.'/database/migrations/002_departments_places.sql');
    if(!is_string($sql))throw new RuntimeException('Migration fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$sql,$matches);
    if(count($matches[0])!==3)throw new RuntimeException('Migration ist unvollständig.');
    foreach($matches[0] as $statement)$db->exec($statement);
    $after=$inspector->inspect($db,false);
    if($after['errors'])throw new RuntimeException('Schema nach Migration abweichend: '.\Vereinssystem\Install\SchemaInspector::summary($after['errors']));
    fwrite(STDOUT,"Abteilungen, Zuordnungen und Orte sind bereit. Bestehende Daten blieben erhalten.\n");
}catch(Throwable $e){error_log('[MVC schema] '.$e->getMessage());fwrite(STDERR,"Migration fehlgeschlagen; PHP-Fehlerlog prüfen.\n");exit(1);}
