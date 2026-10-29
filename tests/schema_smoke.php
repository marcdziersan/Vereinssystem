<?php
/** Static migration specification checks, no database required. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Install\SchemaInspector;
$expected=(new SchemaInspector(dirname(__DIR__)))->expected();
if(count($expected)!==18)throw new RuntimeException('16 Tabellen erwartet');
foreach(['mitglieder','vs_konten','vs_kassenbuch','vs_belege','vs_audit','vs_system_settings','vs_system_audit'] as $name){
    if(!isset($expected[$name]))throw new RuntimeException('Fehlende Tabelle '.$name);
    if(!isset($expected[$name]['indexes']['PRIMARY']))throw new RuntimeException('Primary key fehlt '.$name);
}
echo "PASS: 18 migration table declarations, essential keys present.\n";
