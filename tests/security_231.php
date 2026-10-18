<?php
/** CLI-Sicherheitsszenarien 2.3.1; ohne Datenbank und ohne Vereinsdaten. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/inc/security.php';

$passed = 0;
function expect231(bool $ok, string $message): void
{
    global $passed;
    if (!$ok) {
        fwrite(STDERR, "FEHLER: {$message}\n");
        exit(1);
    }
    $passed++;
}

expect231(!vs_safe_link('javascript:alert(1)'), 'JavaScript-Links blockieren');
expect231(!vs_safe_link('data:text/html,abc'), 'Data-Links blockieren');
expect231(!vs_safe_link('//evil.invalid'), 'Externe schematische Links blockieren');
expect231(!vs_safe_link('../private.php'), 'Pfadwechsel blockieren');
expect231(vs_safe_link('https://example.org/a'), 'HTTPS-Links zulassen');
expect231(vs_safe_link('member_show.php?id=7'), 'Interne Links zulassen');
expect231(vs_safe_link('/verein/news.php'), 'App-interne absolute Pfade zulassen');

$unsafe = vs_safe_html('<img src="x" onerror="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)" onclick="run()">Link</a>');
expect231(!str_contains($unsafe, 'onerror'), 'Keine Event-Attribute an Bildern');
expect231(!str_contains($unsafe, 'onclick'), 'Keine Event-Attribute an Links');
expect231(!str_contains($unsafe, '<script'), 'Kein script-Tag');
expect231(!str_contains($unsafe, 'href="javascript:'), 'Kein javascript-Href');
expect231(str_contains($unsafe, 'Link'), 'Legitimer Text bleibt erhalten');
expect231(!str_contains(vs_mail_header_value("Club\r\nBcc: secret@example.org"), "\n"), 'Kein E-Mail-Header-Zeilenumbruch');

$src = file_get_contents(dirname(__DIR__).'/veranstaltungen_process.php');
expect231(!str_contains((string)$src, 'stripslashes(db_escape('), 'Keine Entfernung des SQL-Escapings');
$news = file_get_contents(dirname(__DIR__).'/news.php');
expect231(!str_contains((string)$news, '<td colspan=\\"5\\">$sql'), 'Keine SQL-Debugausgabe');

printf("OK: %d Sicherheitsprüfungen\n", $passed);
