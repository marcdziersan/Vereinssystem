# Vereinssystem MVC – 3.0.0-alpha.5

**Die eigenständige Neuentwicklung der Vereinsverwaltung mit PHP 8, OOP, MVC, PDO, HTML, CSS und JavaScript.**

Dieser Branch wird Schritt für Schritt neu aufgebaut, ohne die PHP-5-/PHP-8-Altskripte als paralleles System weiterzuführen. Die Anwendung auf [master](https://github.com/marcdziersan/Vereinssystem/tree/master) bleibt die fachliche Referenz. Die MVC-Entwicklung findet auf [refactor/mvc-architecture](https://github.com/marcdziersan/Vereinssystem/tree/refactor/mvc-architecture) statt.

## Funktionen dieser Entwicklungsfassung

| Bereich | Umsetzung |
| --- | --- |
| Kernel | Klassen-Autoloader, Router, Request/Response, Views, Fehlerseiten, PDO-Fabrik |
| Login und Logout | Geschützte Sitzungen, Passwort-Hashes und Übernahme historischer MD5-Konten |
| Rechtesystem | Zentrale Rollenmatrix; Administrationsmaske für die Rechtevergabe |
| Dashboard | Persönliche Startseite, Vereinskennzahlen für berechtigte Rollen |
| Mitglieder | Suche, Stammdaten, Detailansichten, Anlage, Bearbeitung und Statusverwaltung |
| **Orte und Anschriften** | Neue MVC-Masken zum Anlegen, Bearbeiten und Deaktivieren von Anschriften und Veranstaltungsorten |
| **Abteilungen** | Neue MVC-Verwaltung mit Umlagen und Mitgliedszugehörigkeiten (aktiv/passiv) |
| **Installer** | Browsergestützte Ersteinrichtung mit Datenbanktabellen und erstem Administrator |
| **Veranstaltungen und Aufgaben** | Termine mit frei definierbaren Aufgaben (Datum, Uhrzeit, Ort, Mitgliederauswahl); mehrfache Zuweisung; Konfliktprüfung |
| **MVC-Schichten** | Neue `Models`, `Services`, `Repositories` und aktiv verwendete `Middleware` für Veranstaltungen und Aufgaben |
| **WAMP im Unterordner** | Root-`.htaccess` für Start ohne eigenen `public/`-DocumentRoot |
| Frontend | Eigenes kommentiertes Mobile-First-UI-Kit, lokal bereitgestelltes jQuery optional |

Nachrichten, Beiträge und Kasse folgen in den nächsten MVC-Meilensteinen. Die bisherigen Module bleiben ausschließlich auf `master` als Vorlage erhalten.

## Installation unter WAMP – ohne public als Standardverzeichnis

1. Projektdateien beispielsweise nach `C:\wamp64\www\verein\` entpacken.
2. Apache `mod_rewrite` aktivieren und für diesen Ordner `AllowOverride All` zulassen.
3. In phpMyAdmin eine **leere MySQL- oder MariaDB-Datenbank** mit einem Datenbanknutzer anlegen.
4. Im Browser **`http://localhost/verein/`** öffnen. Die Root-`.htaccess` leitet intern in `public/` um.
5. Den Installationsassistenten ausfüllen. Er richtet sieben Tabellen ein (`mitglieder`, `orte`, `abteilungen`, `abteilungszugehoerigkeit`, `veranstaltungen`, `vs_event_aufgaben`, `vs_event_aufgaben_mitglieder`) und legt bei einer leeren Mitgliedertabelle einen Administrator an.
6. Danach über den Login anmelden. Die Datei `config/database.local.php` wird vom Installer automatisch erstellt und bleibt privat.

Die Installerroute lautet `/install`, etwa `http://localhost/verein/?r=%2Finstall`. Eine separate `install.php` im Root ist nicht notwendig – sämtliche HTTP-Aufrufe laufen über den MVC-Front-Controller `public/index.php`.

### Bestehende alpha.3-Datenbank

Ist `config/database.local.php` bereits vorhanden, bleibt sie unverändert. Die ergänzenden Tabellen werden einmalig über die PHP-Konsole angelegt:

```sh
php bin/migrate-directories.php
php bin/migrate-events.php
```

Die Migration ist wiederholbar und löscht keine vorhandenen Vereins- oder Mitgliederdatensätze. Vor Änderungen an echten Vereinsdaten empfiehlt sich ein Datenbankbackup.

### Alternativ: PHP-Entwicklungsserver

```sh
php -S 127.0.0.1:8080 -t public public/router.php
```

Hier lautet der Einstieg `http://127.0.0.1:8080/` und benötigt keine Apache-`.htaccess`.

## Bedienung

- `/dashboard`: Persönliche Startseite.
- `/members`: Mitgliederübersicht für Vorstand und Administration; im Mitgliederformular kann eine hinterlegte Anschrift ausgewählt werden.
- `/places`: Orte/Anschriften (Verwaltung ab Rechtestufe 4).
- `/departments`: Abteilungen ansehen (ab Rechtestufe 1); Bearbeiten und Zuordnen ab Stufe 4.
- `/rights`, `/admin/roles`: Rechtesystem und Rechtevergabe.
- `/events`: Veranstaltungen und Aufgaben; anlegen/bearbeiten ab Rechtestufe 4.

Alle Bearbeitungsaktionen sind serverseitig berechtigt und benötigen einen gültigen CSRF-Token. Deaktivierte Datensätze bleiben erhalten; Vorgänge der Mitgliedszuordnung verwenden transaktionale PDO-Abfragen.

## Ordnerstruktur

```text
app/
  Auth/             Benutzeridentitäten, Authentifizierung, Rollen
  Controllers/      HTTP-Endpunkte, Darstellung und Service-Aufrufe
  Models/           Domänenmodelle der alpha.5-Aufgabenplanung
  Services/         Geschäftslogik und Validierungsregeln
  Http/Middleware/  Authentifizierung, Rollen, CSRF für Event-Routen
  Events/           Repository-Verträge und native PDO-Persistenz
  Core/             MVC-Kernel, Datenbank, Routing, Session, View
  Departments/      Abteilungen, Zuordnungen, Services, Repositories
  Install/          Sicherer Erstinstallationsservice
  Members/          Mitgliederverwaltung
  Places/           Orte und Anschriften
  Repositories/     Authentifizierungszugriffe auf PDO
bin/                CLI-Werkzeuge für Migration und Administration
config/             Allgemeine und lokale Konfiguration (geheim)
database/migrations/ SQL-Schemas für Neuinstallation und Upgrade
docs/               Architektur, Rollen, UI, Installation
public/             Front-Controller und öffentliche Assets
resources/views/    Getrennte serverseitige PHP-Templates
routes/web.php      Explizite MVC-Routen
storage/            Private Runtime-Daten
tests/              PHP-Prüfprogramme
.htaccess           WAMP-Root-Rewrite und Zugriffsschutz
```

Intern bleibt die MySQL-Datenstruktur der früheren Vereinsverwaltung kompatibel. Die neuen Module verwenden ausschließlich native PDO-Parameterbindungen und keine `mysql_*`-Funktionsaufrufe.

## Dokumentation

- [Veranstaltungen, Aufgaben und MVC-Schichten](docs/EVENTS_TASKS.md)
- [Erstinstallation, WAMP-Root und Migration](docs/ALPHA4.md)
- [MVC-Architektur](docs/ARCHITECTURE.md)
- [Rollen und Berechtigungen](docs/AUTHORIZATION.md)
- [Mitgliederverwaltung](docs/MEMBERS.md)
- [UI-Kit und Breakpoints](docs/UI_KIT.md)
- [Code-Stil und Kommentarregeln](docs/CODE_STYLE.md)
- [Versionsgeschichte](CHANGELOG.md)

## Herkunft und Nutzungsbedingungen

Das ursprüngliche Vereinssystem wurde 2012/2013 von **Nicolas Jourdan und Sebastian Fuss** entwickelt. Die kostenlose, nichtkommerzielle Nutzung und Erweiterung des ursprünglichen Projekts bleibt maßgeblich. Die eigenen Weiterentwicklungen von **Marcus Dziersan** ab 2026 stehen im Umfang der jeweiligen Rechte unter der **PolyForm Noncommercial License 1.0.0**. Für jQuery und historische Grafiken gelten die jeweiligen Drittanbieterrechte; eine kommerzielle Nutzung ist nicht allgemein freigegeben. Details: [LICENSE.md](LICENSE.md) und [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
