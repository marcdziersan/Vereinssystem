# MVC-Architektur – 3.0.0-alpha.10.2 (Nachhärtung von alpha.9)

## Schichten und Abhängigkeiten

1. **`public/index.php`** ist der einzige öffentliche PHP-Einstieg und erzeugt eine `Request`-Instanz.
2. **`app/Core/Application.php`** bildet die Composition Root und registriert die Services und Controllers.
3. **`routes/web.php`** definiert feste, menschenlesbare Routen (GET/POST).
4. **Controllers** übersetzen HTTP-Anfragen in Aufrufe fachlicher Services und Views.
5. **`AuthService` und `Roles`** kapseln Anmeldung und Berechtigungen unabhängig von der Datenbank.
6. **`UserRepository` und `PdoUserRepository`** isolieren Abfragen an die bestehende `mitglieder`-Tabelle.
7. **`resources/views`** rendern ausschließlich HTML; alle dynamischen Werte werden escaped.

Die Verträge erlauben es, für die Entwicklungschecks ein Fake-Repository einzusetzen, ohne die reale Datenbank zu verändern.

## Konfiguration und Sicherheit

`config/app.php` enthält keine Geheimnisse. `config/database.local.php` ist die private lokale Konfiguration und wird nicht versioniert. Die PDO-Verbindung entsteht erst beim ersten Datenbankzugriff (lazy connection) mit Fehlerausnahmen, `utf8mb4` und nativen Prepared Statements.

Der Webroot ist ausschließlich `public/`. Sessions und Login-Versuchsdateien liegen geschützt auf dem Server. Formulare erfordern CSRF-Prüfung. Zugriffsschutz erfolgt in Route-Middleware (Authentifizierung, Rechte, CSRF) und ergänzend in Controllern bei objektbezogenen Freigaben.

## Datenmodell

Alpha.3 verwendet für Anmeldung und Mitgliederverwaltung jeweils sorgfältig ausgewählte Spalten der historisch vorhandenen Tabelle `mitglieder`. Für eine **neue** Datenbank kann `bin/bootstrap-auth.php` die kompatible Tabelle initialisieren, ohne die übrigen 17 Tabellen der alten Anwendung einzurichten. Die alten Fachmodule werden nicht übernommen.

## Frontend

CSS-Komponenten mit `vs-`-Prefix, zentrales Token-System, progressive Verbesserungen durch Vanilla-JavaScript. Das ursprüngliche Vereinslogo bleibt enthalten. jQuery ist optional verfügbar, jQuery Mobile und Bootstrap sind nicht nötig.

## Historischer Meilenstein

Abteilungen und Orts-/Adressverwaltung als eigenständige MVC-Fachmodule. Die abgeschlossenen Dashboard- und Mitgliederfunktionen bleiben dabei unverändert integriert.

## Alpha.3 – Mitgliederdomäne

Die Anmeldung verwendet weiterhin `UserRepository`, das nur Authentifizierungsdaten
liefert. Fachliche Mitgliedsdaten gehören unabhängig davon zum `MemberRepository`.

`MemberController` → `MemberService` (Validierung/Geschäftsregeln) →
`MemberRepository` (Vertrag) → `PdoMemberRepository` (parametrisierte SQL-Abfragen).

`DashboardController` nutzt dasselbe Repository für aggregierte Kennzahlen. Die
Repository-Verbindung wird vom Composition Root (`Application`) erstellt; HTTP,
Services und Views greifen niemals direkt auf eine PDO-Instanz zu.

Die vorhandene Tabelle `mitglieder` bleibt kompatibel. Die erste Mitgliederanlage
setzt zwingende historische Felder ausdrücklich und erstellt ein Konto mit Level 1.
Zugangsrechte bleiben ausschließlich im Rechte-Controller änderbar. Passwörter und
Bankinformationen werden in Profil-Queries nie abgefragt oder gerendert.

## alpha.4 – Neue Fachmodule und Installer

