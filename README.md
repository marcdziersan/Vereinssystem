# Vereinssystem 2.1.0 – Vereinsverwaltung und Beitragsbuchhaltung

**Webbasierte Mitglieder- und Vereinsverwaltung für PHP 8 mit MySQL/MariaDB und PDO.**

Das Vereinssystem führt Mitglieder, Abteilungen, Veranstaltungen, Helferdienste, Nachrichten und Vereinsbeiträge an einer zentralen Stelle zusammen. Die Version **2.1.0** erweitert die bisherige reine Beitragsberechnung erstmals um **tatsächliche Zahlungsbuchungen mit Soll-/Ist-Abgleich**, Teilzahlungen, Rückständen, Guthaben und nachvollziehbare Stornos. Die Anwendung läuft auf eigenem Webspace ohne Node.js, SPA-Framework oder Cloud-Abonnement.

> **Teststand:** PHP-Syntax und datenbankfreie Smoke-Checks wurden ausgeführt. Ein vollständiger Browser- und Buchungstest mit produktionsnaher MySQL-/MariaDB-Instanz steht aus. Bei echten Mitgliederdaten – besonders bei Kindern und Bankdaten – zunächst ausschließlich eine gesicherte Testinstallation verwenden.

## Herkunft und Rechte

Das ursprüngliche **Vereinssystem** entstand 2012/2013 als Abiturprojekt von **Nicolas Jourdan und Sebastian Fuss**. Die Originalautoren erlaubten die **kostenlose Nutzung und Erweiterung für nichtkommerzielle Zwecke**. Diese Erlaubnis bleibt bestehen und wird nicht durch eine andere Lizenz ersetzt.

**Marcus Dziersan** führt die Anwendung seit 2026 technisch weiter. Die eigenen Erweiterungen werden, soweit die dafür notwendigen Rechte vorliegen, unter der **PolyForm Noncommercial License 1.0.0** bereitgestellt. Für fremde Programmteile, Bibliotheken, Icons und Schriftarten gelten deren jeweilige Rechte. Eine kommerzielle Verwendung des Gesamtprojekts ist **nicht pauschal erlaubt**. PolyForm Noncommercial ist aufgrund des Kommerzausschlusses keine OSI-Open-Source-Lizenz.

Lizenzinformationen: `LICENSE.md`, `LICENSE-POLYFORM-NONCOMMERCIAL.md`, `THIRD-PARTY-NOTICES.md` und das historische `docs/ORIGINAL-README-2013.md`.

## Funktionsumfang

| Bereich | Funktionsumfang |
| --- | --- |
| Mitglieder | Stammdaten, Eintritt, Status, familiäre Zuordnung, Benutzerkonto |
| Abteilungen | Verwaltung, Mitgliedszugehörigkeit, Aktivenumlagen |
| Anschriften | Orts- und Adressverwaltung |
| Veranstaltungen | Veranstaltungen und Helferdienste, Kuchen- und Dienstplanung |
| Nachrichten | Vereinsinterne Mitteilungen |
| Finanzen 2.1 | Monats-Sollstellungen, echte Zahlungsbuchungen, automatische Zuordnung, Restschuld, Guthaben, Teilzahlungen, Storno, Jahresübersicht |
| Benutzerrechte | Klassische Stufen 1–5 (Finanzbuchungen ab Level 4, Sollstellung und Forderungsstorno nur ab Level 5) |
| Hintergrundaufgaben | Erinnerungs-E-Mails und historische Diensteabrechnungen über Cronjob |

**Nicht enthalten (geplant für spätere Versionen):** mehrere frei anlegbare Kassen-/Bankkonten mit Umbuchungen und Kassenabschlüssen; SEPA-Lastschriftmandate; Bank-CSV-Import; ein vollständig aufgabenbasiertes Berechtigungsmodell; vollständige mobile Neugestaltung.

## Neu in 2.1.0 – Beitragsbuchhaltung

### So arbeitet die Buchhaltung

1. **Sollstellungen erzeugen:** Berechtigte Administratoren öffnen **Finanzen** und wählen einen Beitragsmonat. Die Anwendung legt für aktive Mitglieder mit Eintritt spätestens am Monatsende Monatsbeiträge sowie aktuell aktive Abteilungsumlagen an. Ruhende Mitglieder (`mitgliedschaft=2`) werden nicht automatisch abgerechnet. Für die Einstufung Kind/Erwachsener zählt das Alter **am Monatsersten**. Eintritt im Lauf des Monats löst den vollen Monatsbeitrag aus.
2. **Zahlung buchen:** Im Beitragskonto eines Mitglieds können Vorstand/Verwaltung ab Level 4 einen Betrag, Zahlungsdatum, Zahlungsweg, Zahler, Verwendungszweck und Bemerkung eintragen. Zahlungen können dem Vereinsmitglied zugeordnet werden, auch wenn zum Beispiel ein Elternteil tatsächlich überwiesen hat.
3. **Automatisch zuordnen:** Der Betrag begleicht auf Wunsch zuerst eine bestimmte offene Forderung, ansonsten die ältesten offenen Beiträge zuerst. Ein Zahlungseingang kann mehrere Monate ausgleichen. Nicht zugeordnete Restbeträge bleiben als **Guthaben** erhalten und können später mit neuen Forderungen verrechnet werden.
4. **Soll/Ist ansehen:** Mitglieder sehen ausschließlich das eigene Beitragskonto. Benutzer ab Level 4 können andere Konten und die Verwaltungsübersicht öffnen. Offene Beiträge = Soll minus zugeordnete aktive Zahlungen. Zahlungen ohne Zuordnung sind **Guthaben**, nicht Ist.
5. **Korrigieren:** Falsche Zahlungen lassen sich nur mit Stornogrund stornieren; Datensatz, Datum und Bearbeiter bleiben gespeichert. Sollstellungen können nur dann storniert werden, wenn keine aktive Zahlung darauf verrechnet wurde. Stornierte Zahlungen machen die betroffenen Forderungen wieder offen.

