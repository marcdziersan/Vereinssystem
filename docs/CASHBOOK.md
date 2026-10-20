# Kassenverwaltung – MVC 3.0.0-alpha.8

## Ziel und Architektur

Die Kassenverwaltung ist ein eigenständiges Modul, aber **kein zweites Beitragsbuchhaltungssystem**. Es übernimmt die Tabellennamen und Buchungsarten der historischen Version 2.4.0, führt das Kassenjournal jedoch ausschließlich über die OOP-/MVC-Schichten:

- `app/Models/CashAccount.php` und `CashEntry.php`: unveränderliche, centgenaue Datenobjekte für die Ausgabe.
- `app/Cash/CashRepository.php`: fachlicher Vertrag, ohne PDO-Abhängigkeit.
- `app/Cash/PdoCashRepository.php`: native parametrisierte SQL-Befehle, InnoDB-Transaktionen und Zeilensperren.
- `app/Services/CashService.php`: Prüfung von Konten, Datumsfeldern, Textlängen, Buchungsarten, Beträgen und Stornogründen.
- `app/Controllers/CashController.php`: HTTP-Eingaben, Statusmeldungen, Weiterleitungen und Template-Daten.
- `routes/web.php`: Authentifizierungs-, Berechtigungs- und CSRF-Middleware für **jede** Kassenroute.
- `resources/views/cash/index.php` und `public/assets/css/vs-ui.css`: mobile Kartenansichten, zugängliche Formulare und Journal.

### Konten und Salden

Konten sind frei benennbar (z. B. Bargeld, Bankkonto, PayPal oder weitere). Der Typ ist ebenfalls frei. Anfangsbestand und Eröffnungsdatum bleiben nach der Anlage unverändert; Name, Typ und Aktivstatus können von Administratoren angepasst werden. Deaktivierte Konten bleiben im Journal und in Gesamtsalden sichtbar, können aber keine neuen Buchungen aufnehmen.

Alle Beträge liegen als ganzzahlige **Centwerte** vor. Der aktuelle Kontostand ist:

`Anfangsbestand + Einnahmen + Umbuchung_ein − Ausgaben − Umbuchung_aus`.

Berücksichtigt werden nur aktive (nicht stornierte) Buchungssätze; der ursprüngliche Datensatz einer Stornierung bleibt jedoch vorhanden. Das Filtern nach Jahr betrifft ausschließlich die Journalansicht, nicht den fortgeschriebenen Kontostand.

### Umbuchung

Eine Umbuchung zwischen zwei unterschiedlichen, aktiven Konten erzeugt **zwei** gleich hohe Datensätze mit demselben kryptografisch zufälligen `umbuchung_key`: einen Ausgang auf dem Quellkonto und einen Eingang auf dem Zielkonto. Beide entstehen in **einer** InnoDB-Transaktion oder gar nicht. Werden Umbuchungen storniert, müssen beide passenden Buchungssätze gemeinsam storniert werden; beschädigte beziehungsweise nur teilweise stornierte Paare werden abgewiesen.

### Beitragszahlung und Kasse

Bei einem neuen Zahlungseingang im Beitragskonto ist ein Kassenkonto auszuwählen. Der Controller ruft `CashService::contribution()` auf. `PdoCashRepository` startet eine Transaktion; `PdoFinanceRepository::receivePaymentInsideCurrentTransaction()` speichert die neue Zahlung und verteilt sie auf Forderungen, **ohne eine zweite Transaktion zu starten**. Anschließend wird der Kassenbeleg mit `zahlung_id` eingefügt und die Aktion auditiert. Erst danach folgt der Commit.

Tritt dabei ein Fehler auf (z. B. ungültiges Konto, doppelte `zahlung_id` oder Datenbankfehler), werden Beitrag, Zuordnungen, Kasseneintrag und Auditänderungen gemeinsam zurückgerollt. Der eindeutige Index auf `vs_kassenbuch.zahlung_id` verbietet eine zweite Kassenbuchung zur selben Beitragszahlung.

Bei einem Beitragsstorno werden zuerst das Mitglied und die betroffene Zahlung gesperrt. `CashService::voidContribution()` und das PDO-Repository stornieren die zugehörige Kassenbuchung und die Zahlung mit einer gemeinsamen Datenbanktransaktion. Bei alten, bisher nicht mit der Kasse verknüpften Zahlungen ist weiterhin ein Zahlungsstorno möglich. Eine verknüpfte Zahlung darf **nicht** ausschließlich im Kassenjournal storniert werden.

### Berechtigungen

| Rolle | Kassenbereich | Buchen/umbuchen | Konten verwalten | Stornieren |
|---|---|---|---|---|
| Mitglied (1), Moderator (2), Abteilungsleitung (3) | nein | nein | nein | nein |
| Vorstand (4) | ja | ja | nein | nein |
| Administration (5) | ja | ja | ja | ja |

Alle Änderungen erfolgen ausschließlich mit `POST` und gültigem CSRF-Token. Die Oberfläche ist keine Sicherheitsgrenze; die Middleware verhindert auch direkte POST-Versuche.

## Datenbank und Installation

`database/migrations/006_cash.sql` ergänzt drei Tabellen **ohne DROP/TRUNCATE**:

| Tabelle | Bedeutung |
|---|---|
| `vs_konten` | frei angelegte Konten, Anfangsbestand und Aktivstatus |
| `vs_kassenbuch` | Journal mit Beitragslink, Umbuchungsschlüssel und Stornofeldern |
| `vs_audit` | unveränderliches Aktionsprotokoll durch INSERTs |

Die Spalten und Indizes entsprechen dem Stand von `master` 2.4.0, sodass die Migration vorhandene Konten und Buchungen **nicht überschreibt**. `vs_belege` (private Dateianhänge), Banking-/CSV-Import und SEPA sind **noch nicht** Teil dieser Alpha. Es kann bereits eine Belegnummer als Text erfasst werden.

Eine neue Installation unter `http://localhost/verein/` richtet 15 Tabellen ein. Bei bestehenden alpha.7-Installationen: Dateien einspielen, Datenbank sichern, danach im WAMP-PHP-Terminal ausführen:

```sh
php bin/migrate-cash.php
```

**Wichtig:** Bei einer bislang alten `master`-Datenbank sollten vor einem produktiven Einsatz bestehende Kassenverknüpfungen, Auditdaten und Buchungssummen mit einer Datenbankkopie überprüft werden.

## Fachliche Beispiele

1. Zwei Konten Bank: 100,00 € und Bar: 50,00 € (beides Anfangsbestand) → 150,00 € gesamt.
2. Einnahme 20,00 € auf Bank und Ausgabe 10,00 € aus Bar → 160,00 € gesamt.
3. Umbuchung 35,00 € von Bank nach Bar → Bank 85,00 €, Bar 75,00 €, gesamt weiter 160,00 €.
4. Storno der Umbuchung → Bank 120,00 €, Bar 40,00 €, gesamt 160,00 €.
5. Beitragszahlung 15,00 € auf Bank → Finanzzahlung und Kassenbuchung atomar, Bank 135,00 €.
6. Gemeinsames Beitragsstorno → Zahlung und verknüpfter Kasseneintrag deaktiviert, Bank wieder 120,00 €.

`tests/cash_smoke.php` führt die genannten Regeln zusätzlich mit einem isolierten Repository und mit HTTP-/Rechteprüfungen aus.
