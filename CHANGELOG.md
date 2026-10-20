# Änderungsprotokoll

## 3.0.0-alpha.9 – 20.10.2026

### Jahresberichte, Belege und Exporte
- Unbegrenzte Jahresauswertungen mit nach Kalenderjahren abgegrenztem Anfangs-/Endbestand, auch für unterjährig eröffnete und inaktive Kassenkonten. Stornos bleiben im Journal, aber nicht in Salden.
- CSV-Export (UTF-8, Semikolon, Excel-Formelschutz) und mehrseitiger PDF-Kassenbericht ohne externe Bibliotheken.
- Private Belegverwaltung: PDF/JPEG/PNG/WebP bis 8 MiB, zufällige interne Dateinamen, SHA-256-Integritätsprüfung, rollen- und CSRF-geschützter Upload.
- Metadaten in der historischen `vs_belege`-Tabelle, unveränderte `vs_kassenbuch`-Buchungsregeln.
- Installer um Migration `007_cash_receipts.sql` auf 16 Tabellen erweitert; `bin/migrate-receipts.php` ergänzt.
- Neue Reports-/Receipts-Models, Interfaces, PDO-Repositories, Services, Controller und mobile Ansichten.
- Neue Dokumentation `docs/REPORTS_RECEIPTS.md` und eigenständige Fachprüfungen.
- `master` wird nicht verändert.


## 3.0.0-alpha.8 – 20.10.2026

### Neue Kassenverwaltung
- Eigenständige MVC-Kasse mit frei anlegbaren, frei benennbaren Konten (Bargeld/Bank/PayPal/weitere), Anfangsbeständen und Aktivstatus.
- Native PDO-Buchungen für Einnahmen, Ausgaben, atomare Umbuchungspaare und nachvollziehbare Stornos mit Grund, Zeit und Akteur.
- Verbindung zur Beitragsbuchhaltung: Zahlung, Forderungszuordnung, Kassenbuchung und Audit werden zusammen transaktional gespeichert oder zurückgerollt; Zahlungen und verknüpfte Kassenbuchungen werden ebenfalls gemeinsam storniert.
- Drei additive, historisch kompatible Tabellen (`vs_konten`, `vs_kassenbuch`, `vs_audit`) in `006_cash.sql`, CLI `bin/migrate-cash.php` sowie erweiterter Installer mit 15 Tabellen.
- Echte `CashAccount`-/`CashEntry`-Models, `CashService`, `CashRepository`, `PdoCashRepository`, `CashController`, geschützte Routen und ein responsives Kassenjournal.
- Rollentrennung: Vorstand kann buchen, Administration verwaltet Konten und Stornos, sonst kein Zugriff.
- Dokumentation `docs/CASHBOOK.md` und fachliche Prüfungen in `tests/cash_smoke.php`.

### Bereinigt und abgegrenzt
- Doppelten Navigationspunkt „Beiträge“ entfernt; Kasse erhält einen eigenen Menüpunkt.
- Belegnummern sind textuell möglich. Beleg-Uploads, Bankimporte, SEPA und Jahres-PDF sind Folgephasen.
- Der historische `master`-Branch bleibt unverändert.


## 3.0.0-alpha.7 – 20.10.2026

### Neue Finanzdomäne
- Eigenständige MVC-Beitragsbuchhaltung mit Models `ContributionRate`, `Claim`, `Payment`, `FinanceService`, `FinanceRepository`, `PdoFinanceRepository` und `FinanceController`.
- Soll-/Ist-Konto pro Mitglied und Jahr, Teilzahlungen, historische Zahlungs-/Forderungslisten, periodenübergreifendes Guthaben und offene Restbeträge.
- Dokumentierte Monatstarife (Kinder, Erwachsene, Abteilungen), idempotente Monats-Sollstellung und manuelle Forderungen.
- Zahlungen nach Fälligkeit automatisch zugeordnet; Guthaben später erneut anrechenbar; Storno ohne Datenlöschung und mit Transaktionssperren.
- Getrennte Finanzberechtigungen, geschützte POST-Routen und CSRF-Middleware.
- Mobile-First-Karten und Formulare, Administrator-Tarifverwaltung, Navigationslink.
- Additive Migration `005_finance.sql` mit vier bestehenden `master`-Tabellennamen; CLI `bin/migrate-finance.php`; Browser-Installer erweitert.
- Dokumentation `docs/FINANCE.md`, neue Finanzprüfungen.

