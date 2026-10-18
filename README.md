# Vereinssystem MVC – 3.0.0-alpha.2

**OOP/MVC-Neuentwicklung in klassischem PHP 8, MySQL/MariaDB (PDO), HTML, CSS und JavaScript.** Diese Version erweitert den Kernel aus `3.0.0-alpha.1` um **Login, Logout und ein eigenständiges Rechtesystem**. Der MVC-Branch enthält keine übernommenen PHP-Endpunkte und kein Legacy-Routing.

Das ursprüngliche Vereinssystem (Nicolas Jourdan und Sebastian Fuss, 2012/2013) liegt weiterhin auf `master` als fachliche Vorlage. Der Branch [`refactor/mvc-architecture`](https://github.com/marcdziersan/Vereinssystem/tree/refactor/mvc-architecture) wird bewusst Modul für Modul neu aufgebaut.

## Vorhandene Funktionen

- **OOP-Kernel:** expliziter Router, Front Controller, Request/Response, Fehlerbehandlung und serverseitiges View-System.
- **Login:** Benutzername **oder Mitgliedsnummer**, Passwortprüfung mit `password_verify()` und einmaliger MD5-Altpasswortmigration nach erfolgreichem Login.
- **Logout:** ausschließlich über ein CSRF-geschütztes POST-Formular; Session und Session-Cookie werden beendet.
- **Benutzerstatus:** gesperrte und inaktive Konten dürfen sich nicht anmelden; Berechtigungen werden bei jeder geschützten Anfrage neu gelesen.
- **Rechteverwaltung:** sechs Stufen (0 gesperrt, 1 Mitglied, 2 Moderator, 3 Abteilungsleitung, 4 Vorstand, 5 Administration). Zentraler Berechtigungsdienst statt verstreuter Zahlenprüfungen.
- **Administrator:** aktive Konten und Rechtestufen einsehen, andere Konten entsperren/sperren oder Rollen vergeben. Eigene Administratorrolle lässt sich dabei nicht ändern.
- **Sicherheit:** PDO-Parameterbindung, CSRF, sichere Cookies, Session-ID-Wechsel, dateibasierte Limitierung fehlgeschlagener Logins und HTML-Escaping.
- **Mobile First:** responsives Login, Kontoübersicht, Rechtematrix und Administration auf Basis des eigenen UI-Kits – ohne CSS-/JS-Frameworkzwang.

**Noch nicht integriert:** Dashboard, vollständige Mitgliederprofile, Abteilungen, Veranstaltungen, Finanzen und Kasse. Diese Module kommen jeweils mit eigenen Controllern, Services, Repositories und Views zurück. Die Zugangsadministration in alpha.2 ist **keine vollständige Mitgliederverwaltung**.

## Voraussetzungen

- PHP **8.1 oder neuer**, empfohlen 8.2–8.4, PHP-PDO-Erweiterung `pdo_mysql`.
- MySQL 5.7+/8.x oder kompatible MariaDB mit einer vorhandenen Vereinsdatenbank **oder** einer leeren Datenbank.
- HTTPS bei öffentlichen Installationen.
- Webroot **ausschließlich `public/`**. `app/`, `config/`, `storage/` usw. dürfen nicht öffentlich erreichbar sein.
- Schreibrecht des PHP-Prozesses für `storage/rate_limits/`.

Keine externen PHP- oder JavaScript-Paketmanager erforderlich. jQuery 3.6.1 liegt optional lokal vor, wird für Authentifizierung und UI nicht benötigt.

## Einrichtung

1. Projektdateien entpacken und in der Webserver-Konfiguration nur `public/` freigeben.
2. `config/database.php.example` **kopieren** nach `config/database.local.php` und die Zugangsdaten eintragen. Die lokale Datei ist von Git ausgeschlossen.
3. **Bestehende Vereinsdatenbank:** keine neue Tabelle erforderlich. Bereits angelegte aktive Benutzer können sich mit ihrem Namen oder ihrer Mitgliedsnummer und Passwort anmelden. Niemals ungesichert auf Produktivdaten ausprobieren.
4. **Neue leere Datenbank:** zuerst über `php bin/bootstrap-auth.php` die kompatible Kontotabelle und das erste Administratorkonto anlegen. Das Skript läuft nur über CLI, überschreibt keine vorhandenen Benutzer und verlangt ein Passwort von mindestens 12 Zeichen.
5. Die Webanwendung öffnen und anmelden.

### Lokaler Start

```sh
php -S 127.0.0.1:8080 -t public public/router.php
```

Browser: `http://127.0.0.1:8080/login`. Die Startseite `/` leitet zum Login oder nach der Anmeldung zu `/account` weiter.

Auf WAMP/Apache auf das `public`-Verzeichnis als DocumentRoot achten. Ohne URL-Rewrite sind alle Routen über `index.php?r=/login`, `index.php?r=/rights` usw. erreichbar.

## Seiten und Zugriffsregeln

| Route | Methode | Zugriff | Zweck |
| --- | --- | --- | --- |
| `/login` | GET / POST | Öffentlich | Anmeldeformular und Zugangskontrolle |
| `/logout` | POST | CSRF-Tokens | Sitzung sicher beenden |
| `/account` | GET | Angemeldet | Eigene Zugangsdaten und Rolle anzeigen |
| `/rights` | GET | Angemeldet | Implementierte Berechtigungen ansehen |
| `/admin/roles` | GET / POST | Administration (Level 5) | Rechtestufen anderer Konten ändern |
| `/status` | GET | Öffentlich | Technischer Kernel-Status ohne private Daten |

**Grundsatz:** Der Zugriff wird auf dem Server geprüft. Das Ausblenden einer Schaltfläche allein ist keine Berechtigungsprüfung. Ein anderer Benutzer kann sich nicht durch die Änderung von HTML-Formularwerten zusätzliche Rechte erteilen.

## Projektstruktur

| Verzeichnis | Verantwortung |
| --- | --- |
| `app/Core/` | Kernel, Router, Request/Response, Session, CSRF und PDO-Verbindung |
| `app/Auth/` | Benutzerobjekt, Authentifizierungsservice, Login-Sperre, Rollenregeln |
| `app/Repositories/` | Benutzer-Repository-Interface und PDO-Implementierung |
| `app/Controllers/` | Login-/Logout-Controller, Zugangskonto, Rollenverwaltung |
| `resources/views/` | Deutsche, serverseitig gerenderte HTML-Views und Layouts |
| `public/assets/` | Kommentierte mobile CSS-/JS-Komponenten, Original-Logo und optional jQuery |
| `routes/web.php` | Festes Register aller HTTP-Routen |
| `config/` | Öffentliche App-Einstellungen und nicht versionierte DB-Konfiguration |
| `database/migrations/` | SQL-Struktur für die Erstinbetriebnahme einer leeren Kontodatenbank |
| `bin/bootstrap-auth.php` | CLI-only Initialisierung des ersten Administrators |
| `tests/` | OOP-, Routing-, Authentifizierungs- und Berechtigungsprüfungen |
| `docs/` | Architektur, Rechtesystem, Codekonventionen und UI-Kit |

## Dokumentation

- [Architektur](docs/ARCHITECTURE.md)
- [Authentifizierung und Rechte](docs/AUTHORIZATION.md)
- [UI-Kit](docs/UI_KIT.md)
- [Stil- und Kommentarregeln](docs/CODE_STYLE.md)
- [Änderungsverlauf](CHANGELOG.md)

## Versionierung und nächster Schritt

- **3.0.0-alpha.1** – eigenständiger MVC-Kernel, Routing, Views, UI-Basis.
- **3.0.0-alpha.2** – Anmeldung, Abmeldung, Benutzerstatus und zentrale Rechtevergabe.
- **3.0.0-alpha.3 (geplant)** – Dashboard und anschließend die vollständige MVC-Mitgliederverwaltung.

## Herkunft und Lizenz

Das Originalprojekt stammt von **Nicolas Jourdan und Sebastian Fuss** (2012/2013), die technische Weiterentwicklung seit 2026 von **Marcus Dziersan**. Die ursprünglichen Bedingungen zur kostenlosen nichtkommerziellen Nutzung bleiben für übernommene Bestandteile bestehen. Eigene Ergänzungen sind im Umfang der verfügbaren Rechte unter **PolyForm Noncommercial 1.0.0** verfügbar. Fremde Bibliotheken und Grafiken unterliegen ihren jeweils eigenen Rechten. Details in `LICENSE.md` und `THIRD-PARTY-NOTICES.md`.
