# Vereinssystem 2.0.1 – PHP 8 / PDO

Aktualisierung des ursprünglichen **Vereinssystems (2013)** von Nicolas Jourdan und Sebastian Fuss. Die ursprünglichen Verwaltungsmasken und Module sind enthalten: Mitglieder, Abteilungen und Zugehörigkeiten, Anschriften/Orte, Veranstaltungen, Dienste, Nachrichten, Einstellungen, Rechte und Erinnerungs-E-Mails.

**Wichtig zum Nutzungsrecht:** Laut mitgeliefertem Original-README darf das ursprüngliche Projekt **kostenlos zu nichtkommerziellen Zwecken** verwendet und erweitert werden. Die Umstellung auf PHP 8/PDO hebt diese Bedingung nicht auf. Alle Rechte am Original verbleiben bei den jeweiligen Urhebern.

## Voraussetzungen

- PHP **8.0 oder neuer** (PHP 8.2–8.4 empfohlen)
- PHP-Erweiterungen **PDO + pdo_mysql**; `mbstring` empfohlen, `gd` nur bei aktiviertem Login-Captcha erforderlich
- MySQL 5.7+/8.x oder kompatible MariaDB-Installation mit `utf8mb4`
- Bestehende, leere MySQL-Datenbank und zugehöriger Datenbankbenutzer (vorher beim Hoster anlegen)
- PHP darf während der Installation `inc/config.php` schreiben; danach Berechtigung einschränken
- Für personenbezogene Daten **HTTPS verpflichtend im praktischen Betrieb**

## Neue Installation

1. ZIP entpacken; den **Inhalt** des Ordners `Vereinssystem-PHP8-PDO-2.0.1/` auf den Webspace laden.
2. In Hosting-Verwaltung eine **leere MySQL/MariaDB-Datenbank** samt Datenbanknutzer anlegen.
3. Im Browser `https://Ihre-Adresse/vereinssystem/install.php` öffnen.
4. Datenbankhost, Port, Datenbankname, Benutzer und Passwort angeben. Anschließend Vereinsname und Admin-Konto anlegen (mit tatsächlichem Geburtsdatum, Passwort mindestens 12 Zeichen).
5. Auf **Installieren** klicken. Der Assistent erstellt acht Tabellen sowie die Grundeinstellungen. Beim leeren Mitgliederbestand erstellt er ein Admin-Konto mit Rechtelevel 5.
6. Mit `login.php` anmelden und in den Einstellungen die Vereinsanschrift, E-Mail-Adressen, Beiträge und Dienste-Regeln prüfen.
7. Nach erfolgreicher Installation **`install.php` vom Server löschen**. Eine erneute Ausführung ist außerdem blockiert, sobald `inc/config.php` vorhanden ist.

Es ist kein manueller phpMyAdmin-Import mehr erforderlich. `sql/schema.sql` bleibt als dokumentierter Datenbankschema-Stand enthalten.

## Update einer bestehenden Datenbank (2013)

**Vorher vollständiges Datenbank- und Datei-Backup anlegen.**

1. Neue PHP-Dateien in ein **getrenntes Verzeichnis** hochladen (nicht blind den laufenden Originalbestand überschreiben).
2. Im Installer die Zugangsdaten **der bestehenden Vereinssystem-Datenbank** eingeben.
3. `install.php` erkennt vorhandene Mitglieder, erhält sie und erstellt **keinen zweiten Administrator**. Fehlende Tabellen und Grundeinstellungen werden ergänzt; vorhandene Datensätze nicht gelöscht.
4. Mit dem bisherigen Nutzernamen und Passwort anmelden. Historische 32-stellige MD5-Passwort-Hashes werden **nur nach erfolgreicher Anmeldung** in aktuelle `password_hash()`-Hashes umgewandelt.
5. Funktionen und Berechtigungen mit einer **Kopie produktiver Daten** prüfen, erst danach auf die neue Version wechseln.

Der Installer ist **keine vollständige Schema-Migrationsengine** und führt keine Struktur-Änderungen an bereits vorhandenen Tabellen durch. Bei individuell geänderten Datenbankschemata können manuelle Anpassungen nötig sein. Alte Bankdatenfelder bleiben aus Kompatibilitätsgründen unverändert.

## Umstellung auf PDO

