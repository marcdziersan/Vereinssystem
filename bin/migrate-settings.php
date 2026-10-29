<?php
/** Alpha 10.2 additive settings migration; stops on incompatible existing schema. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Core\Database;
use Vereinssystem\Install\SchemaInspector;
$root=dirname(__DIR__);
try{
    $path=$root.'/config/database.local.php';
    if(!is_file($path))throw new RuntimeException('Datenbankkonfiguration fehlt.');
    $config=require $path;
    if(!is_array($config))throw new RuntimeException('Ungültige Konfiguration.');
    $pdo=(new Database($config))->connection();
    $inspector=new SchemaInspector($root);
    $before=$inspector->inspect($pdo,false);
    if($before['errors'])throw new RuntimeException(SchemaInspector::summary($before['errors']));
    $sql=file_get_contents($root.'/database/migrations/008_system_settings.sql');
    if(!is_string($sql))throw new RuntimeException('Migration fehlt.');
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$sql,$matches);
    if(count($matches[0])!==2)throw new RuntimeException('Zwei Einstellungstabellen erwartet.');
    foreach($matches[0] as $create)$pdo->exec($create);
    $after=$inspector->inspect($pdo,true);
    if($after['errors'])throw new RuntimeException(SchemaInspector::summary($after['errors']));
    echo "Systemeinstellungen und Änderungsprotokoll bereit; vorhandene Vereinsdaten bleiben erhalten.\n";
}catch(Throwable $e){fwrite(STDERR,'Migration fehlgeschlagen: '.$e->getMessage().PHP_EOL);exit(1);}
