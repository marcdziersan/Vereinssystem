<?php
/**
 * Vereinssystem 2.3.0 – Modul: cash smoke.
 * Pfad: tests/cash_smoke.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Datenbankfreie Regressionstests: Parser, Schema, PDF und Quellcodeverknüpfung. */
require_once __DIR__.'/../inc/cash.php';
require_once __DIR__.'/../inc/pdf.php';

$count=0;
function check_cash(bool $value,string $name):void {global $count;$count++;if(!$value)throw new RuntimeException('Prüfung fehlgeschlagen: '.$name);}
function must_fail(callable $fn):bool {try{$fn();return false;}catch(DomainException){return true;}}

foreach (['1'=>'100','15,00'=>'1500','12.5'=>'1250','999999,99'=>'99999999'] as $input=>$expected) {
    check_cash(vk_amount((string)$input)===(int)$expected,'Betrag '.$input);
}
foreach (['-1,50'=>-150,'-20'=>-2000,'24,99'=>2499] as $input=>$cent){
    check_cash(vk_parse_signed((string)$input)===$cent,'Anfangsbestand '.$input);
}
foreach (['0','-1','1,234','1e3','9999999','1.000,00','1,23456'] as $input){
    check_cash(must_fail(static fn()=>vk_amount($input)),'Ungültiger Betrag '.$input);
}
foreach (['2026-02-30','2026-13-01','99-12-31'] as $input) {
    check_cash(must_fail(static fn()=>vf_date($input)),'Ungültiges Datum '.$input);
}
check_cash(vf_date('2026-02-28')==='2026-02-28','Gültiges Datum');
check_cash(vk_text('Kasseneingang',100,true)==='Kasseneingang','Text');
check_cash(must_fail(static fn()=>vk_text('',100,true)),'Pflichttext');
check_cash(count(VK_RECHTE)>=8,'Feingranulare Berechtigungen');
$sql=file_get_contents(__DIR__.'/../sql/schema.sql');
check_cash(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===18,'18 Tabellen');
$migration=file_get_contents(__DIR__.'/../sql/finance_2_3_0.sql');
check_cash(substr_count($migration,'CREATE TABLE IF NOT EXISTS')===7,'7 neue Tabellen');
foreach (['vs_konten','vs_kassenbuch','vs_belege','vs_berechtigungen','vs_audit','vs_tarife','vs_schema_version'] as $table) {
    check_cash(str_contains($migration,'`'.$table.'`'),'Migrations-Tabelle '.$table);
}
$bytes=vk_pdf_bytes('Jahresbericht',['Einnahmen 15,00 EUR','Ausgaben 2,00 EUR']);
check_cash(str_starts_with($bytes,'%PDF-1.4'),'PDF-Signatur');
check_cash(str_contains($bytes,'xref'),'PDF-Xref');
check_cash(str_contains($bytes,'%%EOF'),'PDF-Abschluss');
check_cash(str_contains($bytes,'/Count 1'),'PDF-Seitenzahl');
$long=vk_pdf_bytes('Titel',array_fill(0,180,'Testzeile'));
check_cash(str_contains($long,'/Count 4'),'PDF-Mehrseiten');
foreach (['kasse.php','kasse_process.php','kasse_import.php','kasse_admin.php','kasse_beleg.php','kasse_export.php','kasse_protokoll.php','upgrade.php'] as $file) {
    check_cash(is_file(__DIR__.'/../'.$file),'Routendatei '.$file);
}
echo "OK: $count datenbankfreie Kassen-Smoketests\n";