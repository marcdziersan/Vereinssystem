# Vereinssystem 2.4.0 – Mobile-First Vereinsverwaltung, Beiträge und Kassenführung

**Vereinssystem** ist eine klassische, selbst hostbare PHP-Vereinsverwaltung. Sie vereint Mitglieder, Abteilungen, Vereinsveranstaltungen, Helferdienste, Nachrichten, Beitragsforderungen und Zahlungseingänge mit einer mehrkontenfähigen Kassenverwaltung. Die Bedienung läuft im Browser. **Kein Node.js, kein React und kein verpflichtendes Cloud-Abonnement.**

**Version:** 2.4.0 (18. Oktober 2026) · **PHP:** ab 8.0 · **Datenbank:** MySQL/MariaDB via PDO · **Nutzung:** nichtkommerziell nach den unten erläuterten Herkunfts- und Lizenzbedingungen.


## Herkunft und Entwicklung

Das ursprüngliche Vereinssystem entstand **2012/2013** als Abiturprojekt von **Nicolas Jourdan und Sebastian Fuss**. Schon damals standen Mitglieder, Abteilungen, Veranstaltungen, Nachrichten und Vereinsdienste im Mittelpunkt. Der historische PHP-5-Code nutzte die später entfernten `mysql_*`-Funktionen. Die Originalautoren gestatteten die **kostenlose Nutzung und Erweiterung zu nichtkommerziellen Zwecken**.

**Marcus Dziersan** entwickelt die Anwendung seit **2026** weiter. Die Urheberschaft und Nutzungsbedingungen der ursprünglichen Autoren bleiben erhalten. Für neue eigene Beiträge, soweit die jeweiligen Rechte vorliegen, gilt **PolyForm Noncommercial 1.0.0**. Die Bedingungen von jQuery und anderen Drittkomponenten bleiben selbstständig gültig. Die Lizenzdokumente sind `LICENSE.md`, `LICENSE-POLYFORM-NONCOMMERCIAL.md`, `THIRD-PARTY-NOTICES.md` und `docs/ORIGINAL-README-2013.md`.

## Funktionen nach Version

| Bereich | Stand in 2.4.0 |
|---|---|
| Mitglieder und Benutzer | Stammdaten, Familienbeziehungen, Status, Login |
| Abteilungen | Zuordnungen, Abteilungsumlagen und Leitungen |
| Vereinsalltag | Orte, Veranstaltungen, Arbeits-/Helferdienste, Kuchenleistungen, Nachrichten |
| Beitragsbuchhaltung (2.1) | Monatsforderungen, Zahlungseingänge, Soll/Ist, Teilzahlungen, Guthaben, Rückstände, Storno |
| **Beliebige Kassenkonten (2.3)** | Frei benennbare Konten, z. B. Bargeld, Bank, PayPal, Spendenkasse, weitere |
| **Kassenbuch (2.3)** | Einnahmen, Ausgaben, Anfangsbestände, atomare Umbuchungen, nachvollziehbare Stornos |
| **Beitragsanbindung (2.3)** | Neue Beitragszahlungen gleichzeitig auf einem gewählten Kassenkonto erfassen; gemeinsames Storno |
| **Belege (2.3)** | Dokumentdateien sicher in einem explizit konfigurierten Speicher **außerhalb des Webroots** ablegen und herunterladen |
| **Berichte (2.3)** | CSV-Export, Jahres-PDF nach Konto und Kategorie, PDF-Buchungsbelege |
| **CSV-Import (2.3)** | Datei prüfen, Vorschau bestätigen, maximal 100 Positionen pro Import, Duplikaterkennung |
| **Berechtigungen (2.3)** | Eigene Kassenrechte zusätzlich zu den fünf bisherigen Rechtestufen |
| **Audit (2.3)** | Zeit, handelndes Mitglied, Objekt, Änderung und Stornogrund protokollieren |
| **Beitragssätze mit Datum (2.3)** | Kind-/Erwachsenentarife und Abteilungsumlagen ab frei wählbarem Stichtag |
| Responsive Darstellung | Neue Finanz-/Kassenmasken mobil nutzbar; Altmodule größtenteils noch im klassischen Layout |

