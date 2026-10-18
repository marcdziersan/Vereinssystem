<?php
/**
 * Vereinssystem 2.3.1 – Nur lesender Datenbank-Selbstcheck für die Praxisinstallation.
 *
 * Verwendung: php tests/praxis_db.php
 * Es werden niemals Tabellen, Buchungen oder Mitglieder angelegt/verändert.
 * Die Ausgabe enthält nur Schema- und Konsistenzkennzahlen.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Dieses Werkzeug darf nur über PHP-CLI gestartet werden.');
}

require_once __DIR__ . '/../inc/database.php';
if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "pdo_mysql fehlt. Bitte in der PHP-CLI-Installation aktivieren.\n");
    exit(2);
}
if (!is_file(__DIR__ . '/../inc/config.php')) {
    fwrite(STDERR, "Noch keine installierte Datenbankkonfiguration.\n");
    exit(2);
}

try {
    $db = db_pdo();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $required = [
        'abteilungen', 'abteilungszugehoerigkeit', 'dienste', 'einstellungen',
        'mitglieder', 'news', 'orte', 'veranstaltungen', 'vs_forderungen',
        'vs_zahlungen', 'vs_zuordnungen', 'vs_konten', 'vs_kassenbuch',
        'vs_belege', 'vs_berechtigungen', 'vs_audit', 'vs_tarife',
        'vs_schema_version',
    ];
    $query = $db->query('SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema=DATABASE()');
    $tables = array_fill_keys($query->fetchAll(PDO::FETCH_COLUMN), true);
    $missing = array_diff($required, array_keys($tables));
    printf("Datenbanktabellen: %d von %d gefunden\n", count($required) - count($missing), count($required));
    if ($missing) {
        fwrite(STDERR, 'Fehlende Tabellen: ' . implode(', ', $missing) . "\n");
        exit(1);
    }

    $checks = [
        'Verwaiste Zahlungszuordnungen' => 'SELECT COUNT(*) FROM vs_zuordnungen a LEFT JOIN vs_zahlungen z ON z.id=a.zahlung_id LEFT JOIN vs_forderungen f ON f.id=a.forderung_id WHERE z.id IS NULL OR f.id IS NULL',
        'Zuordnungen ueber Zahlbetrag' => 'SELECT COUNT(*) FROM (SELECT z.id,z.betrag_cent,COALESCE(SUM(a.betrag_cent),0) as zugeordnet FROM vs_zahlungen z LEFT JOIN vs_zuordnungen a ON a.zahlung_id=z.id GROUP BY z.id,z.betrag_cent HAVING zugeordnet>z.betrag_cent) AS x',
        'Doppelte Kassenverknuepfungen' => 'SELECT COUNT(*) FROM (SELECT zahlung_id FROM vs_kassenbuch WHERE zahlung_id IS NOT NULL GROUP BY zahlung_id HAVING COUNT(*)>1) AS x',
        'Umbuchungen unvollstaendig' => "SELECT COUNT(*) FROM (SELECT umbuchung_key,COUNT(*) n,SUM(CASE WHEN art='umbuchung_ein' THEN betrag_cent ELSE -betrag_cent END) delta FROM vs_kassenbuch WHERE umbuchung_key IS NOT NULL GROUP BY umbuchung_key HAVING n<>2 OR delta<>0) AS x",
        'Aktive Kasse zu stornierter Zahlung' => 'SELECT COUNT(*) FROM vs_kassenbuch b JOIN vs_zahlungen z ON z.id=b.zahlung_id WHERE z.storniert_am IS NOT NULL AND b.storniert_am IS NULL',
    ];
    $failed = false;
    foreach ($checks as $label => $sql) {
        $count = (int)$db->query($sql)->fetchColumn();
        printf("%s: %d%s\n", $label, $count, $count ? ' [PRUEFEN]' : ' [OK]');
        if ($count) $failed = true;
    }
    $version = $db->query('SELECT version FROM vs_schema_version ORDER BY version DESC LIMIT 1')->fetchColumn();
    echo 'Datenbankschema: ' . ($version ?: 'nicht gesetzt') . "\n";
    exit($failed ? 1 : 0);
} catch (Throwable $e) {
    error_log('[Praxischeck] ' . $e->getMessage());
    fwrite(STDERR, "Datenbank-Selbstcheck fehlgeschlagen: Details im PHP-Fehlerprotokoll.\n");
    exit(2);
}