### Historische Daten und wichtige Grenzen

- Beiträge werden bei der Monatserstellung **als feste Forderungen gespeichert**. Spätere Beitragssatzänderungen verändern diese Forderungen nicht. Für alte Monate gibt es keine vollständigen historischen Beitrags-/Zugehörigkeitsverläufe; zurückdatierte Sollstellungen verwenden deshalb **aktuelle** Stammdaten. Vor einer historischen Nachberechnung unbedingt prüfen.
- Die Sollstellung ist **idempotent**: bereits erstellte Monatspositionen werden bei erneutem Durchlauf nicht dupliziert oder überschrieben. Einmal stornierte Positionen bleiben aus Nachvollziehbarkeitsgründen historisch erhalten und werden nicht unbemerkt wiederhergestellt.
- Beträge werden ausschließlich als **ganze Cent** verarbeitet. Die Datenbank führt drei neue Tabellen mit Referenzen zwischen Forderungen, Zahlungen und Zuordnungen.
- Es werden **keine** Zahlungsdaten von einer Bank automatisch abgerufen. Eine erfasste Zahlung ist eine manuelle Buchung, **kein Beweis** für einen tatsächlichen Geldeingang.
- Abteilungsmitglieder mit geänderter Zugehörigkeit werden für frühere Monate nicht automatisch rückwirkend korrigiert.
- Guthaben ist gesamtbezogen; die Jahressicht der Zahlungen zeigt Zahlungen nach Zahlungsdatum, während Ist-Beträge alle Zahlungen berücksichtigt, die Forderungen **dieses Beitragsjahres** begleichen.

### Beispiel

Für Januar, Februar und März liegen je 15,00 € Soll vor. Eine Zahlung von 20,00 € begleicht Januar vollständig und Februar zu 5,00 €. Damit sind **45,00 € Soll**, **20,00 € Ist**, **25,00 € offen** und **0,00 € Guthaben** vorhanden. Eine weitere Zahlung von 30,00 € begleicht Februar und März, die übrigen 5,00 € werden als Guthaben vorgemerkt.

## Voraussetzungen

- PHP **8.0 oder neuer**, empfohlen 8.2–8.4; Erweiterung **PDO + pdo_mysql** (und optional `mbstring`, `gd` für CAPTCHA).
- MySQL 5.7+/8.x oder kompatible MariaDB mit InnoDB und `utf8mb4`.
- Bestehende (anfangs leere) Datenbank samt Nutzer; PHP muss während der Erstinstallation `inc/config.php` schreiben dürfen.
- Eigener Webspace oder lokale Umgebung (z. B. WAMP). Im öffentlichen Betrieb **HTTPS erforderlich**.

Die alten Module nutzen einen PDO-basierten Ergebnisadapter (`db_query` usw.). Noch nicht alle historischen dynamischen Abfragen nutzen gebundene Parameter. Der derzeitige PDO-Kompatibilitätsmodus (`sql_mode = NO_ENGINE_SUBSTITUTION`) muss langfristig ersetzt werden.

## Installation – neue Datenbank

1. Vollständiges Projekt auf den Webspace hochladen (zum Beispiel in den Ordner `/verein/`).
2. Eine leere MySQL-/MariaDB-Datenbank und einen Datenbanknutzer im Hosting-Panel erstellen.
3. `https://deine-domain.de/verein/install.php` öffnen, Verbindungsdaten und Verein eintragen.
4. Administrator mit gültiger E-Mail, Geburtsdatum und einem Passwort von mindestens 12 Zeichen anlegen.
5. Der Assistent legt **11 Tabellen** an (8 ursprüngliche + 3 neue Finanztabellen). Ein Admin (Rechtestufe 5) wird angelegt, sofern der Mitgliederbestand leer ist.
6. Über `login.php` anmelden, die Beitragswerte in den Einstellungen eintragen und erst anschließend eine Sollstellung anlegen.
7. **`install.php` löschen**, Konfigurationsdatei schützen und eine Datensicherung einrichten.

## Update von 2.0.x auf 2.1.0 – bestehende Daten erhalten