`app/Places` und `app/Departments` besitzen jeweils einen Repository-Vertrag, ein PDO-Repository und einen Fachservice zur Eingabevalidierung. Controllers nehmen ausschließlich explizit erlaubte Formularfelder entgegen, prüfen Rollen und CSRF, rufen den Service auf und rendern Views ohne SQL. Die Tabellenbezeichnungen und Integer-Cent-Beträge entsprechen der Originaldatenbank von 2013.

Der Installer unter `app/Install/` ist ein eigenständiger Verwaltungsdienst. Er erzeugt die vier Tabellen über zwei deklarative SQL-Migrationen und speichert die lokale Datenbankkonfiguration erst nach erfolgreicher Initialisierung. Bestehende Mitglieder werden nicht überschrieben. Eine bereits vorhandene lokale Konfiguration blockiert eine erneute Installation.

### Apache/WAMP ohne public-DocumentRoot

Die `.htaccess` im Projektroot schreibt Anfragen intern nach `public/` um, sperrt private Ordner und schützt Konfiguration und Bootstrap. `public/index.php` bleibt der einzige HTTP-Front-Controller; der Router verarbeitet nur explizit definierte Routen. `Request::fromGlobals()` erkennt ein Apache-internes `/verein/public/index.php`, erzeugt aber externe Verweise auf `/verein/?r=...`, sodass Unterordnerinstallationen funktionieren. Der PHP-Entwicklungsserver nutzt weiterhin `public/router.php`.

`bin/migrate-directories.php` führt auf bereits eingerichteten Installationen ausschließlich `CREATE TABLE IF NOT EXISTS` aus. Es ist kein automatischer Migrations- oder Datenlöschprozess über GET-Routen zugänglich.

## alpha.5 – Domänenmodelle, Services, Repository, Middleware

`Controller → Service → Model → Repository (PDO)` ist verbindlich für die neue Veranstaltungs-/Aufgabenplanung. Das EventRepository-Interface bleibt für echte Datenbankzugriffe und ersetzbare Testimplementierungen erhalten. Authentifizierungs-, Rechte- und CSRF-Middleware werden pro Route in `routes/web.php` zusammengesetzt. Einzelheiten: [EVENTS_TASKS.md](EVENTS_TASKS.md).

## alpha.6 – Veröffentlichte Vereinsnachrichten

`NewsController` übersetzt GET-/POST-Anfragen in reine Domain-Aktionen von `NewsService`.
Der Service validiert Titel, Klartext, Sichtbarkeitsstufe, Autorenschaft und
Archivstatus und übergibt unveränderliche `Models/NewsPost` an das
`NewsRepository`-Interface. `News/PdoNewsRepository` arbeitet mit gebundenen
PDO-Parametern gegen die vorhandene `news`-Tabelle.

Die Middleware `Authenticated`, `Permission` und `VerifyCsrf` sichert
Nachrichtenrouten vor dem Controller. Zusätzlich prüfen Service und Controller
objektbezogene Rechte: Autorenschaft, Mindestfreigabe, Archivzustand.

**Nichtöffentliche Ansichten**: Beiträge werden nur ab ihrer Freigabestufe
aus der Datenbank gelesen; Detailansichten prüfen dieselbe Regel separat.
Archivierte Meldungen tauchen im normalen Verzeichnis nicht auf. Im Archiv
ist die Wiederherstellung ausschließlich Administratoren möglich.

Views escaped dynamische Werte konsequent. Historisch gespeichertes HTML
wird als Text angezeigt und nicht interpretiert. Es existiert absichtlich
kein JavaScript-HTML-Editor und keine private Direktnachrichtenfunktion.

Migrationsweg: `database/migrations/004_news.sql` beim Installer oder per
`php bin/migrate-news.php` bei bestehenden Installationen. Kein DROP, DELETE
oder UPDATE vorhandener Nachrichten. Details: [NEWS.md](NEWS.md).

## Alpha.7: Contribution ledger

