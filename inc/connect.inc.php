<?php
/** Gemeinsamer Einstieg für sämtliche historischen Module. */
if (!defined('VS_INTERNAL')) define('VS_INTERNAL', true);
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
if (!is_file(__DIR__ . '/config.php')) {
    if (PHP_SAPI !== 'cli') { header('Location: install.php'); exit; }
    throw new RuntimeException('Bitte install.php im Browser aufrufen.');
}
try {
    $settings = [
        'vereinsname'=>'Mein Verein', 'vereinsname_lang'=>'Mein Verein', 'vereinsanschrift'=>'0',
        'usecaptchalogin'=>'0', 'beitrag_kinder'=>'0', 'beitrag_erwachsener'=>'0',
        'annualwork'=>'0', 'cakeworkcount'=>'0', 'lastcronrun'=>'0',
        'last_diensteabrechnung'=>'0', 'lastcronoutput'=>'', 'webmastermail'=>'',
        'leitermail'=>'', 'version'=>'2.3.1',
    ];
    $result = db_query('SELECT `name`, `value` FROM `einstellungen`');
    if ($result === false) throw new RuntimeException('Datenbanktabelle einstellungen fehlt.');
    while ($setting = db_fetch_assoc($result)) $settings[$setting['name']] = $setting['value'];
    $settings['version'] = '2.3.1';
} catch (Throwable $e) {
    error_log('[Vereinssystem] Datenbankinitialisierung: '.$e->getMessage());
    http_response_code(503);
    exit('Die Datenbank ist nicht erreichbar oder unvollständig eingerichtet. Bitte prüfen Sie die Datenbankkonfiguration.');
}
