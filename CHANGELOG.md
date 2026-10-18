# Vereinssystem – Änderungsprotokoll

## 2.3.1 – 18.10.2026

### Behoben
- Interne Versionsanzeige berichtete fälschlich 2.1.0 statt des aktuellen Stands.
- Unsicheres Rückgängigmachen von SQL-Escaping in historischen Veranstaltungsabfragen entfernt.
- In historischen Nachrichten unerlaubte HTML-Attribute, Script- und Eventhandler-Ausführung unterbunden.
- SQL-Debugausgabe auf der Nachrichtenübersicht entfernt; Datenbankfehler des Cronjobs werden ins Serverlog geschrieben.
- Dashboard mit fehlender Kuchenleistungs-Konfiguration stürzt nicht mehr wegen Division durch null ab.
- Alte Seitennavigation ist auch mit ungünstigem `page`-Parameter definiert.
- Cronjob im Browser startet nur noch per ausdrücklichem Admin-POST; planmäßiger CLI-Cron bleibt ausführbar.

### Geändert
- Suche bei Mitgliedern, Nachrichten und Abteilungsmitgliedern auf gebundene PDO-Parameter umgestellt; mehrere Detailabfragen ebenfalls.
- E-Mail-Absenderfelder im Cronjob von Steuerzeichen bereinigt, Vereinsname im HTML-Kontext escaped.
- Rechtliche Herkunft und vorhandene Vereinsdaten bleiben unangetastet; kein neues Datenbankschema notwendig.
- PHP-8-Syntax, Quellkatalog und Dokumentation aktualisiert; Quell-/Sicherheitsregeln und gesonderte Praxisabnahme ergänzt.
- README beschreibt künftig die Anwendung ohne wiederkehrende Hinweise zur Entwicklungs-/Testumgebung.

### Nachfolgende Modernisierung
- Historische `db_escape()`-SQL-Builder und der alte MySQL-SQL-Mode werden weiterhin schrittweise durch vollständig parametrisierte, strikt SQL-konforme Abfragen ersetzt. Siehe `docs/PDO_MIGRATION.md`.


## 2.3.0 – 18.10.2026

**Gemeinsamer Release der ursprünglich geplanten Ausbaustufen 2.2.0 und 2.3.0.** Es wird keine separate Zwischenversion 2.2.0 benötigt.

### Neu

- Frei anlegbare Kassenkonten beliebigen Typs (Bargeld, Bank, PayPal usw.), mit Anfangsbestand, Eröffnungsdatum und Aktivstatus.
- Einnahmen, Ausgaben, über zwei Konten verknüpfte atomare Umbuchungen sowie Jahresendbestände je Konto.
- Beitragszahlungen aus 2.1.0 können jetzt unmittelbar dem tatsächlich verwendeten Kassenkonto zugeordnet werden – **ohne Doppelbuchung**. Stornos sind gekoppelt.
- Belegnummern, PDF-Buchungsnachweise, Kategorie- und Kontenauswertung als PDF-Jahresbericht sowie CSV-Einzelbuchungsexport.
- PDF-/Bildbelege per geschütztem Upload/Download mit Content-Type-, Hash- und Pfadprüfung (privater Speicher **außerhalb** des Webroots erforderlich; absolute Linux-/Windows-Pfade unterstützt).
- Zweistufiger CSV-Import mit Dateiprüfung, Vorschau, Bestätigung und SHA-256-Duplikatssperre.
- Granulare Kassenrechte (Buchen, Storno, Konten, Import, Export, Belege, Audit) mit Administrator-Vergabe und vorhandenen Altrechten weiterhin intakt.
- Prüfspur über `vs_audit` für Kassenänderungen, Rechte, Beitragssätze und gekoppelte Zahlungsvorgänge.
- Beitragssätze mit Gültigkeitsbeginn für Erwachsene/Kinder und Abteilungsumlagen; bestehende Sollstellungen werden nicht rückwirkend überschrieben.
- Additive 2.3.0-Migration (7 Tabellen, zusätzlich zu 2.1.0) und Erstinstaller mit insgesamt **18 Tabellen**.
- Responsive Kassenoberflächen, zusätzliche `css/cash.css` und Navigationslink.

### Geändert

- README mit vollständiger Funktionsvorstellung, Installations-/Update-/Bedienungsanleitung und Lizenzherkunft überarbeitet.
- Vollständiger PHP-Dateikatalog und Architektur-/Sicherheits-/Migrationsdokumentation ergänzt (`docs/`).
- Alle PHP-Quelldateien auf LF-Zeilenenden und ohne nachfolgende Leerzeichen standardisiert; vorhandene Modulkommentare erhalten. Neue Komponenten dokumentiert. **Historische Inline-HTML-Mischmodule nicht risikoreich blind umformatiert.**
- Finanzbuchhaltung mit Cash-Transaktionen verknüpft und Beitragsberechnung um tarifdatierte Werte ergänzt.
- Regressionstests auf 18 Tabellen angepasst; zusätzliche Kassen- und PDF-Tests.

### Einschränkungen

- **Keine vollständige MySQL-/MariaDB-Liveintegration in dieser Entwicklungsumgebung**, da keine passende Datenbank und `pdo_mysql` bereitstanden. PHP-8.4-Syntaxprüfungen und datenbankfreie Tests ausgeführt.
- Historische Mitgliederzahlungen werden nicht automatisch einem neuen Kassenkonto zugewiesen; es erfolgt keine fiktive Kontoaufwertung.
- Keine Bankkonto-Anbindung, kein SEPA-Einzug, keine revisionssichere GoBD-/Steuerbuchhaltung und keine PDF-Signatur.
- Hochgeladene Dokumente benötigen einen außerhalb des Webroots konfigurierten Speicherpfad.
- Rechte und SQL-Logik historischer Module werden nicht vollständig durch das neue Rollensystem ersetzt.

## 2.1.0 – 18.10.2026

- Beitragsforderungen, Zahlungen, Teilzahlungen, automatische Zuordnung, Guthaben und Soll-/Ist-Jahresübersicht.
- Storno und drei zusätzliche Tabellen, wiederholbare Schema-Migration.
- Erstinstaller auf elf Tabellen und neue responsive Finanzmasken erweitert.

## 2.0.1 – 18.10.2026

- Fehlende PDO-Dateien, Sitzungsverwaltung, SQL-Schema und lokales jQuery ergänzt.
- Wertebereichsvalidierung verbessert und Repository-Struktur vervollständigt.

## 2.0.0 – 17.10.2026

- Migration der Originalanwendung auf PHP 8/PDO, geführte Installation, Passwortmigration und CSRF-/Session-Schutz.

## 2012/2013 – Originalfassung

- Vereinsverwaltung von Nicolas Jourdan und Sebastian Fuss: Mitglieder, Abteilungen, Veranstaltungen, Dienste, Nachrichten, E-Mail-Erinnerungen.
- Historische kostenlose, nichtkommerzielle Nutzungserlaubnis bleibt bestehen.
