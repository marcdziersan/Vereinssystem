# Quellcode-Richtlinien – Vereinssystem 2.3.1

## Technische Vorgaben

- PHP 8.0+, PDO/MySQL/MariaDB, keine Framework-/Node-/Composer-Pflicht.
- Neue PHP-Dateien: UTF-8 ohne BOM, LF, 4 Leerzeichen, sprechende Variablennamen, `declare(strict_types=1);` wo möglich.
- Neue Funktionen: kurze PHPDoc-Kopfzeile zu Aufgabe, Eingaben, Ausgaben und Seiteneffekten (z. B. Datenbanktransaktion, Mail, Dateischreibzugriff).
- HTML-Ausgabe: `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` oder `vf_escape()`. Nur sicher sanitiertes HTML über `vs_safe_html()` zulassen.
- SQL: keine neuen SQL-Strings mit vom Benutzer abgeleiteten Konkatenationen; `db_query($sql, $params)` oder `vf_one`, `vf_all`, `vf_exec` nutzen.
- Money: Cent als `int`, niemals `float` für Buchungsarithmetik. Domain-Schreibvorgänge in Transaktionen mit Rollback.
- HTTP: mutierende Aufrufe ausschließlich POST mit Session, CSRF und konkreter serverseitiger Berechtigungsprüfung.
- Dateien und E-Mails: private Ablage außerhalb Webroot, keine direkten Benutzerdateinamen als Dateipfade oder ungeprüfte Headerwerte.
- Datenänderungen: keine stillen Löschungen von Zahlungen; Storno als nachvollziehbares Fachereignis.

## Bestandsschutz

Historische PHP-/HTML-Mischdateien von 2013 werden nicht durch einen reinen Whitespace-Formatter mit unkontrollierter Token-Verschiebung neu geschrieben. Das kann SQL-Strings, Inline-JavaScript und HTML-Attribute verändern. Bei Berührung einer alten Maske wird deren SQL-Zugriff, HTML-Ausgabe und Rechteprüfung stattdessen **gezielt** refaktoriert. Neue und geänderte Codeabschnitte werden nachvollziehbar gegliedert. Eine vollständige Umstellung aller 2013er PHP-/HTML-Templates auf PSR-12 erfolgt schrittweise mit visueller Kontrolle.

## Dokumentation

- `README.md`: Vorstellung, Funktionen, Installation, Lizenz (ohne Umgebungstest-Status).
- `CHANGELOG.md`: tatsächliche Änderungen in `Neu / Geändert / Behoben`.
- `docs/ARCHITECTURE.md`: Buchungsregeln, Datenmodell, Sicherheitsgrenzen.
- `docs/SOURCE_REFERENCE.md`: jeder PHP-Einstiegspunkt mit Aufgabe und Funktionsliste.
- `docs/PDO_MIGRATION.md`: historische SQL-Builder und Ablöseplan.
- `docs/PRAXISTEST-2.3.1.md`: praktische Browser-/Datenbankabnahme.