### Kassenbeispiel

Ein Verein hat drei Konten: Vereinskonto **2.150,00 €**, Barkasse **385,50 €** und PayPal **310,00 €**. Der Gesamtbestand beträgt **2.845,50 €**. Eine Umbuchung von **100,00 €** von der Bank zur Kasse erfasst zwei korrespondierende Buchungen. Der Gesamtbestand bleibt **2.845,50 €**. Externe Einnahmen und Ausgaben erhöhen oder vermindern dagegen den Bestand.

### Beitragszahlungen im Kassenbuch

Eine Beitragszahlung über `finanzkonto.php` verlangt nach dem Upgrade ein Zielkonto. Die Zahlung wird in der Beitragsbuchhaltung verrechnet **und gleichzeitig** als Kasseneingang erfasst, nicht zweimal separat gebucht. Ein Storno im Beitragskonto storniert auch den verknüpften Kasseneingang in derselben Datenbanktransaktion.

**Bestehende Zahlungen aus 2.1.0 bleiben erhalten**, erhalten jedoch **keine erfundene rückwirkende Kontenzuordnung**. Frühere Zahlungseingänge müssen anhand der Originalbelege gesondert abgeglichen werden. Eine Bankdatei darf nicht ein zweites Mal als normale Einnahme importiert werden, wenn sie bereits über die Beitragsbuchhaltung verbucht ist.

## Mobile-First Bedienoberfläche seit 2.4.0

**Alle vorhandenen Bedienbereiche nutzen seit 2.4.0 dieselbe responsive Hülle.** Die ursprünglichen Vereinslogos bleiben erhalten; Layout, Navigation, Abstände, Formulare, Statusmeldungen und Bedienelemente wurden vereinheitlicht. Die bestehende PHP/PDO-Geschäftslogik sowie Mitglieder- und Finanzdaten bleiben unangetastet.

- **Anmeldung und Abmeldung:** neu gestaltete Loginseite mit eindeutig beschrifteten Feldern, Passwort-Anzeige-Umschalter und verständlichen Statusmeldungen; Abmeldung führt zurück zum Login.
- **Navigation:** zentrale Seitenleiste auf großen Bildschirmen, ausklappbares Menü und untere Schnellnavigation auf Smartphones. Menüpunkte bleiben berechtigungsabhängig.
- **Vereinsalltag:** Dashboard, Mitglieder, Abteilungen, Orte, Veranstaltungen/Dienste und Nachrichten in einem einheitlichen Layout. Die bestehenden PHP-Masken nutzen dieselben neuen Styles.
- **Verwaltung:** Beitragskonten, Kasse, CSV-Importe, Belege, Protokolle, Einstellungen und Berechtigungsseiten mit konsistenten Formularen und Tabellen.
- **Tabellen:** einfache Datentabellen erscheinen auf kleinen Bildschirmen als beschriftete Karten, komplexe Tabellen behalten eine horizontal scrollbar beschriftete Ansicht. JavaScript verändert **keine** Buchungsdaten.
- **Bedienbarkeit:** gut erreichbare Bedienelemente, sichtbarer Tastaturfokus, Skip-Link, Dialog-/Menüsteuerung per Escape und optionales Hell-/Dunkelschema (nur lokal gespeichert).
- **Installieren und Aktualisieren:** `install.php` und `upgrade.php` nutzen ein gemeinsames responsives Setup-Design.

Für Darstellung und Verhalten sind `css/app.css`, `css/setup.css`, `assets/js/app.js` und `inc/ui.php` zuständig. Weitere Einzelheiten und Pflegehinweise: [docs/UX_UI_2.4.0.md](docs/UX_UI_2.4.0.md).

