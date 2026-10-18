# MVC-Kernarchitektur – 3.0.0-alpha.1

## Ausgangspunkt und Abgrenzung

Der Branch ist ein echter Neuanfang. Die MVC-Ordner ersetzen den alten Wurzeldateibaum, statt ihn durch eine zweite Oberfläche zu ergänzen. Der alte Code bleibt über den `master`-Branch als Referenz verfügbar. Im ersten Meilenstein werden keine alten PHP-Dateien, Datenbanktabellen oder Mitglieder-/Kassenfunktionen ausgeführt.

## HTTP-Datenfluss

```text
Browser
  │  GET /
  ▼
public/index.php (Front Controller)
  │
bootstrap.php → Autoloader → Application
  │
Request → Router (routes/web.php)
  │
KernelController → View (resources/views/*)
  │
Response → Browser
```

Ein zweiter Pfad `GET /status` liefert JSON. Ein unbekannter Pfad liefert 404, eine falsche Methode auf einer bekannten Route 405. Ausnahmen werden zentral protokolliert und als generische 500-Seite ausgegeben.

## Bausteine

- **Core:** kleine Klassen für HTTP, Routing, Templates, Konfiguration, Session, CSRF und optionale PDO-Verbindungen. Keine Kenntnis von Mitgliedern oder Konten.
- **Controller:** entscheidet, welche Anwendungsaktion auf einen HTTP-Request folgt. `KernelController` ist derzeit das einzige Beispiel.
- **Services:** Geschäftsregeln und transaktionale Abläufe kommen in späteren Releases hierher.
- **Repositories:** kapseln ausschließlich Datenzugriffe über PDO/Prepared Statements.
- **Models:** repräsentieren Fachobjekte; Kern und View sollen sie nicht selbst speichern.
- **Views:** reine HTML-Ausgabe auf Basis übergebener Variablen, kein SQL und keine Schreibaktionen.
- **Middleware:** später Berechtigungen, CSRF und Änderungsprotokoll pro Route.

## Betrieb und Sicherheit

1. Nur `public/` ist Webroot. `app/`, `config/`, `resources/`, `database/`, `storage/` dürfen nicht direkt im Browser geöffnet werden.
2. Die erste Ansicht nutzt keine Datenbank; `Database::connection()` wird erst von späteren Repositories angefordert.
3. Session-Funktionen sind vorbereitet, starten aber nicht ohne Bedarf.
4. Die statischen Routes werden niemals aus dem URL-Pfad als Klassennamen oder PHP-Dateipfade berechnet.
5. Ausgaben mit variablen Werten erfolgen über `View::escape()`; rohe HTML-Ausgabe ist nur für explizit intern erstellte Teil-Views erlaubt.
6. PDO setzt Exception-Modus und native Prepared Statements; kein `sql_mode=NO_ENGINE_SUBSTITUTION`.
7. Kein PHP-Fehlertext oder SQL-Stacktrace wird absichtlich im Browser ausgegeben.
8. `.htaccess` ermöglicht saubere Apache-URLs; die `?r=`-Fallback-Routen funktionieren auch ohne Rewrite.

## Hosting ohne Framework

Für PHP-CLI: `php -S 127.0.0.1:8080 -t public public/router.php`.
Für WAMP/Apache: DocumentRoot auf `…/Vereinssystem-MVC-3.0.0-kernel/public/`, Rewrite-Regeln optional.

## Datenbankkompatibilität der künftigen Fachmodule

Beim Neuaufbau werden Feld- und Tabellennamen der bestehenden Datenstruktur einzeln aus dem `master`-Branch übernommen, ihre Anforderungen dokumentiert und danach **als eigener Repository- und Service-Code** umgesetzt. Die 18 Tabellen werden nicht blind im ersten Schritt kopiert. Datenbankmigrationen werden versioniert angelegt, sobald ein Fachmodul sie benötigt.
