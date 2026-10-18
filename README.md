# Vereinssystem MVC – Kernel (3.0.0-alpha.1)

**Eigenständiger PHP-8-MVC-Neubau auf dem Branch `refactor/mvc-architecture`.** Das bisherige Vereinssystem 2.4.0 bleibt unverändert auf `master` und dient ausschließlich als Referenz für Fachregeln, Datenbankstruktur und die Herkunft der grafischen Elemente.

Dieses erste Release enthält **nur den Kernel**. Es gibt absichtlich **keinen Login, kein Dashboard, keine Mitgliederverwaltung, keine Kassenführung und keine Legacy-Links**. Die Anwendung startet im Browser und demonstriert die HTTP-Verarbeitung, die wir anschließend Schritt für Schritt für echte Fachmodule nutzen.

## Technische Basis

- PHP 8.2+ empfohlen (Core verwendet Features ab PHP 8.1), OOP mit Namespaces und eigenem Autoloader.
- MVC-Struktur mit Front Controller, Router, Request, Response, View und KernelController.
- PDO-Verbindungsfabrik bereits vorhanden, **aber keine Datenbankverbindung beim Start erforderlich**.
- Session und CSRF-Kern vorbereitet; Authentifizierung kommt in einem späteren Meilenstein.
- Eigenes Mobile-First-UI-Kit (`vs-ui.css`, `vs-ui.js`) als schlanke Alternative zu Bootstrap/jQuery Mobile.
- Das historische VereinsSystem-Logo ist als wiedererkennbares Markenzeichen enthalten.
- Lokales jQuery 3.6.1 liegt als optionaler Drittanbieterbaustein bereit, wird vom Kernel aber nicht benötigt.
- Kein Composer, Node, NPM, Laravel, React oder Vue.

## Lokaler Start (Windows/WAMP oder PHP-CLI)

In einer Konsole im Projektordner:

```powershell
php -S 127.0.0.1:8080 -t public public/router.php
```

Danach im Browser **http://127.0.0.1:8080/** öffnen. Der JSON-Endpunkt steht unter **http://127.0.0.1:8080/status**. Ein absichtlich ungültiger Pfad zeigt eine 404-Seite.

Unter Apache/WAMP muss das öffentlich erreichbare Verzeichnis **`public/`** sein (etwa über einen VirtualHost). Falls kein Umschreiben von URLs verfügbar ist, funktionieren die Routen trotzdem über `public/index.php?r=/status`.

**Wichtig:** Nicht den gesamten Projektordner ungeschützt ins DocumentRoot legen. `config/`, `storage/`, `app/` und `resources/` dürfen nicht aus dem Browser erreichbar sein.

## Dateien und Zuständigkeiten

| Ort | Funktion |
| --- | --- |
| `bootstrap.php` | Autoloader und Application-Factory |
| `app/Core/Application.php` | Komposition, HTTP-Dispatch und Fehlerbehandlung |
| `app/Core/Router.php` | Explizite Routing-Tabelle inklusive 404/405 |
| `app/Core/Request.php`, `Response.php` | HTTP-Anfrage/-Antwort als Objekte |
| `app/Core/View.php` | Gekapselte PHP-Views und HTML-Escaping |
| `app/Core/Database.php` | Optionale PDO-Fabrik mit parametrisierungstauglicher Verbindung |
| `app/Core/Session.php`, `Csrf.php` | Vorbereiteter Schutz für spätere Formulare |
| `app/Controllers/KernelController.php` | Nur Kernel-Startansicht und JSON-Status |
| `routes/web.php` | Routenregistrierung (ohne dynamisches PHP-Include) |
| `resources/views/` | Layouts und Kernel-Views |
| `public/` | Einziger Webroot mit Front Controller und statischen Assets |
| `public/assets/css/vs-ui.css` | Eigenes Mobile-First-Komponentensystem |
| `public/assets/js/vs-ui.js` | Barrierearme, optionale UI-Interaktionen |
| `config/` | Öffentliches App-Setup und DB-Konfigurationsvorlage |
| `tests/kernel_smoke.php` | Datenbankfreie Kernprüfungen |

Die Architektur ist ausführlich unter [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) beschrieben, die UI-Bausteine unter [docs/UI_KIT.md](docs/UI_KIT.md) und die Code-Kommentarregeln unter [docs/CODE_STYLE.md](docs/CODE_STYLE.md).

## Prüfungen

```sh
php tests/kernel_smoke.php
```

Die einzelnen Quelldateien enthalten Docblocks und Kommentare zum **Warum** jeder Infrastrukturkomponente. Neue Fachmodule erhalten in Folgeschritten dieselbe Struktur.

## Geplante Reihenfolge

1. **Kernel** – dieser Entwicklungsstand.
2. Login/Logout und Rechteverwaltung neu implementieren.
3. Dashboard und Mitgliederverwaltung.
4. Abteilungen, Orte, Veranstaltungen, Dienste, Nachrichten.
5. Beitragsbuchhaltung und Kasse unter dem neuen MVC-Service-Layer.
6. Installation/Updates, Migration und vollständige fachliche Abnahme.

Alle Module werden *neu* in die MVC-Struktur übertragen. Es findet keine automatische Übernahme der alten `*_process.php`-Seiten statt.

## Herkunft und Lizenz

Originalprojekt **Nicolas Jourdan und Sebastian Fuss (2012/2013)**, Weiterentwicklung **Marcus Dziersan (seit 2026)**. Die historischen nichtkommerziellen Nutzungsbedingungen bleiben für übernommene Bestandteile maßgeblich. Eigenständige neue Beiträge stehen – soweit die nötigen Rechte vorhanden sind – unter der **PolyForm Noncommercial License 1.0.0**. Details in [LICENSE.md](LICENSE.md), [LICENSE-POLYFORM-NONCOMMERCIAL.md](LICENSE-POLYFORM-NONCOMMERCIAL.md) und [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
