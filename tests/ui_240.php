<?php
/** Statische UI-Vertragsprüfungen ohne Datenbankzugriff. */
declare(strict_types=1);
$base = dirname(__DIR__);
$checks = 0;
function ui_assert(bool $value, string $title): void {
    global $checks;
    if (!$value) { fwrite(STDERR, "FEHLER: {$title}\n"); exit(1); }
    ++$checks;
}
$head = file_get_contents($base.'/inc/head.inc.php');
$css = file_get_contents($base.'/css/app.css');
$js = file_get_contents($base.'/assets/js/app.js');
$login = file_get_contents($base.'/login.php');
ui_assert(str_contains($head,'<meta name="viewport"'), 'Viewport in allen Seiten');
ui_assert(str_contains($head,'css/app.css?v=2.4.0'), 'Zentrales CSS mit Cacheversion');
ui_assert(str_contains($head,'assets/js/app.js?v=2.4.0'), 'Zentrales JS');
ui_assert(str_contains($head,'$appendjs'), 'Historische jQuery-Eventhandler');
ui_assert(str_contains($head,'data-vs-menu'), 'Mobiles Menü');
ui_assert(str_contains($head,'vs-mobile-tabs'), 'Mobile Schnellnavigation');
ui_assert(str_contains($head,'vui_navlink'), 'Rechteabhängige Seitenleiste');
ui_assert(str_contains($head,'vk_can'), 'Kassenrechte erhalten');
ui_assert(str_contains($login,'name="vs_login_token"'), 'Login-Token erhalten');
ui_assert(str_contains($login,'name="psword"'), 'Passwortfeld erhalten');
ui_assert(str_contains($login,'name="after"'), 'Redirect-Parameter erhalten');
ui_assert(str_contains($login,'name="captcha"'), 'CAPTCHA-Feld vorhanden');
ui_assert(str_contains($js,'dataset.vsLabel'), 'Mobile Tabellenbeschriftung');
ui_assert(str_contains($js,"event.key === 'Escape'"), 'Escape-Support');
ui_assert(str_contains($js,'aria-expanded'), 'Menüzustand für Assistenztechnik');
ui_assert(str_contains($css,'@media(min-width:1100px)'), 'Desktop-Breakpoint');
ui_assert(str_contains($css,'prefers-reduced-motion'), 'Reduzierte Bewegung');
ui_assert(str_contains($css,'env(safe-area-inset-bottom)'), 'Smartphone Safe-Area');
ui_assert(str_contains(file_get_contents($base.'/install.php'),'css/setup.css?v=2.4.0'), 'Setup-Design');
ui_assert(str_contains(file_get_contents($base.'/upgrade.php'),'css/setup.css?v=2.4.0'), 'Upgrade-Design');
$pages = glob($base.'/*.php') ?: [];
$views = 0;
foreach ($pages as $file) {
    $code = file_get_contents($file);
    if (str_contains($code, 'head.inc.php')) { $views++; ui_assert(str_contains($code,'footer.inc.php') || str_contains($file,'table.php'), basename($file).' Seitenabschluss'); }
}
ui_assert($views >= 35, 'Mindestens 35 Seiten am UI-Layout');
echo "OK: {$checks} Prüfungen, {$views} Ansichten unter gemeinsamer UI.\n";