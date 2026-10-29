# Alpha.9 – Konsistenz und technische Abnahme

**Projekt:** Vereinssystem MVC 3.0.0-alpha.9  
**Branch:** `refactor/mvc-architecture`  
**Referenz:** `master` bleibt auf 2.4.0 unverändert.

## Implementierter Umfang

Die Anwendung umfasst alle Module der vorherigen MVC-Alphas sowie die Kassenberichte,
CSV-/PDF-Exporte und privaten Kassenbelege. Das Belegschema liegt unter
`database/migrations/007_cash_receipts.sql`. Eine Neuinstallation erstellt 16 Tabellen.
Ein Update von alpha.8 verwendet `php bin/migrate-receipts.php`.

## Nachvollziehbare Prüfungen

Die Quellcode-Pakete alpha.1 bis alpha.9 enthalten neun lokale Prüfprogramme.
Sie umfassen Kernel, Authentifizierung, Mitglieder, Orte/Abteilungen, Veranstaltungen,
Nachrichten, Finanzen, Kasse sowie Belege und Berichte. Die aktuellen Prüfprogramme
umfassen zusammen 369 Assertions. Die PHP-Dateien werden zusätzlich per `php -l`
auf syntaktische Gültigkeit kontrolliert.

WAMP/MySQL-Praxistests bleiben für den produktiven Einsatz erforderlich. Die
Prüfprogramme verwenden stellenweise simulierte Datenbank-Repositories und können
InnoDB-Transaktions- und Fremdschlüsselverhalten nicht vollständig ersetzen.

## Noch offene technische Arbeiten

1. Das vorhandene SQL-Schema aus 2.4.0 mit realen Daten prüfen: `CREATE TABLE IF NOT EXISTS`
   allein erkennt Unterschiede an bereits vorhandenen Spalten und Indizes nicht.
2. Beiträge, Kasse, Stornos, Mehrfachbuchungen und Rollbacks mit MySQL/MariaDB/InnoDB
   sowie parallelen Anfragen praktisch abnehmen.
3. Die privaten Belegdateien gemeinsam mit der Datenbank sichern; eine reine
   SQL-Sicherung enthält die Dateien nicht.
4. Geschützte Uploads und Downloads einschließlich direktem Zugriff auf `storage/`
   auf dem tatsächlichen WAMP-/Apache-Host verifizieren.
5. Das Routing älterer MVC-Controller langfristig an die gemeinsame Middleware
   angleichen; neue Kassen-, Bericht- und Beleg-Routen verwenden sie bereits.
6. Die jQuery-Distribution und die Lizenznotiz zwischen älteren Release-Dateien und
   dem GitHub-Branch vereinheitlichen; die PHP-MVC-Anwendung selbst benötigt jQuery
   für die neu implementierten Berichts- und Belegabläufe nicht.

## Grenzen von alpha.9

Keine automatische Bankabstimmung, kein festgeschriebener Jahresabschluss,
keine automatisierte Sicherung/Wiederherstellung, kein vollständiger Ersatz aller
historischen System-/E-Mail-Einstellungen und kein produktiver Release Candidate.
Ein späteres Storno verändert die aktuelle Berechnung historischer Jahresberichte.

Weitere Schritte werden einzeln und auf dem MVC-Branch durchgeführt; `master` wird
nicht überschrieben. Installations- und Upgradehinweise stehen in `README.md` und
`docs/REPORTS_RECEIPTS.md`.