### Update von 2.3.1 auf 2.4.0

1. Dateien und Datenbank sichern.
2. Neue Anwendung hochladen und die bestehende Datei `inc/config.php` behalten.
3. Browser-Cache für CSS/JavaScript neu laden; Asset-Version ist `2.4.0`.
4. Mit dem gewohnten Benutzerkonto anmelden und sämtliche Verwaltungsfunktionen weiterverwenden.
5. **Es sind keine neuen Datenbanktabellen erforderlich.** Bestehende Daten und Rollen bleiben erhalten.

[Ausführliche Upgrade-Anleitung](UPGRADE-2.4.0.md)

## Systemvoraussetzungen

- PHP **8.0 oder neuer**; empfohlen 8.2–8.4.
- PHP-Erweiterungen: `PDO`, **`pdo_mysql`**, `fileinfo`, `iconv`; `mbstring` empfohlen; `gd` nur für optionales Captcha.
- MySQL 5.7+/8.x oder kompatibles MariaDB mit **InnoDB** und **utf8mb4**.
- Eigener Webspace oder lokaler PHP-Server (z. B. WAMP), Schreibrechte bei der Installation.
- Öffentlich ausschließlich über **HTTPS** ausliefern.
- Optionaler privater Dateispeicher **außerhalb** des öffentlich erreichbaren Dokumentenstamms für Rechnungen und Kassenbelege.

Die älteren PHP-Module nutzen weiterhin den PDO-Ergebnisadapter (`db_query`, `db_fetch_assoc`, `db_escape`). Noch nicht alle alten dynamischen SQL-Ausdrücke sind auf Parameterbindung umgestellt. Neu entwickelte Finanz- und Kassenmodule verwenden vorbereitete PDO-Statements.

## Erstinstallation

