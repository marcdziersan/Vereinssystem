# Vereinssystem MVC – 3.0.0-alpha.8

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
| **Nachrichten und Mitteilungen** | Veröffentlichte Vereinsmeldungen mit Rechtestufen, Suche, Bearbeitung durch Autoren/Administration, Archiv und Wiederherstellung |
| **Kassenverwaltung** | Frei konfigurierbare Konten, Einnahmen, Ausgaben, Umbuchungen, Stornos, gemeinsame Beitragsbuchungen und mobile Kassenübersicht |
| **Beiträge und Finanzen** | Historische Beitragssätze, monatliche Sollstellung, Forderungen, Zahlungseingänge, Teilzahlungen, Guthaben, Rückstände und Stornierungen |
| **MVC-Schichten** | `Models`, `Services`, `Repositories` und aktiv verwendete `Middleware` für Veranstaltungen, Aufgaben und Nachrichten |
| **WAMP im Unterordner** | Root-`.htaccess` für Start ohne eigenen `public/`-DocumentRoot |
| Frontend | Eigenes kommentiertes Mobile-First-UI-Kit, lokal bereitgestelltes jQuery optional |

Die Beitrags- und Kassenbuchhaltung sind inzwischen miteinander verknüpft. Private Belegdateien und der automatische Bankabgleich folgen separat. Das Nachrichtensystem ist ein interner Vereins-Aushang, kein privater Chat. Die bisherigen Module bleiben ausschließlich auf `master` als Vorlage erhalten.

## Installation unter WAMP – ohne public als Standardverzeichnis

1. Projektdateien beispielsweise nach `C:\wamp64\www\verein\` entpacken.
2. Apache `mod_rewrite` aktivieren und für diesen Ordner `AllowOverride All` zulassen.
3. In phpMyAdmin eine **leere MySQL- oder MariaDB-Datenbank** mit einem Datenbanknutzer anlegen.
4. Im Browser **`http://localhost/verein/`** öffnen. Die Root-`.htaccess` leitet intern in `public/` um.
5. Den Installationsassistenten ausfüllen. Er richtet 15 Tabellen ein (`mitglieder`, `orte`, `abteilungen`, `abteilungszugehoerigkeit`, `veranstaltungen`, `vs_event_aufgaben`, `vs_event_aufgaben_mitglieder`, `news`, `vs_forderungen`, `vs_zahlungen`, `vs_zuordnungen`, `vs_tarife`, `vs_konten`, `vs_kassenbuch`, `vs_audit`) und legt bei einer leeren Mitgliedertabelle einen Administrator an.
6. Danach über den Login anmelden. Die Datei `config/database.local.php` wird vom Installer automatisch erstellt und bleibt privat.

Die Installerroute lautet `/install`, etwa `http://localhost/verein/?r=%2Finstall`. Eine separate `install.php` im Root ist nicht notwendig – sämtliche HTTP-Aufrufe laufen über den MVC-Front-Controller `public/index.php`.

### Bestehende Entwicklungsdatenbank

Ist `config/database.local.php` bereits vorhanden, bleibt sie unverändert. Die ergänzenden Tabellen werden einmalig über die PHP-Konsole angelegt:

