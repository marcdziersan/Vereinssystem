<?php
/** Read-only CLI schema inspection; no implicit ALTER, DROP or data writes. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Core\Database;
use Vereinssystem\Install\SchemaInspector;
try {
    $root=dirname(__DIR__);
    $settingsFile=$root.'/config/database.local.php';
    if(!is_file($settingsFile))throw new RuntimeException('Datenbankkonfiguration fehlt.');
    $settings=require $settingsFile;
    if(!is_array($settings))throw new RuntimeException('Datenbankkonfiguration ungültig.');
    $inspector=new SchemaInspector($root);
    $report=$inspector->inspect((new Database($settings))->connection(),true);
    echo 'Geprüfte Tabellen: '.$report['checked'].'/16'.PHP_EOL;
    foreach($report['errors'] as $message)echo '[ABWEICHUNG] '.$message.PHP_EOL;
    if($report['errors']){fwrite(STDERR,"Schemavergleich fehlgeschlagen; keine automatische Änderung.\n");exit(1);}
    echo "Schemakonsistenz erfolgreich geprüft.\n";
} catch(Throwable $e){fwrite(STDERR,'Schema-Prüfung nicht möglich: '.$e->getMessage().PHP_EOL);exit(1);}