1. Das vollständige Projekt als ZIP entpacken oder von [GitHub](https://github.com/marcdziersan/Vereinssystem) beziehen.
2. Projektdateien in den Ordner der Anwendung auf dem Webspace laden (etwa `/verein/`).
3. Beim Hoster eine **leere** MySQL-/MariaDB-Datenbank mit Benutzer anlegen.
4. `https://domain.example/verein/install.php` aufrufen, Datenbankdaten und Vereinsnamen eingeben, Administratorkonto mit mindestens 12-stelligem Passwort einrichten.
5. Der Assistent richtet **18 Tabellen** ein und erstellt bei leerem Mitgliederbestand ein Administrator-Konto.
6. Über `login.php` anmelden, Grundeinstellungen und Beitragssätze prüfen, erst danach echte Sollstellungen erzeugen.
7. In **Kasse → Konto anlegen** zuerst beliebig viele Zahlungskonten mit den belegten Anfangsbeständen einrichten.
8. Nach der Einrichtung `install.php` entfernen; `inc/config.php` schützen und Backups aktivieren.

### Vorherige Schema-Updates (von 2.0.x, 2.1.0 oder 2.3.0)

**Unbedingt Datenbank UND Dateien sichern!**

1. Vor jedem Update Datenbank und Dateien sichern; bestehende `inc/config.php` behalten.
2. Als Administrator (Rechtelevel **5**) anmelden.
3. `upgrade.php` aufrufen, per POST die Datenbankmigration starten. Das Skript erzeugt nur fehlende Tabellen aus **2.1.0** (3 Tabellen) und **2.3.0** (7 Tabellen).
4. Kassenkonten mit korrektem **Anfangsdatum und Anfangsbestand** anlegen; bereits importierte oder manuell erfasste Zahlungen prüfen.
5. Mitglieder- und Finanzdaten kontrollieren und den neuen Stand anschließend freigeben.
6. Erst danach auf Produktivdaten umstellen und `upgrade.php` vom Server **löschen**.

**Keine rückwirkende automatische Kassenbuchung:** 2.1.0-Bestandszahlungen werden ausdrücklich nicht automatisch Konten zugeordnet. Das System löscht keine alten Finanzdatensätze. **Das Upgrade ist keine vollständige Migrationsengine für beliebig modifizierte Alt-Datenbankschemata.** Weitere Informationen: [UPGRADE-2.3.1.md](UPGRADE-2.3.1.md).

## Bedienung der neuen Kassenverwaltung

**Konten:** `kasse.php` öffnen, Konto mit beliebigem Namen, Typ, Eröffnungsdatum und belegtem Anfangsbestand anlegen. Konten lassen sich deaktivieren, ohne historische Buchungen zu löschen. Der Anfangsbestand ist **keine laufende Einnahme**.

**Einnahme / Ausgabe:** Konto, Art, Betrag, Datum, Kategorie, Beschreibung und optionale Belegnummer eingeben. Geldbeträge als `15,00` oder `15.00` eingeben (keine Tausenderpunkte). Jede Buchung erhält eine fortlaufende ID und eine Audit-Spur.

**Umbuchung:** Zwei verschiedene aktive Konten auswählen. Ein Ausgang und ein identisch hoher Eingang werden gemeinsam in einer Transaktion angelegt. Bei einer Stornierung werden **beide** Positionen storniert.

**PDF-Beleg:** `kasse.php → PDF-Quittung`. Es handelt sich um einen einfachen automatisch generierten Buchungsnachweis, **nicht** um eine rechtsverbindlich signierte Rechnung oder einen Nachweis für tatsächliche Bankbewegungen.

**Belegdokument hochladen:** `kasse.php → Anhänge`. Zulässig: PDF, JPEG, PNG und WebP, maximal 5 MB. Die Datei wird nur mit einer berechtigten Sitzung über `kasse_beleg.php` ausgeliefert.

**CSV-Import:** `kasse_import.php`, maximal 100 Buchungen pro Datei, Semikolon und UTF-8. Kopfzeile:

```csv
datum;art;betrag;kategorie;beschreibung;belegnummer
2026-10-18;einnahme;15,00;Spenden;Spende Vereinsfest;S-007
2026-10-18;ausgabe;24,50;Verpflegung;Getränke Vereinsabend;R-048
```

Datei prüfen → Vorschau vergleichen → Import bestätigen. Ein gespeicherter SHA-256-Schlüssel identifiziert wiederholt importierte Zeilen derselben Datei und desselben Zielkontos. **CSV-Import ist kein Bankabgleich** und ordnet nicht automatisch Mitgliedszahlungen zu.

**Jahresbericht:** `kasse.php → Jahresbericht PDF` umfasst Kontosalden, Einnahmen/Ausgaben, interne Umbuchungen und Kategorien. Der CSV-Export umfasst die Einzelbuchungen des gewählten Jahres (derzeit maximal 10.000 Zeilen). Ein Jahresabschluss mit Bilanzrecht oder gemeinnützigkeitsrechtlicher Bewertung wird dadurch nicht automatisch erstellt.

## Private Belegablage konfigurieren

Die Variable **`VS_PRIVATE_STORAGE_DIR`** muss auf ein beschreibbares **absolutes Verzeichnis außerhalb von `DOCUMENT_ROOT`** zeigen. Beispiel auf Linux-Webhosting:

```text
VS_PRIVATE_STORAGE_DIR=/home/hosting-account/private/vereinssystem-belege
```

Der genaue Weg zur Einrichtung hängt vom Hoster ab (PHP-FPM-/Webserver-Umgebung). Ohne diese Variable bleiben **Datei-Uploads und -Downloads deaktiviert**, damit Bankbelege nicht versehentlich öffentlich abgelegt werden. PDF-Buchungsbelege werden unabhängig davon aus der Datenbank erzeugt. Unter Windows/WAMP kann ein absoluter Pfad wie `C:\\Vereinssystem-Privat\\Belege` verwendet werden; dieser muss ebenfalls außerhalb des Webroots liegen.

## Rechte und Prüfung

Die bisherigen Stufen 1–5 bleiben bestehen. **Level 5** erhält sämtliche zusätzlichen Kassenrechte automatisch. Andere Mitglieder können gezielt die Rechte **Ansehen**, **Buchen**, **Konten bearbeiten**, **Storno**, **CSV-Import**, **Export**, **Belegzugriff**, **Protokolleinsicht** und **Beitragstarife** erhalten. Nur Level 5 kann Zusatzrechte vergeben oder entziehen. Rechte werden in `kasse_admin.php` verwaltet; alle neuen mutierenden Endpunkte prüfen das passende Recht serverseitig.

Beitragsbuchungen werden derzeit zusätzlich weiterhin über das historische Rechtelevel **4** freigegeben. Das bisherige gesamte Rechtesystem wurde bewusst **nicht** stillschweigend ersetzt. Ein vollständiges funktionsweises Rechtemodell für die Altmodule ist weiterer Entwicklungsbedarf.

Das Audit unter `kasse_protokoll.php` führt Aktionen mit Benutzer-ID, Zeit und Details. Es ist ein **Anwendungsprotokoll**, kein manipulationssicheres externes revisionsrechtliches Journalsystem. Backups, abgesicherte Serverzugriffe und unabhängige Kontrollen bleiben erforderlich.

## Historische Beitragstarife

Unter **Kasse → Rechte & Beiträge** lassen sich neue Kind-/Erwachsenenbeiträge und Abteilungsumlagen mit Gültigkeitsbeginn erfassen. Bei **neuen** Monats-Sollstellungen wählt die Anwendung den zum Monatsersten gültigen Eintrag. Vor dem ersten definierten Stichtag gelten die bisherigen Beitragseinstellungen. Bereits erzeugte Forderungen werden **niemals automatisch überschrieben**. Frühere Abteilungszugehörigkeiten und Beitrittsstatus sind im alten Modell nicht vollständig historisiert.

## Quellcode und Verzeichnisaufbau

| Datei/Ordner | Aufgabe |
|---|---|
| `inc/database.php`, `inc/connect.inc.php` | Zentraler PDO-Adapter und Einstellungen |
| `inc/finance.php` | Mitgliedsbeitrags-/Forderungs- und Zahlungskern |
| `inc/cash.php` | Konten, Buchungen, Umbuchungen, Rechte, Audit, Tarife |
| `inc/pdf.php` | Einfacher PDF-Renderer ohne Composer/Node-Abhängigkeit |
| `finanzen.php`, `finanzkonto.php` | Beitragsübersicht und Zahlungseingabe |
| `kasse.php`, `kasse_process.php` | Kassenmasken und geschützte Schreibaktionen |
| `kasse_admin.php`, `kasse_protokoll.php` | Berechtigung, Tarifhistorie, Prüfspur |
| `kasse_import.php`, `kasse_export.php` | CSV-Import, CSV-/PDF-Export |
| `kasse_beleg.php` | Private PDF-/Bildbelege |
| `sql/schema.sql` | Gesamtschema für Erstinstallation: 18 Tabellen |
| `sql/finance_2_1_0.sql`, `sql/finance_2_3_0.sql` | Additive, wiederholbare Migrationen |
| `install.php`, `upgrade.php` | Erstinstallation und Versionsupdate |
| `docs/ARCHITECTURE.md` | Architektur, Geldflüsse und Schema |
| `docs/SOURCE_REFERENCE.md` | Vollständiger PHP-Datei-/Funktionskatalog |
| `docs/USER_GUIDE.md`, `UPGRADE-2.4.0.md` | Bedienung und UI-Upgrade |
| `inc/ui.php`, `inc/head.inc.php`, `inc/footer.inc.php` | Gemeinsame responsive Seitenstruktur |
| `css/app.css`, `css/setup.css`, `assets/js/app.js` | Designsystem, Setup und Interaktion |
| `docs/UX_UI_2.4.0.md` | Mobile UX, Breakpoints und Oberflächenstruktur |
| `inc/security.php` | Sichere HTML-Darstellung historischer Nachrichten |

Die Quelldateien sind im [Modul- und Funktionskatalog](docs/SOURCE_REFERENCE.md) verzeichnet. Neue Module nutzen dokumentierte Schnittstellen und einheitliche Formatierungsregeln gemäß [CODE_STYLE.md](docs/CODE_STYLE.md). Historische gemischte PHP/HTML-Templates behalten ihre gewachsene Struktur, bis sie funktional modernisiert werden.

## Versionsübersicht

| Version | Inhalt |
|---|---|
| Original (2012/2013) | Grundlegende Vereinsverwaltung von Nicolas Jourdan und Sebastian Fuss |
| 2.0.0 / 2.0.1 (2026) | PHP 8, PDO, Installer, Session-/Validierungskorrekturen |
| 2.1.0 (2026) | Sollstellungen, Zahlungen, Teilzahlungen, Guthaben, Rückstände |
| **2.3.0 (2026)** | Gemeinsamer Ausbau der geplanten **2.2.0 und 2.3.0**: Kasse, Rechte, Berichte, Import, Belege, Audit, Stichtagstarife |
| **2.3.1 (2026)** | Codehärtung, PDO-Suchabfragen, Schutz für Nachrichten-HTML und Cron-POST, Quellcodedokumentation |
| **2.4.0 (2026)** | Mobile-first UX/UI für Anmeldung, Verwaltungs- und Finanzseiten, Navigation, Formulare, Tabellen und Setup |

Die beiden zuvor geplanten Versionsschritte **2.2.0 und 2.3.0 wurden in einem gemeinsamen Release 2.3.0 zusammengefasst**. Es ist keine eigenständig veröffentlichte Zwischenversion 2.2.0 erforderlich.

## Sicherheit und Grenzen

Dies ist eine modernisierte Altanwendung, **kein abschließend sicherheitsauditiertes Vereinsprodukt**. Historische Masken verwenden teilweise noch den dokumentierten Datenbank-Kompatibilitätsmodus. Für die neuen Finanzmasken werden Prepared Statements, CSRF- und Rechteprüfungen, Sessions und Datenbanktransaktionen eingesetzt; CSV-Daten und Belege sind vertraulich. Hinweise in `SECURITY.md` und `docs/ARCHITECTURE.md` beachten.

Keine Bank- oder Steuerdaten als Beweis für eine tatsächliche Transaktion interpretieren, bevor eine Prüfung anhand von Belegen/Kontoauszügen erfolgt ist.

## Lizenz, Urheber und Mitwirkung

Ursprüngliche Urheber: **Nicolas Jourdan und Sebastian Fuss** (2012/2013). Weiterentwicklung: **Marcus Dziersan** (seit 2026). Die ursprüngliche kostenlose, nichtkommerzielle Nutzungserlaubnis bleibt unverändert. Eigene neue Beiträge stehen im Umfang der eigenen Rechte unter **PolyForm Noncommercial 1.0.0**. Drittanbieterbestandteile haben unabhängige Lizenzen. Eine kommerzielle Nutzung des Gesamtprojekts ist **nicht pauschal erlaubt**. Aufgrund des Kommerzausschlusses handelt es sich nicht um OSI-konform lizenzierte Open-Source-Software.

Fehlermeldungen und Verbesserungen: [GitHub Issues](https://github.com/marcdziersan/Vereinssystem/issues). **Keine personenbezogenen Mitglieder-, Kinder- oder Bankdaten in Issues veröffentlichen.**
