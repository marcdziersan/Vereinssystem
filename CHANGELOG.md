# Änderungsprotokoll

## 2.0.0 – PHP 8 / PDO, 17.10.2026

**Neu**
- `install.php`: geführte Installation mit Datenbankverbindung, acht Tabellen, Grundeinstellungen und initialem Admin.
- `inc/database.php`: native PDO-Verbindung mit vorbereiteten Abfragen und gepuffertem Ergebnisadapter für Altabfragen.
- `inc/session.php`: einheitlicher Session-Start.
- CSRF-Prüfung bei den vorhandenen POST-Formularen und POST-Aktionen für Dienste.

**Geändert**
- Komplettmigration der entfernten `mysql_*`-Aufrufe auf `db_*`/PDO.
- Neuanmeldungen / Passwortänderungen verwenden `password_hash`; MD5-Bestandskonten migrieren beim Login.
- Deutsche Datumsformatierung ohne `strftime`; Textbereinigung ohne `FILTER_SANITIZE_STRING`.
- UTF-8-Dateien, utf8mb4-Datenbankanbindung, lokal eingebundenes jQuery 3.6.1.
- Login, Session-Fixation-Schutz, Captcha und Admin-Zugang für Browser-Cronjob.

**Entfernt**
- Veraltete Blowfish-Klasse, fest eingetragene MySQL-Root-Zugangsdaten, alte Captcha-Testseite.

**Bekannte Grenzen**
- Historische Query-Builder zum Teil weiterhin stringbasiert mit PDO-Quoting; keine vollständige parameterisierte Neufassung aller 190+ SQL-Aufrufe.
- Keine Prüfung aller Abläufe gegen eine echte MySQL-Datenbank in dieser Umgebung.
- Die historische Oberfläche und Datenschemata wurden im Wesentlichen erhalten.