1. **Vorher Datenbank und Dateien vollständig sichern.** Bestehende `inc/config.php` nicht überschreiben.
2. Neue Projektdateien (ohne fremde `inc/config.php`) zunächst in einer Testumgebung hochladen.
3. Als Administrator (Rechtestufe **5**) anmelden und `upgrade.php` öffnen.
4. Auf **„Datenbankschema installieren / prüfen“** klicken: drei neue Tabellen werden ergänzt; bestehende acht Tabellen und Mitglieder werden nicht neu angelegt. Der Vorgang ist wiederholbar.
5. `finanzen.php` öffnen und Monatssollstellungen für einen **Testmonat** anlegen. Beiträge und Berechtigungen prüfen, danach Zahlung, Teilzahlung, Storno und Guthaben mit Testdaten ausprobieren.
6. Bei korrektem Verhalten die produktive Installation aktualisieren. **`upgrade.php` anschließend vom öffentlichen Webspace entfernen.**

Ohne diese Migration zeigt die neue Finanzverwaltung einen Hinweis statt Buchungsfunktionen. Das Upgrade verändert keine alten Beitragsdatensätze (sie wurden im Original nicht als Sollstellungen gespeichert) und **erzeugt keine rückwirkenden Forderungen automatisch**.

## Rollen und Datenschutz

- Level 1–3: eigenes Buchungskonto nur lesen (zusätzlich zu bisherigen Rechten).
- Level 4: fremde Mitgliederkonten ansehen, Zahlungseingänge erfassen/zuordnen und Zahlungen stornieren.
- Level 5: außerdem Monatsforderungen erzeugen, Forderungen stornieren und `upgrade.php` ausführen.

Finanzaktionen verwenden serverseitige Rechteprüfungen, CSRF-Tokens, eine einmalige Formular-Nonce zur Verringerung versehentlicher Doppelbuchungen und PDO-Parameter. Zuordnungen laufen innerhalb von Datenbanktransaktionen und sperren die jeweilige Mitgliedszeile. Das verhindert nicht sämtliche Fehlbedienungen: Buchungen müssen organisatorisch mit Kontoauszügen/Kassenbelegen abgeglichen werden.

Der Altbestand enthält potenziell personenbezogene Daten, Bankdaten und Angaben von Minderjährigen. Ein Rollentest, Datenschutzkonzept und ein Live-Sicherheitstest bleiben Voraussetzung für den echten Vereinsbetrieb. Siehe `SECURITY.md`.

## Projektstruktur

| Datei/Ordner | Aufgabe |
| --- | --- |
| `install.php` | Neue Datenbank mit 11 Tabellen einrichten |
| `upgrade.php` | Bestehende Version auf Finanzschema 2.1.0 ergänzen |
| `finanzen.php` | Gesamtübersicht und Monats-Sollstellung |
| `finanzkonto.php` | Mitgliedskonto, Soll/Ist, Zahlungen und Stornos |
| `finanzen_process.php` | Berechtigte POST-Buchungsaktionen |
| `inc/finance.php` | Finanzlogik, Transaktionen und Cent-Berechnung |
| `sql/finance_2_1_0.sql` | Idempotente Finanzmigration (3 Tabellen) |
| `sql/schema.sql` | Neuinstallation einschließlich Finanzschema |
| `css/finance.css` | Neue Finanzmasken (auch auf kleinen Bildschirmen bedienbar) |
| `inc/database.php`, `inc/connect.inc.php` | PDO-Kern und Einstellungen |
| `inc/checkuser.inc.php`, `inc/session.php` | Session, Anmeldung, CSRF |
| `member_*.php`, `abteilung_*.php`, `veranstaltung_*.php` | Bestehende Vereinsmodule |
| `tests/smoke.php`, `tests/finance_smoke.php` | Datenbankfreie Prüfungen |
| `CHANGELOG.md` | Historie der Änderungen |

## Versionen und Ausblick

| Version | Stand |
| --- | --- |
| **2012/2013** | Original-Vereinsverwaltung (Nicolas Jourdan / Sebastian Fuss) |
| **2.0.0** · 17.10.2026 | PHP-8-/PDO-Migration, neuer Installer |
| **2.0.1** · 18.10.2026 | Wartung, fehlende Kern-Dateien, Fehlerkorrekturen |
| **2.1.0** · 18.10.2026 | Forderungen, Zahlungen, Soll/Ist, Guthaben, Storno, Upgrade |
| **2.2.0** · geplant | Frei anlegbare Geldkonten, Umbuchungen und Kassenbuch |
| **2.3.0** · geplant | Berechtigungen, Im-/Export, Berichte und Belege |
| **2.4.0** · geplant | Umfangreiche responsive Modernisierung |

Anregungen und Fehlerberichte: [GitHub Issues](https://github.com/marcdziersan/Vereinssystem/issues). Bitte keine echten Bank-, Mitglieder- oder Zugangsdaten veröffentlichen.

**Nutzungsrecht:** kostenlose, nichtkommerzielle Nutzung und Erweiterung. Details in den Lizenzdokumenten. Für kommerzielle Nutzung ist die gesonderte Zustimmung sämtlicher betroffener Rechteinhaber erforderlich.