`FinanceController` ruft `FinanceService` auf; `FinanceRepository` kapselt die Datenspeicherung und `PdoFinanceRepository` hält Beitragsbuchungen per InnoDB-Transaktion konsistent. `Models/Claim.php`, `Payment.php` und `ContributionRate.php` sind reine unveränderliche Datenobjekte. Alle Finanzrouten verwenden `Authenticated`, `Permission` und für POST `VerifyCsrf`. Siehe [`FINANCE.md`](FINANCE.md).

## alpha.8 – Kasse und Beitragsbuchhaltung

Die Kassen-Domäne implementiert `CashController` → `CashService` → `CashRepository`/`PdoCashRepository` → PDO/InnoDB. `CashAccount` und `CashEntry` sind reine Lese-Models. Die Finance- und Cash-Repositories werden in der Composition Root mit **derselben** `Database`-Instanz erzeugt. Für Beitragszahlungen nutzt der Cash-Adapter `PdoFinanceRepository::receivePaymentInsideCurrentTransaction()` als wiederverwendbare Buchungsoperation, statt verschachtelte Transaktionen zu starten.

Berechtigungen und CSRF werden für alle neuen Kassenrouten in der Middleware geprüft. `006_cash.sql` ergänzt drei historisch kompatible Tabellen. Weitere Details stehen in [`CASHBOOK.md`](CASHBOOK.md).


## alpha.9 – Reports und sichere Belegdateien

Getrennte MVC-Schichten in `app/Models/CashReport.php`, `app/Reports/*`, `app/Services/ReportService.php`, `app/Controllers/CashReportController.php` sowie `app/Models/Receipt.php`, `app/Receipts/*`, `app/Services/ReceiptService.php` und `app/Controllers/ReceiptController.php`. Alle Controller sind an Rollen-Middleware gebunden, Uploads zusätzlich an CSRF. Der PDF-Generator `PdfWriter` ist von PDO unabhängig. Der Cache für das 300-zeilige Kassenjournal spielt für Jahresberichte keine Rolle. Ein Beleg besteht aus einer Datenbank-Metazeile und einer Datei unter `storage/receipts/`; Datenbank und Dateispeicher müssen gemeinsam gesichert werden.

## Alpha-9-Härtung / Alpha 10.1

Die Altrouten aus Alpha 2–4 verwenden nun dieselbe Auth-/Rechte-/CSRF-Middleware wie neuere Module. Die selbst sichtbare Mitgliederprofilroute verwendet das niedrigere Routenrecht `members.self`; die eigentliche Eigentums- und Mitarbeitendenprüfung verbleibt im Controller.

`Install/SchemaInspector.php` vergleicht bestehende Tabellen mit den sieben SQL-Migrationsdateien und verweigert bei Abweichungen eine unbemerkte Einrichtung. `bin/schema-check.php` verändert selbst keine Daten.

`Receipts/StoragePolicy.php` erzwingt einen realen Speicherort unter `storage/` (nicht im Webroot, keine Symlinks). Weitere Freigabebedingungen: [ALPHA9_HARDENING.md](ALPHA9_HARDENING.md).

Die Kontopflege aus Alpha 10.1 arbeitet unabhängig von der Mitgliederdomäne in `app/Accounts`. Sie überprüft das bisherige Passwort bei jeder Änderung und erneuert nach Passwortwechsel die Sitzung. Die Authentifizierung bindet Sessions an den beobachteten Passwort-Hash, um ältere angemeldete Sitzungen nach Passwortwechsel zu widerrufen.

## Alpha 10.2 – Konfiguration und Audit

`SystemSettingsController` → `SettingsService` (Feld-Whitelist/Validierung) →
`SettingsRepository`/`PdoSettingsRepository` (InnoDB, CAS-Revision und
transaktionales, nur anhängbares Änderungsjournal). Anonyme und nichtadministrative
Aufrufe werden durch Route-Middleware abgewehrt, POST benötigt einen CSRF-Token.
Keine SMTP-Passwörter im Settings-Speicher, kein Mailversand. Neue Migration `008_system_settings.sql`.
