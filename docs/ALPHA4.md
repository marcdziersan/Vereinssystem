# MVC 3.0.0-alpha.4 – Abteilungen, Orte, Installer und WAMP

## Umfang

- `app/Places/*`: Domänenvalidierung, PDO-Repository und Ortsdaten.
- `app/Departments/*`: Abteilungen, Cent-Umlagen, Mitgliedszuordnung, Transaktionen.
- `app/Controllers/{Place,Department}Controller.php`: HTTP-/Rechte-/CSRF-Schicht.
- `app/Install/InstallationService.php` und `InstallController.php`: geführte Ersteinrichtung in `GET/POST /install`.
- `database/migrations/002_departments_places.sql`: drei zusätzliche, kompatible Tabellen.
- `bin/migrate-directories.php`: CLI-Update bereits eingerichteter alpha.3-Installationen.
- `.htaccess` im Projektroot: Apache/WAMP leitet an `public/` weiter und blockiert internen Code.

## WAMP ohne eigenen public-DocumentRoot

Projekt nach `C:\\wamp64\\www\\verein\\` entpacken und `http://localhost/verein/` öffnen. Für saubere URL-Umschreibungen muss Apache `mod_rewrite` aktiviert sein und `AllowOverride All` im Verzeichnis gelten. Im Projektroot wird `public/` intern angesprochen. CSS/JavaScript liegt weiterhin unter `public/assets/`.

### Neue Einrichtung

1. Leere MySQL-Datenbank und Datenbankbenutzer anlegen.
2. `/install` im Browser aufrufen (z. B. `http://localhost/verein/?r=%2Finstall`).
3. DB-Zugang und Administrator eingeben. Der Installer ergänzt vier Tabellen und schreibt `config/database.local.php`.
4. Nach der Einrichtung ist der Installer gesperrt. Über die Anmeldung geht es zu Dashboard und Mitgliedern.

### Existierende alpha.3

Bestehende `config/database.local.php` behalten; im WAMP-Terminal `php bin/migrate-directories.php` ausführen. Der Vorgang ergänzt ausschließlich die Tabellen `orte`, `abteilungen`, `abteilungszugehoerigkeit`.

## Sicherheitsgrenzen

- Keine Quellcodeordner öffentlich freigeben; `.htaccess`-Dateien und `public/`-Root schützen den Apache-Betrieb. Installations- und Sicherheitsfunktionen liegen im neuen OOP-Core.
- Der Installer ist nur ohne lokale Datenbankkonfiguration erreichbar und überschreibt nie bereits angelegte Daten.
- Änderungen an Orten, Abteilungen und Zuordnungen erfolgen über POST mit CSRF-Schutz und den Rollenrechten.
- Alte Tabellenfelder und vorhandene Mitglieder-IDs werden beibehalten. Es gibt keine Legacy-PHP-Seiten.

> **Ab alpha.5:** Zusätzlich gehört das Veranstaltungsschema `003_events_tasks.sql` zur Erstinstallation; bestehende alpha.4-Datenbanken werden mit `php bin/migrate-events.php` erweitert. Siehe `docs/EVENTS_TASKS.md`.
