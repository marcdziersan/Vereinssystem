# Vereinssystem MVC – 3.0.0-alpha.3

**Modularer Vereinsmanager in klassischem PHP 8, OOP/MVC, PDO, HTML, CSS und JavaScript.** Die Neuentwicklung läuft unter [`refactor/mvc-architecture`](https://github.com/marcdziersan/Vereinssystem/tree/refactor/mvc-architecture). Der historische Entwicklungsstand **2.4.0 auf `master`** dient ausschließlich als fachliche Vorlage. Es gibt kein Legacy-Routing und keine alten PHP-Endpunkte im MVC-Branch.

## Was alpha.3 enthält

- **MVC-Kernel:** klarer Autoloader, Request/Response, feste Routen, Fehlerseiten, Views, Session und CSRF.
- **Login/Logout:** Benutzername oder Mitgliedsnummer, PHP-Passworthashes, automatische Ablösung alter MD5-Hashes, Sitzungsverwaltung und Login-Begrenzung.
- **Rechtesystem:** sechs zentrale Stufen, geschützte Rollenänderungen ausschließlich durch die Administration.
- **Dashboard:** persönliche Einstiegsseite; Vorstand und Administration sehen zusätzlich aktive Mitgliederzahlen nach Mitgliedschaftsart.
- **Mitgliederverzeichnis:** mobile Suche nach Name, Mitgliedsnummer und Benutzername, begrenzte Seitennavigation und Aktiv-/Inaktiv-Filter für die Administration.
- **Mitgliedsprofil:** Informationen zu Mitgliedschaft, Kontakt, Eintritt und Geburtstag; interne Notizen nur für Verwaltungsberechtigte.
- **Mitglieder anlegen:** Stammdaten und Initialzugang anlegen; neue Konten erhalten **immer** die niedrigste aktive Rechtestufe 1.
- **Mitglieder bearbeiten:** Stammdaten validiert speichern; Passwörter, Rechte, Zugangsnamen und Bankdaten sind bewusst nicht über diesen Vorgang änderbar.
- **Statusverwaltung:** Administration kann nichtadministrative Mitglieder deaktivieren und reaktivieren, ohne Datensätze zu löschen. Eigene oder fremde Administratorkonten sind vor diesem Vorgang geschützt.
- **Mobile-First-UI:** ein eigenes, kommentiertes CSS-/JavaScript-UI-Kit mit Karten, Formularen, Navigation und Hell-/Dunkelschema.

Noch nicht Bestandteil der MVC-Neuentwicklung sind Abteilungen, Orte, Veranstaltungen, Dienste, Nachrichten, Beitrags-/Kassenbuchhaltung und der vollständige historische Benutzerprofil-/Bankdateneditor. Diese Module werden in späteren Meilensteinen **neu aufgebaut**, nicht als alte PHP-Skripte mitgeführt.

## Rechte für das neue Mitglieder-Modul

| Funktion | Berechtigt |
| --- | --- |
| Dashboard, eigenes Mitgliedsprofil | Aktive Benutzer ab Stufe 1 |
| Mitglieder suchen und Profile ansehen | Vorstand (4), Administration (5) |
| Stammdaten anderer nichtadministrativer Mitglieder bearbeiten | Vorstand (4), Administration (5) |
| Administratorprofile bearbeiten | Ausschließlich Administration (5) |
| Mitglieder anlegen | Ausschließlich Administration (5) |
| Mitglieder deaktivieren/reaktivieren | Ausschließlich Administration (5); keine Adminkonten |
| Rechte anderer Konten ändern | Ausschließlich Administration (5), über die getrennte Rechteverwaltung |

Bei jedem geschützten Request werden Zugang und Rechte erneut aus der Datenbank gelesen. Das Ausblenden von Links ersetzt keine serverseitige Zugriffsprüfung.

## Installation und lokaler Start

Voraussetzungen: PHP **8.1+**, MySQL/MariaDB und die PHP-Erweiterung `pdo_mysql`. Apache, WAMP oder ein vergleichbarer Webserver; bei öffentlich zugänglichen Installationen HTTPS.

1. Projekt in ein eigenes Verzeichnis entpacken; **Webroot ausschließlich auf `public/`** legen.
2. `config/database.php.example` nach `config/database.local.php` kopieren und die Zugangsdaten zur Vereinsdatenbank hinterlegen (diese Datei wird nicht versioniert).
3. Vorhandene Vereinsdatenbank: Die Mitglieder-Tabelle `mitglieder` des alten Vereinssystems wird weitergenutzt, ohne eine automatische Migration oder Änderung an den bisherigen Finanzdaten.
4. Neue leere Datenbank: `php bin/bootstrap-auth.php` aufrufen, um die kompatible Mitgliedertabelle und das erste Administratorkonto anzulegen.
5. Anwendung unter `/login` öffnen, anmelden und das Dashboard unter `/dashboard` aufrufen.

Lokaler Entwicklungsserver:

```sh
php -S 127.0.0.1:8080 -t public public/router.php
```

Browser: `http://127.0.0.1:8080/login`. Für Apache ohne URL-Rewrite steht `index.php?r=/login` zur Verfügung. Der `/status`-Endpunkt liefert den technischen Kernel-Status ohne private Mitgliederdaten.

**Achtung bei alten Mitgliedsdaten:** Die MVC-Formulare schreiben in die ursprüngliche `mitglieder`-Tabelle. Vor Änderungen an einem bestehenden Verein den Datenbankbestand sichern. Kein automatisch erzeugter Admin darf vorhandene Logins überschreiben.

## Architektur

```text
app/
  Auth/               # Authentifizierung, Rollen und Identitätsmodell
  Controllers/        # HTTP-Controller; Zugriffs- und CSRF-Grenzen
  Core/               # Kernel, Routing, Datenbankfabrik, Sessions, View
  Members/            # Mitgliedsdatenmodell, Verträge, PDO-Repository, Fachservice
  Repositories/       # Login-Repository und zugehöriger Vertrag
config/               # Versionskontrollierte Defaults und lokale DB-Konfiguration
database/migrations/  # SQL-Basis für eine leere Mitgliedertabelle
resources/views/      # PHP-Views, ohne SQL-Logik
  auth/ dashboard/ members/ rights/ layouts/ errors/
public/               # Einziger öffentlicher Webroot
  assets/css/         # Eigenes Bootstrap-artiges UI-System
  assets/js/          # Interaktionslogik ohne Frameworkzwang
routes/web.php        # Alle Routen explizit registriert
tests/                # Isolierte Kernel-, Auth- und Fachlogikchecks
docs/                 # Architektur, Berechtigungen und UI-Konventionen
```

Alle Abfragen der neuen Mitgliederverwaltung verwenden vorbereitete PDO-Statements mit gebundenen Parametern. Es gibt **keine** Übernahme historischer `mysql_*`-Funktionen, `db_escape()`-Hilfen oder des alten SQL-Kompatibilitätsmodus.

## Weitere Dokumentation

- [Architektur und Datenflüsse](docs/ARCHITECTURE.md)
- [Mitgliedermodul: Felder und Berechtigungen](docs/MEMBERS.md)
- [Rollen und Zugriff](docs/AUTHORIZATION.md)
- [UI-Kit](docs/UI_KIT.md)
- [Code-Stil](docs/CODE_STYLE.md)
- [Versionsgeschichte](CHANGELOG.md)

## Herkunft und Lizenz

Original: **Nicolas Jourdan und Sebastian Fuss**, 2012/2013. Technische Weiterentwicklung seit 2026: **Marcus Dziersan**. Die nichtkommerzielle Nutzungserlaubnis des Originals bleibt für übernommene Bestandteile gültig. Eigene Erweiterungen stehen, soweit entsprechende Rechte bestehen, unter **PolyForm Noncommercial 1.0.0**. Fremde Ressourcen haben eigene Nutzungsbedingungen. Details: `LICENSE.md`, `LICENSE-POLYFORM-NONCOMMERCIAL.md` und `THIRD-PARTY-NOTICES.md`.
