# MVC-Architektur – 3.0.0-alpha.3

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

Der Webroot ist ausschließlich `public/`. Sessions und Login-Versuchsdateien liegen geschützt auf dem Server. Formulare erfordern CSRF-Prüfung. Zugriffsschutz wird im Controller geprüft, nicht allein durch verborgene Navigation.

## Datenmodell

Alpha.3 verwendet für Anmeldung und Mitgliederverwaltung jeweils sorgfältig ausgewählte Spalten der historisch vorhandenen Tabelle `mitglieder`. Für eine **neue** Datenbank kann `bin/bootstrap-auth.php` die kompatible Tabelle initialisieren, ohne die übrigen 17 Tabellen der alten Anwendung einzurichten. Die alten Fachmodule werden nicht übernommen.

## Frontend

CSS-Komponenten mit `vs-`-Prefix, zentrales Token-System, progressive Verbesserungen durch Vanilla-JavaScript. Das ursprüngliche Vereinslogo bleibt enthalten. jQuery ist optional verfügbar, jQuery Mobile und Bootstrap sind nicht nötig.

## Nächster Meilenstein

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