```sh
php bin/migrate-directories.php
php bin/migrate-events.php
php bin/migrate-news.php
php bin/migrate-finance.php
php bin/migrate-cash.php
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
- `/news`: Vereinsnachrichten lesen (ab Rechtestufe 1, einzelne Beiträge nach Mindeststufe).
- `/news/create`, `/news/edit`: Mitteilungen verfassen und bearbeiten (Vorstand/Administration; fremde Autoren nur Administration).
- `/news/archived`: Archiv verwalten und Beiträge wiederherstellen (Administration).
- `/finance`: persönliches Beitragskonto; Vorstands- und Administrationsrollen können über eine Mitglieds-ID fremde Konten verwalten.
- `/finance/tariffs`: historische Monatstarife für Erwachsene, Kinder und Abteilungen (Administration).
- `/cash`: Kassenkonten, Journal, Ein-/Ausgaben und Umbuchungen (Vorstand/Administration); Konten und Stornos nur Administration.
- `/finance`: Zahlungseingänge mit Kassenkonto, manuelle Forderungen, Sollstellung und Guthaben-Zuordnung (je nach Rolle).

Alle Bearbeitungsaktionen sind serverseitig berechtigt und benötigen einen gültigen CSRF-Token. Deaktivierte Datensätze bleiben erhalten; Vorgänge der Mitgliedszuordnung verwenden transaktionale PDO-Abfragen.

## Ordnerstruktur

```text
app/
  Auth/             Benutzeridentitäten, Authentifizierung, Rollen
  Controllers/      HTTP-Endpunkte, Darstellung und Service-Aufrufe
  Models/           Unveränderliche Domänenobjekte u. a. für CashAccount und CashEntry
  Services/         Geschäftslogik und Validierungsregeln (inklusive NewsService)
  Http/Middleware/  Authentifizierung, Rollen und CSRF für geschützte Fachrouten
  Events/           Repository-Verträge und native PDO-Persistenz
  News/             Nachrichten-Repositories und Validierungsfehler
  Finance/          Exakte Cent-Beträge und Repository-Schnittstellen
  Cash/             Kassen-Repository-Vertrag und InnoDB-Persistenz
  Services/FinanceService.php  Beitragsregeln und Validierung
  Services/CashService.php     Kassenregeln und Validierung
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

- [Kassenbuch: Konten, Buchungen, Umbuchungen und Beitragsverknüpfung](docs/CASHBOOK.md)
- [Beitragsbuchhaltung](docs/FINANCE.md)
- [Nachrichten, Rollen, Archiv und Migration](docs/NEWS.md)
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

## Beitragsverwaltung (alpha.7)

- **Monatliche Sollstellung:** Ein Administrator trägt zunächst datierte Tarife ein und erzeugt anschließend pro Monat Forderungen. Bereits gebuchte Monatsforderungen werden nicht dupliziert. Ruhende Mitgliedschaften werden übersprungen. Aktivenumlagen können den Betrag der Abteilung als Ausgangswert verwenden.
- **Zahlungseingänge:** Vorstand und Administration buchen Zahlungen und Teilzahlungen. Der Finanzservice verteilt diese auf die ältesten offenen Forderungen, oder zuerst auf eine ausgewählte Forderung. Nicht verbrauchte Beträge bleiben als Guthaben erhalten.
- **Nachvollziehbarkeit:** Forderungen und Zahlungen bleiben bei Storno mit Datum, Grund und Akteur erhalten. Bereits bezahlte Forderungen können nicht einzeln storniert werden. Mit Kasseneinträgen verknüpfte Zahlungen werden im Finanzbereich gemeinsam mit ihrer Kassenbuchung storniert.
- **Berechtigungen:** Mitglieder sehen ausschließlich ihr eigenes Beitragskonto. Der Vorstand darf buchen, die Administration verwaltet zudem Tarife, monatliche Sollstellung und Stornos.

Details zu Datenmodell, Buchungsfällen und Transaktionen stehen in [`docs/FINANCE.md`](docs/FINANCE.md).

## Kassenverwaltung (alpha.8)

- **Konten:** Frei definierbare Bargeld-, Bank-, PayPal- und weitere Konten; Anfangsbestände und Eröffnungsdatum bleiben historisch fixiert.
- **Buchungsjournal:** Einnahmen und Ausgaben in Cent, Kontoauswahl, Kategorien, Beschreibung, Belegnummer und Datum.
- **Umbuchungen:** Zwei gleich hohe Gegenbuchungen in einer Transaktion. Ein Storno hebt beide Seiten auf.
- **Beiträge:** Zahlungseingänge werden dem ausgewählten Kassenkonto und dem Beitragskonto in **derselben Transaktion** zugeordnet; auch das Storno ist gemeinsam.
- **Rechte:** Ab Vorstand ist die Kasse einseh- und bebuchbar; Kontenverwaltung und Stornos sind der Administration vorbehalten.

Details: [`docs/CASHBOOK.md`](docs/CASHBOOK.md).