- Sämtliche früheren `mysql_*`-Funktionsaufrufe wurden aus dem PHP-Code entfernt.
- Kern: `inc/database.php` mit `PDO`, `utf8mb4`, Exceptions und nativen Prepared Statements.
- Die bisherigen Module nutzen `db_query`, `db_fetch_assoc`, `db_num_rows`, `db_data_seek`, `db_insert_id` usw. als **PDO-basierte Übergangsschicht**, damit ältere Verwaltungsseiten funktionsfähig bleiben.
- Sicherheitskritische neue Zugriffe (Login, Benutzerprüfung, Passwortwechsel und Installer) nutzen parametrisierte PDO-Abfragen bzw. `password_hash`.
- Viele historische, dynamisch zusammengesetzte SQL-Statements verwenden aus Kompatibilitätsgründen noch `db_escape`/PDO-Quoting. **Noch nicht alle Bestandsabfragen wurden vollständig in `?`-Parameter umgeschrieben**.
- Um die ursprünglichen MySQL-5-Abfragen zu erhalten, verwendet die PDO-Verbindung sitzungsbezogen `sql_mode = NO_ENGINE_SUBSTITUTION` (u.a. ohne `ONLY_FULL_GROUP_BY` und strikten Einfügemodus). Dies ist ein **bewusster Kompatibilitätsmodus**, keine Empfehlung für neu geschriebene Datenbanklogik.

## Technische Änderungen

- Installationsassistent `install.php` mit automatischer Tabellenerstellung, Grundeinstellungen und Admin-Erstellung.
- Session-Konfiguration korrigiert: `HttpOnly`, `SameSite=Lax`, `Secure` bei HTTPS und Session-ID-Wechsel beim Login.
- CSRF-Prüfung für die vorhandenen POST-Speicherformulare; Dienste hinzufügen/entfernen über POST statt mutierendem GET.
- Login mit `password_verify`/`password_hash`, MD5-Umstellung für bestehende Mitglieder, einfache Sitzungssperre bei vielen Fehlversuchen.
- CAPTCHA ohne veraltete Blowfish-Eigenimplementierung; standardmäßig aus, bei Bedarf in den Einstellungen aktivieren (`gd` notwendig).
- `strftime()` durch deutsche Datumsformatierung ersetzt; veraltetes `FILTER_SANITIZE_STRING` durch eine interne Textbereinigung ersetzt.
- Zeichensatz des PHP-Codes auf UTF-8 vereinheitlicht, Datenbankverbindung mit utf8mb4.
- jQuery **3.6.1 lokal mitgeliefert** statt unsicherem HTTP-CDN; Lizenzhinweis im `assets/js`-Ordner.
- Der `cronjob.php` ist im Browser nur nach Admin-Anmeldung erreichbar, CLI-Aufruf bleibt möglich.

## Cronjob / Mailversand

Für den automatischen Lauf (z.B. täglich) den Hoster-Cron auf die PHP-CLI-Datei `cronjob.php` im installierten Projekt richten. Der Cronjob sendet unter Umständen echte E-Mails, deshalb **erst mit Testdaten bzw. Testadressen prüfen** und die Einstellungen vorher vervollständigen. Keine E-Mail-Zustellung wurde in dieser Testumgebung nachgestellt.

## Datenschutz und Grenzen

Dies ist eine **umfangreiche Migration einer alten Anwendung**, keine neu entwickelte, abschließend sicherheitsgeprüfte Vereinsplattform. Besonders bei echten Mitgliederdaten (einschließlich Kinder- und Bankdaten) sind eine Datenschutz-/Berechtigungsprüfung, sichere Server-Konfiguration, Backups und vollständige Browser-Funktionstests mit echter MySQL-/MariaDB-Instanz erforderlich. Die historischen Felder für Kontonummer und BLZ sind keine aktuelle SEPA-Datenmodellierung.

Die umfangreichen alten Masken sind funktional übernommen, aber nicht zu einer neuen responsiven Oberfläche umgebaut. Historische Google Static Maps können wegen geänderter API-Anforderungen keine Karte mehr anzeigen. **Nicht ohne Abnahme auf einem öffentlich erreichbaren Produktivsystem einsetzen.**

## Verzeichnisübersicht

- `install.php` – Installation
- `inc/connect.inc.php` – zentraler Start + Einstellungen
- `inc/database.php` – PDO-Datenbankzugriff, Übergangsfunktionen
- `inc/session.php` – Sessions
- `inc/checkuser.inc.php` – Login- und CSRF-Prüfung
- `inc/config.php` – **erst nach Installation automatisch erzeugt, nicht im ZIP**
- `sql/schema.sql` – Tabellenstruktur
- `assets/js/` – lokal mitgeliefertes jQuery
- `*_process.php` – vorhandene Speichermodule
- `css/`, `images/` – originale Darstellung

## Update 2.0.1

Repositoryreparatur für PDO-Dateien, PHP-8-Session und lokale JavaScript-Datei. Vor dem Aktualisieren Datenbank und Webspace sichern. Die bestehende `inc/config.php` bewahren. Änderungen an Mitgliedsart, Abrechnung und Rechten werden strikt geprüft. Siehe `UPGRADE-2.0.1.md`. Die Beitragsbuchhaltung ist noch nicht Bestandteil dieser Version.
