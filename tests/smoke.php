<?php
/** PHP CLI: php tests/smoke.php – benötigt keine Datenbankverbindung. */
declare(strict_types=1);
require_once __DIR__.'/../inc/validation.php';
require_once __DIR__.'/../inc/database.php';
$checks = 0;
function ok(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) {fwrite(STDERR, "FEHLER: $message\n"); exit(1);}
}
ok(vs_valid_choice('0',0,2), 'Mitgliedschaft 0');
ok(vs_valid_choice('2',0,2), 'Mitgliedschaft 2');
ok(!vs_valid_choice('3',0,2), 'Mitgliedschaft 3 muss fehlschlagen');
ok(!vs_valid_choice('-1',0,2), 'Mitgliedschaft -1 muss fehlschlagen');
ok(!vs_valid_choice('1.5',0,2), 'Dezimalzahl muss fehlschlagen');
ok(!vs_valid_choice('abc',0,2), 'Text muss fehlschlagen');
ok(!vs_valid_choice([],0,2), 'Array muss fehlschlagen');
ok(!vs_valid_choice('2abc',0,2), 'Gemischter Wert muss fehlschlagen');
ok(vs_valid_choice(5,0,5) && !vs_valid_choice(6,0,5), 'Rechtelevel');
$r = new VSResult([['id'=>1, 'name'=>'Test'],['id'=>2,'name'=>'Demo']]);
ok(db_num_rows($r)===2,'Ergebnisanzahl');
ok(db_fetch_assoc($r)['name']==='Test','Assoc 1');
ok(db_fetch_row($r)===[2,'Demo'],'Assoc -> Row');
ok(db_data_seek($r,0),'Seek');
ok(db_fetch_assoc($r)['id']===1,'Seek -> Assoc');
ok(vs_sanitize_text('<b>Text</b>')==='Text','Textbereinigung');
$schema = file_get_contents(__DIR__.'/../sql/schema.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=.*?;/si', $schema, $matches);
ok(count($matches[0])===18,'18 installierbare Tabellen');
echo "OK: $checks Smoke-Checks (ohne Datenbank)\n";