### Abgrenzung
- Noch keine neue Kassenkontenverwaltung, Belege, Bankimporte oder SEPA-Abwicklung. Bereits mit der alten Kasse verknüpfte Zahlungen können in alpha.7 nicht isoliert storniert werden.


## 3.0.0-alpha.6 – 20.10.2026

### Neu
- Nachrichtenverwaltung als eigenständiges MVC-Modul mit `NewsPost`, `NewsService`, `NewsRepository`, `PdoNewsRepository` und `NewsController`.
- Beiträge mit Veröffentlichungsstufe, Autorenbindung, Datum, Suchfunktion, responsiver Übersicht und Volltextdarstellung.
- Nachrichten bearbeiten und archivieren; Administratoren erhalten Archivansicht und Wiederherstellung.
- Serverseitige Freigabe nach Rechtestufe, Autorenrechte, CSRF- und Rollenmiddleware.
- Alte Nachrichten aus der historischen `news`-Tabelle werden übernommen; Nutzer-HTML wird nie ungefiltert ausgeführt.
- Additive SQL-Migration `004_news.sql`, CLI-Befehl `bin/migrate-news.php` und Erweiterung des Erstinstallers auf **acht Tabellen**.
- Dokumentation in `docs/NEWS.md` und separate Fachprüfungen in `tests/news_smoke.php`.

### Geändert
- Nachrichten-Link im gemeinsamen Mobile-First-Menü, zentrale UI-Stile und Konfiguration auf alpha.6.
- Domain- und Berechtigungsregeln konsequent in Model, Service, Repository und Middleware getrennt.
- `master` bleibt auf dem bisherigen PHP-Release; kein Legacy-Routing im MVC-Branch.


## 3.0.0-alpha.5 – 20.10.2026

### Neu
- MVC-Veranstaltungen mit Übersicht, Terminformularen, Ansprechpartnern und Orten.
- Frei definierbare Aufgaben mit Titel, Beschreibung, Datum/Uhrzeit, Einsatzort und mehrfacher Mitgliederauswahl.
- Echte OOP-Models (`Event`, `EventTask`, `TaskAssignment`), Services (`EventService`, `TaskService`) und PDO-Repository.
- Tatsächlich an die Routen angeschlossene Middleware für Anmeldung, Rollen und CSRF.
- Mehrfachzuteilung mit serverseitiger Prüfung aktiver Mitglieder und zeitlicher Überschneidungen.
- SQL-Migration `003_events_tasks.sql`, CLI-Update `bin/migrate-events.php` und Browser-Installer mit sieben Tabellen.
- Mobile-First-Aufgabenformular mit durchsuchbaren Mitgliedernamen, ohne JavaScript-Pflicht.

### Geändert
- Keine Standdienst-/Kuchen- oder Pflichtslot-Felder im neuen Fachmodul. Die historische Tabelle `dienste` bleibt unberührt.
- Dokumentation zu Model, Service, Repository und Middleware ergänzt.
- WAMP-Root-`.htaccess` bleibt erhalten; `master` bleibt unabhängig.


## 3.0.0-alpha.4 – 19.10.2026

### Neu
- Eigenständiges MVC-Modul Orte/Anschriften mit PDO, mobilen Views, Verwaltung und Rechteprüfung.
- Eigenständiges MVC-Modul Abteilungen mit aktiven/passiven Zugehörigkeiten, Umlagen in Cent, Status und mobiler Oberfläche.
- Neuer Browser-Installer `/install` für eine leere Datenbank mit erstem Administrator.
- Wiederholbare CLI-Migration für vorhandene Installationen.
- Root-`.htaccess` für WAMP-Verzeichnisse ohne `public` als DocumentRoot, mit Zugriffssperren für interne Ordner.
- Neue Berechtigungen und Anschriften-Dropdown im Mitgliederformular.

