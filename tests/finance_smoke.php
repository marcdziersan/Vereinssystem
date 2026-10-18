<?php
/** Unabhängige Prüfungen ohne MySQL-Verbindung. Live-DB-Integration gesondert prüfen. */
declare(strict_types=1);
require_once __DIR__.'/../inc/database.php';
require_once __DIR__.'/../inc/finance.php';
$tests=0;
function verify(bool $ok,string $why): void {global $tests;$tests++;if(!$ok)throw new RuntimeException('FEHLER: '.$why);}
function rejection(callable $fn,string $msg): void {try{$fn();throw new RuntimeException('Akzeptierte ungültige Eingabe: '.$msg);}catch(DomainException $e) {}}
foreach(['1'=>100,'1,5'=>150,'0,01'=>1,'10.99'=>1099,'1000000,00'=>100000000,'9999'=>999900,'004'=>null] as $value=>$c){
 if($c===null){rejection(fn()=>vf_parse_amount((string)$value),'führende Nullen');$tests++;}
 else verify(vf_parse_amount((string)$value)===$c,'Betrag '.$value);
}
foreach(['-1','0','0,00','0,001','1.234,56','1e2','1 000,00','nan','','1000000,01'] as $bad){rejection(fn()=>vf_parse_amount($bad),'Betrag '.$bad);$tests++;}
verify(vf_money(123456)==='1.234,56 €','deutsche Betragsschreibweise');
foreach(['2026-01','2026-12','2099-02'] as $date){verify(vf_period($date)===$date,'Monat '.$date);}
foreach(['2026-00','2026-13','2026-1','1999-12','2026-02-03'] as $date){rejection(fn()=>vf_period($date),'Monat '.$date);$tests++;}
foreach(['2026-02-29','2026-13-04','2026-01-00'] as $date){rejection(fn()=>vf_date($date),'Datum '.$date);$tests++;}
verify(vf_date('2024-02-29')==='2024-02-29','Schaltjahr');
$schema=file_get_contents(__DIR__.'/../sql/schema.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`([^`]+)`\s*\(.*?\)\s*ENGINE=.*?;/si',$schema,$tables);
verify(count($tables[0])===11,'11 Tabellen');
foreach(['vs_forderungen','vs_zahlungen','vs_zuordnungen'] as $name){verify(in_array($name,$tables[1],true),'Tabelle '.$name);}
$upgrade=file_get_contents(__DIR__.'/../sql/finance_2_1_0.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[a-z0-9_]+`\s*\(.*?\)\s*ENGINE=InnoDB.*?;/si',$upgrade,$m);
verify(count($m[0])===3,'Migration 3 Tabellen');
echo "OK: $tests Finanz-Smoke-Checks (ohne Datenbank)\n";