### Geändert
- MVC-Core und Routing umfassen die neuen Fachmodule; alte PHP-Endpunkte bleiben ausgeschlossen.
- Konfiguration meldet Version 3.0.0-alpha.4.

# Änderungsverlauf – MVC-Entwicklung

## 3.0.0-alpha.3 – Dashboard und Mitgliederverwaltung (18.10.2026)

### Neu
- Authentifiziertes MVC-Dashboard mit personalisierten Links und rollenabhängigen Mitgliederstatistiken.
- Mitglieder-Modul als strikt getrennte OOP-Komponenten: `Member`, `MemberRepository`, `PdoMemberRepository`, `MemberService` und `MemberController`.
- Mitgliederliste mit vorbereiteten PDO-Suchabfragen, sicheren LIKE-Wildcards, Filterung und serverseitig begrenzter Pagination.
- Mitgliedsprofile mit an Berechtigungen gebundenen internen Notizen, vollständige Neuanlage und bearbeitbare Stammdaten.
- Soft-Deaktivierung und Reaktivierung für Administratoren ohne physischen Datenverlust; Admin-Konten geschützt.
- Mobile Views für Dashboard, Suche, Profil und mehrteilige Formulare sowie neue UI-Komponenten für diese Ansichten.
- Fachmodultests mit in-memory Repositories, ohne Altcode-PHP-Endpunkte.

### Geändert
- Nach erfolgreicher Anmeldung zum Dashboard statt ausschließlich zur Zugangsseite weiterleiten.
- Rollenmatrix um Member-Lesen, Member-Bearbeiten, Erstellen und Statusverwaltung ergänzt.
- Projektdokumentation auf 3.0.0-alpha.3 angehoben.
- Keine Änderung am 2013er Datenbankschema; neue Mitglieder werden mit allen erforderlichen Nicht-NULL-Feldern geschrieben.

## 3.0.0-alpha.2 – Login, Logout und Rechtesystem

### Neu
- Neues OOP-Domänenobjekt `User`, zentrale Rollenregeln `Roles` und `AuthService`.
- `UserRepository`-Interface sowie PDO-Implementierung für das vorhandene `mitglieder`-Schema.
- HTTP-Routen `/login`, `/logout`, `/account`, `/rights` und `/admin/roles` mit eigenständigen MVC-Views.
- GET-/POST-Trennung, CSRF-geschützte Änderungen und Rechteprüfung auf dem Server.
- Rechtestufen 0–5, Zugangsverwaltung für Level 5; Selbstsperre eines Administrators ausgeschlossen.
- Passwortprüfung mit `password_verify()`; alte MD5-Passwörter werden erst bei erfolgreicher Anmeldung ersetzt.
- Dateibasierter Login-Schutz mit Sperrfrist nach wiederholten Fehlversuchen.
- Mobile-First-Komponenten für Zugangsformulare, Navigationsleisten und Rollenlisten.
- CLI-Bootstrap einer kompatiblen Benutzertabelle und eines ersten Admins für neue, leere Datenbanken.
- Isolierte OOP-Tests für Login, Logout, CSRF, Rollen, gesperrte Bereiche und Althash-Umstellung.

### Geändert
- Das MVC-Root führt nun zum Login beziehungsweise zum eigenen Zugang.
- Session-Lebenszyklus und Redirect-Antworten erweitert; PDO-Verbindung bleibt lazy.
- UI-Kit und Dokumentation auf alpha.2 ergänzt.

### Nicht Teil dieses Meilensteins
- Dashboard, Mitglieder-Stammdatenverwaltung und weitere ursprüngliche Vereinsmodule.

## 3.0.0-alpha.1 – Kernel
- Eigenständiger MVC-Kern ohne Legacy-Routing, neues UI-Kit, Router, Views, Session- und PDO-Basis.
