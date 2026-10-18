# Vereinssystem

**Vereinssystem 2.0.1 – Vereinsverwaltung mit PHP 8, PDO und MySQL/MariaDB**

Das **Vereinssystem** ist eine webbasierte Anwendung zur Verwaltung von Vereinen, ihren Mitgliedern und dem organisatorischen Vereinsalltag. Statt Mitgliederlisten, Abteilungszugehörigkeiten, Veranstaltungen und Helferdienste auf mehrere Dateien zu verteilen, werden die Informationen an einem zentralen Ort gepflegt. Der Zugriff erfolgt im Browser; ein eigener Desktop-Client ist nicht erforderlich.

Die Anwendung entstand ursprünglich als Schul- bzw. Abiturprojekt von **Nicolas Jourdan und Sebastian Fuss** (Projektzeit 2012, veröffentlichter Quellstand 2013). Seit 2026 wird die historische PHP/MySQL-Anwendung im Repository **[marcdziersan/Vereinssystem](https://github.com/marcdziersan/Vereinssystem)** technisch modernisiert und weiterentwickelt.

> **Projektstand:** Version **2.0.1** (18. Oktober 2026) ist eine Wartungs- und Migrationsversion. Der ursprüngliche Funktionsumfang wurde übernommen; die Oberfläche ist noch nicht vollständig responsiv und es steht noch eine vollständige Abnahme mit realer MySQL-/MariaDB-Instanz aus. Bei personenbezogenen Echtdaten sollte die Version erst nach Sicherheits- und Funktionstests verwendet werden.

**Kostenlos für nichtkommerzielle Zwecke nutzbar und erweiterbar.** Die Herkunftsbedingungen der Urheber bleiben bestehen; die eigenen Ergänzungen ab 2026 stehen unter **PolyForm Noncommercial 1.0.0**. Details unter [Lizenz und Urheberrecht](#lizenz-und-urheberrecht) und in [LICENSE.md](LICENSE.md). Dies ist wegen des Ausschlusses kommerzieller Nutzung **keine Open-Source-Lizenz im Sinne der OSI**.

## Inhalt

1. [Was kann das System?](#was-kann-das-system)
2. [Funktionsumfang und Grenzen](#funktionsumfang-und-grenzen)
3. [Benutzerrollen](#benutzerrollen)
4. [Herkunft und Weiterentwicklung](#herkunft-und-weiterentwicklung)
5. [Versionsgeschichte](#versionsgeschichte)
6. [Technik und Voraussetzungen](#technik-und-voraussetzungen)
7. [Neue Installation](#neue-installation)
8. [Updates und Übernahme alter Daten](#updates-und-übernahme-alter-daten)
9. [Hintergrundverarbeitung und E-Mails](#hintergrundverarbeitung-und-e-mails)
10. [Projektstruktur](#projektstruktur)
11. [Datenschutz und Sicherheit](#datenschutz-und-sicherheit)
12. [Geplante Weiterentwicklung](#geplante-weiterentwicklung)
13. [Mitwirken und Fehler melden](#mitwirken-und-fehler-melden)
14. [Lizenz und Urheberrecht](#lizenz-und-urheberrecht)

## Was kann das System?

Das Vereinssystem richtet sich vor allem an kleinere und mittlere Vereine, die Mitglieder und Abläufe selbst verwalten möchten. Der Quellcode kann auf einem eigenen PHP-Webspace oder einer lokalen Entwicklungsumgebung betrieben werden. Es gibt **keine verpflichtende externe Vereinsverwaltungs-Cloud**.

Beispiele aus dem bestehenden Funktionsumfang:

- Mitglieder anlegen, bearbeiten, suchen und mit Status, Eintrittsdatum sowie Kontaktdaten verwalten.
- Abteilungen verwalten und Mitglieder einschließlich aktiver Zugehörigkeiten zuordnen.
- Orte und Anschriften hinterlegen.
- Veranstaltungen planen und anzeigen.
- Helferdienste und Kuchen-/Arbeitsdienste organisieren und auswerten.
- Vereinsinterne Nachrichten und Informationen pflegen.
- Vereinsbeiträge und Abteilungsumlagen als **monatliche Soll-Beträge berechnen**.
- Persönliche Übersichten und Aktionen je nach Berechtigungsstufe anzeigen.
- Geburtstags- bzw. Mitgliedschaftsjubiläumsgrüße über einen Cronjob vorbereiten und versenden.

Die eigentliche Bedienoberfläche stammt zu großen Teilen noch aus dem Ursprungsprojekt. Eine umfassende mobile Neugestaltung wird erst für spätere Versionen geplant.

## Funktionsumfang und Grenzen

| Bereich | In 2.0.1 vorhanden | Einordnung |
| --- | --- | --- |
| Mitgliederverwaltung | Ja | Stammdaten, Mitgliedschaft, Status und Beziehungen |
| Abteilungen | Ja | Zuordnung von Mitgliedern und Aktivenumlagen |
| Orte und Anschriften | Ja | Verwaltungsmaske für Orts-/Adressdaten |
| Veranstaltungen | Ja | Übersicht, Bearbeitung und Anzeige |
| Dienste und Helferplanung | Ja | Arbeits-/Standdienste und Kuchenleistungen |
| Nachrichten | Ja | Vereinsinterne Mitteilungen |
| Beitragsberechnung | Ja | Monatsbeitrag und Umlagen, **keine Zahlungserfassung** |
| Tatsächliche Beitragsbuchungen | **Nein** | Kein Zahlungsjournal, keine Zuordnung von Zahlungen |
| Soll-/Ist-Auswertung | **Nein** | Geplant für eine spätere Beitragsverwaltung |
| Rückstände, Teilzahlungen, Guthaben | **Nein** | Nicht Bestandteil der Version 2.0.1 |
| Vollständiges Kassenbuch | **Nein** | Geplant, nicht implementiert |
| Rollen und Rechtestufen | Ja | Historisches Rechtemodell mit fünf Stufen |
| Erinnerungs-E-Mails | Im Code vorhanden | Versand serverabhängig; erst mit Testempfängern prüfen |
| Vollständig responsive Darstellung | **Nein** | Historische Desktop-Oberfläche |
| SEPA-Lastschriftverwaltung | **Nein** | Alte Kontonummer-/BLZ-Felder sind noch vorhanden |
| Automatisches Upgrade-/Migrationssystem | **Nein** | Installer vorhanden, aber keine allgemeine Migration-Engine |

**Wichtig für die Finanzen:** Die Seite `member_finanzen.php` berechnet anhand der aktuellen Einstellungen und Abteilungszugehörigkeiten den zu zahlenden Monatsbeitrag. Sie protokolliert **nicht**, wer welchen Betrag wann tatsächlich gezahlt hat. Die künftige Soll-/Ist-Buchhaltung muss Forderungen und Zahlungen getrennt erfassen und historisieren.

## Benutzerrollen

Das ursprüngliche Rechtesystem unterscheidet fünf Stufen:

| Level | Bezeichnung |
| --- | --- |
| 1 | Normaler Nutzer |
| 2 | Moderator |
| 3 | Abteilungsleiter |
| 4 | Vereinsvorstand |
| 5 | Administrator |

Die einzelnen Aktionen werden in den vorhandenen PHP-Modulen überwiegend anhand numerischer Berechtigungsstufen gesteuert. Eine moderne, fein einstellbare Rechteverwaltung je Funktion ist **noch nicht vorhanden**. Welche Person sensible Daten bearbeiten darf, muss deshalb vor einem produktiven Betrieb praktisch geprüft werden. Eine ausführliche Übersicht ist innerhalb des Systems über `rechtesystem.php` erreichbar.

## Herkunft und Weiterentwicklung

### 2012/2013 – Das ursprüngliche Vereinssystem

**Nicolas Jourdan** und **Sebastian Fuss** entwickelten das ursprüngliche Vereinssystem als Abiturprojekt. Die damalige Anwendung beruhte auf PHP 5, klassischen MySQL-Funktionen (`mysql_*`) und einer MySQL-Datenbank. Das letzte mitgelieferte SQL-Abbild stammt aus **Dezember 2013**.

Schon die ursprüngliche Fassung bot Mitglieder, Abteilungen, Veranstaltungsplanung und die Organisation von Vereinsdiensten. Das Original-README nennt ausdrücklich die **kostenlose Verwendung und Erweiterung für nichtkommerzielle Zwecke**.

Das Original-README bleibt zu Dokumentationszwecken unter [docs/ORIGINAL-README-2013.md](docs/ORIGINAL-README-2013.md) vollständig erhalten. Sämtliche Rechte der damaligen Autoren am Original bleiben unberührt.

### 2026 – Wiederaufnahme der Entwicklung

Die aktuelle Weiterentwicklung durch **Marcus Dziersan** verfolgt das Ziel, das alte Vereinsverwaltungssystem auch mit einer heutigen PHP-Umgebung lauffähig zu halten und schrittweise funktional auszubauen, ohne die bisherigen Module vorschnell zu ersetzen.

**Leitlinien der Weiterentwicklung:**

- Bewährte Funktionen erhalten und bestehende Vereinsdaten nicht unnötig verändern.
- PHP, HTML, CSS, JavaScript und MySQL/MariaDB verwenden – ohne Framework- oder Node.js-Zwang.
- Datenbankzugriffe schrittweise auf sichere, parametrisierte PDO-Abfragen umstellen.
- Änderungen nachvollziehbar dokumentieren und Versionsstände eindeutig kennzeichnen.
- Sicherheit, Datensparsamkeit und benutzbare Oberflächen vor zusätzlichen Funktionen priorisieren.
- Änderungen zunächst in einer Testumgebung mit echten Browserabläufen überprüfen.

Das Repository bleibt als Fortführung des bestehenden Projekts erkennbar und ist **keine Neuschöpfung ohne Bezug zum Original**.

## Versionsgeschichte

| Version | Datum | Schwerpunkte |
| --- | --- | --- |
| Historischer Stand | 2012–2013 | Ursprüngliche PHP/MySQL-Vereinsverwaltung und SQL-Dump |
| **2.0.0** | 17.10.2026 | PHP-8-/PDO-Migration, Installationsassistent, Passwortmigration und Sicherheitsanpassungen |
| **2.0.1** | 18.10.2026 | GitHub-Dateibestand vervollständigt, PDO-/Session-Dateien und SQL-Schema ergänzt, Wertebereichsprüfungen verbessert und lokales jQuery bereitgestellt |

### Was wurde in 2.0.1 verbessert?

- Fehlende Kernkomponenten der PHP-8-/PDO-Version im Repository ergänzt.
- Verbindung über `inc/database.php` und `inc/config.php` zentralisiert.
- Session-Verwaltung in `inc/session.php` ausgelagert.
- Separate Validierung für Ganzzahl-/Auswahlwerte eingeführt.
- Ungültige Eingaben bei Mitgliedsarten, Abrechnungsarten und Berechtigungen zuverlässiger zurückgewiesen.
- `sql/schema.sql` für den Installer ergänzt.
- jQuery **3.6.1** lokal eingebunden statt per unverschlüsseltem HTTP von einem CDN.
- Konfigurationsdatei aus der Git-Versionsverwaltung ausgeschlossen.
- Update-Hinweise und Changelog ergänzt.

Alle weiteren Details stehen in [CHANGELOG.md](CHANGELOG.md) und [UPGRADE-2.0.1.md](UPGRADE-2.0.1.md).

## Technik und Voraussetzungen

| Bestandteil | Anforderung oder Implementierung |
| --- | --- |
| Server | Webserver mit PHP-Unterstützung, z. B. Apache |
| PHP | **8.0 oder neuer**; Zielbereich 8.2–8.4 |
| Datenbank | MySQL 5.7+/8.x oder kompatible MariaDB |
| Erweiterungen | `PDO`, `pdo_mysql`; `mbstring` empfohlen |
| Optional | `gd` für aktiviertes Login-Captcha |
| Datenbankzeichensatz | `utf8mb4` |
| Backend | Klassisches PHP, kein Pflicht-Framework |
| Frontend | HTML, CSS, JavaScript, lokal bereitgestelltes jQuery 3.6.1 |
| Betrieb | Lokal oder auf eigenem PHP-Webspace |
| Transport | Im produktiven Betrieb HTTPS verwenden |

Die Anwendung verwendet eine **PDO-basierte Kompatibilitätsschicht**. Alte Module rufen häufig noch `db_query()`, `db_fetch_assoc()`, `db_num_rows()` oder `db_escape()` auf. Das ist **nicht** identisch mit einer vollständigen Modernisierung sämtlicher SQL-Abfragen auf Prepared Statements mit gebundenen Parametern.

Um alte SQL-Abfragen aus der MySQL-5-Zeit zunächst weiterhin ausführen zu können, setzt die PDO-Verbindung vorübergehend den Sitzungsmodus `NO_ENGINE_SUBSTITUTION`. Das ist ein Kompromiss der Migration und soll bei einer späteren Überarbeitung der Abfragen entfallen.

### Lokale Entwicklungsumgebung

Zum Beispiel unter WAMP:

1. PHP 8 und `pdo_mysql` aktivieren.
2. MySQL/MariaDB starten und eine leere Datenbank anlegen.
3. Projektdateien in das Webverzeichnis, z. B. `www/verein/`, kopieren.
4. `http://localhost/verein/install.php` aufrufen.
5. Installer ausfüllen und danach `http://localhost/verein/login.php` öffnen.

## Neue Installation

**Vorbereitung:** Eine leere Datenbank samt Datenbanknutzer mit passenden Rechten muss existieren. Die eigentliche Datenbank wird beim Hoster oder lokal erstellt; der Installer richtet darin die Tabellen ein.

1. Repository als ZIP herunterladen oder mit `git clone https://github.com/marcdziersan/Vereinssystem.git` abrufen.
2. Die Projektdateien vollständig auf den Webspace in ein eigenes Verzeichnis übertragen.
3. Im Browser `https://deine-domain.de/verein/install.php` aufrufen.
4. Datenbankhost, Port, Datenbankname und Zugangsdaten eintragen.
5. Vereinsname und Administratorkonto anlegen; das Admin-Passwort muss mindestens **12 Zeichen** haben.
6. **Installieren** starten. Der Assistent richtet die acht bestehenden Tabellen, Grundeinstellungen und bei neuer Datenbank ein Administratorkonto mit Rechtelevel 5 ein.
7. Mit `login.php` anmelden und die Vereins-, Beitrags- und E-Mail-Einstellungen prüfen.
8. Die Installationsdatei `install.php` vom öffentlich erreichbaren Server entfernen und Berechtigungen von `inc/config.php` einschränken.

**Kein manueller SQL-Import nötig:** Bei einer Neuinstallation verwendet `install.php` die Datei `sql/schema.sql`. Dafür muss PHP `inc/config.php` während der Installation anlegen können.

**Vertraulich:** Die automatisch erstellte `inc/config.php` enthält Datenbankzugangsdaten. Sie gehört weder in ein öffentliches Repository noch in eine ungeschützte Webauslieferung.

## Updates und Übernahme alter Daten

### Update von 2.0.0 auf 2.0.1

1. **Datenbank und Webspace vollständig sichern.**
2. Dateien der neuen Version zunächst in einer Kopie der Installation prüfen.
3. Die vorhandene `inc/config.php` **beibehalten und nicht überschreiben**.
4. Neue Programmdateien einspielen. `install.php` bei vorhandener Konfiguration **nicht erneut ausführen**.
5. Anmeldung, Mitgliederprofile, Stammdatenänderungen, Berechtigungen, Abteilungen, Dienste, Veranstaltungen und E-Mail-Einstellungen im Browser prüfen.
6. Erst danach die produktive Installation aktualisieren.

Version 2.0.1 führt **keine neue Beitragsbuchhaltung und keine zusätzliche Tabellenmigration** ein. Hinweise zur Aktualisierung: [UPGRADE-2.0.1.md](UPGRADE-2.0.1.md).

### Umstieg von der Originalversion (2013)

Die alte Version speicherte Passwörter zum Teil als 32-stellige **MD5-Hashes** und nutzte die heute entfernten `mysql_*`-Funktionen. Für einen Umstieg:

1. Eine vollständige Kopie der alten Datenbank und der alten Dateien erstellen.
2. Das neue System getrennt vom laufenden Original aufsetzen.
3. Den Installer mit der bestehenden Datenbankkopie verbinden; vorhandene Datensätze sollen erhalten und fehlende Tabellen/Einstellungen ergänzt werden.
4. Mit einem bestehenden Konto anmelden. Ein gültiger alter MD5-Hash wird beim erfolgreichen Login auf `password_hash()` umgestellt.
5. Individuelle Änderungen der alten Tabellenstruktur gesondert prüfen.

**Einschränkung:** `install.php` ist **keine vollständige Migrationsengine**. Es verändert bestehende individuelle Tabellenstrukturen nicht systematisch. Ein Update direkt auf einer produktiven Originaldatenbank ohne Testkopie ist nicht empfohlen.

## Hintergrundverarbeitung und E-Mails

Der historische `cronjob.php` verarbeitet unter anderem Geburtstags- und Mitgliedschaftsjubiläumsgrüße sowie vorhandene Dienstabrechnungslogik. Er kann über die **PHP-CLI** regelmäßig gestartet werden, zum Beispiel durch einen täglichen Hoster-Cron.

Der Cronjob kann **reale E-Mails auslösen**. Vor Aktivierung müssen Absender, Empfänger, Serverkonfiguration und Testdaten überprüft werden. Die Zustellung hängt unter anderem von der PHP-Mailkonfiguration und dem Hoster ab und wurde für 2.0.1 nicht als vollständiger Integrationsprozess nachgewiesen.

Die jährlichen Dienste-/Arbeitsleistungen sind **keine** allgemeine Finanzbuchhaltung und stellen keine erfassten Zahlungseingänge dar.

## Projektstruktur

| Pfad | Aufgabe |
| --- | --- |
| `index.php` | Start-/Übersichtsseite |
| `login.php`, `login_process.php` | Anmeldung |
| `members.php`, `member_*.php` | Mitgliederverwaltung, Profile, Finanzberechnung |
| `abteilungen.php`, `abteilung_*.php` | Abteilungen und Zugehörigkeiten |
| `orte.php`, `ort_*.php` | Orte und Anschriften |
| `veranstaltungen.php`, `veranstaltung_*.php` | Veranstaltungen |
| `dienste_*.php` | Vereins- und Helferdienste |
| `news*.php` | Nachrichten |
| `einstellungen*.php`, `rechtesystem.php` | Systemeinstellungen und Rechteübersicht |
| `cronjob.php` | Hintergrundverarbeitung / Erinnerungs-E-Mails |
| `install.php` | Erstinstallation |
| `inc/connect.inc.php` | Initialisierung und Einstellungen |
| `inc/database.php` | PDO-Kern und historische Datenbankadapter |
| `inc/session.php`, `inc/checkuser.inc.php` | Sessions, Authentifizierung und Schutzprüfungen |
| `inc/config.php` | Lokal erzeugte Datenbankkonfiguration – **nicht einchecken** |
| `sql/schema.sql` | SQL-Tabellenstruktur |
| `css/`, `images/`, `assets/js/` | Gestaltung, Bildressourcen und JavaScript |
| `CHANGELOG.md` | Änderungen nach Versionen |
| `LICENSE.md` | Lizenzzuordnung und Urheberrecht |
| `LICENSE-POLYFORM-NONCOMMERCIAL.md` | Lizenztext der eigenen Ergänzungen |
| `docs/ORIGINAL-README-2013.md` | Unverändertes Original-README als Herkunftsnachweis |

## Datenschutz und Sicherheit

Das System kann personenbezogene Informationen wie Namen, Adressen, Geburtsdaten, E-Mail-Adressen und historische Bankdaten enthalten. Vor echtem Vereinsbetrieb sind insbesondere folgende Punkte zu berücksichtigen:

- **HTTPS** verwenden; die Konfigurationsdatei gegen direkten Abruf absichern.
- Geeignete Rollen und Zugriffsbeschränkungen prüfen, besonders bei Kindern und Bankdaten.
- Server, PHP und Datenbank aktuell halten; Backups erstellen und ihre Wiederherstellung testen.
- Export, Löschung, Aufbewahrung und Zugriff auf personenbezogene Daten nach den eigenen gesetzlichen Pflichten gestalten.
- Historische SQL-Abfragen konsequent auf Parameterbindung umstellen; den Kompatibilitätsmodus mittelfristig ablösen.
- CSRF-, Session-, Formular- und Dateizugriffe sowie E-Mail-Abläufe in der konkreten Hostingumgebung testen.
- Fehlermeldungen und Logdateien nicht öffentlich ausgeben.
- `install.php` nach der Installation entfernen.

Die vorhandenen Datenfelder Kontonummer und BLZ sind **keine aktuelle SEPA-Verwaltung**. Auch die alte Rechtearchitektur ersetzt kein vollständiges Datenschutzkonzept.

Weitere Hinweise stehen unter [SECURITY.md](SECURITY.md).

## Geplante Weiterentwicklung

Die folgende Roadmap ist eine **Planung**, keine Zusage bereits vorhandener Funktionen.

| Geplante Version | Ziel |
| --- | --- |
| **2.1.0** | Beitragsforderungen und Zahlungsbuchungen, Soll/Ist, Teilzahlungen, Rückstände und Guthaben |
| **2.2.0** | Frei anlegbare Kassen-/Zahlungskonten, Umbuchungen, Storno und Kassenberichte |
| **2.3.0** | Differenziertere Rollen/Rechte, CSV-Import, Belege und Auswertungen |
| **2.4.0** | Weiterentwicklung der responsiven Bedienoberfläche und des Dashboards |

Die Neuentwicklung von Masken soll bereits vor 2.4.0 auf mobile Benutzung Rücksicht nehmen. Bestandsfunktionen und Datenkompatibilität haben bei jedem Schritt Vorrang.

## Mitwirken und Fehler melden

Rückmeldungen, nachvollziehbare Fehlerbeschreibungen und Verbesserungsvorschläge sind über [GitHub Issues](https://github.com/marcdziersan/Vereinssystem/issues) willkommen. Hilfreiche Angaben sind PHP-Version, Datenbankversion, betroffene Seite, die ausgeführte Aktion sowie die erwartete und tatsächliche Reaktion.

**Bitte keine echten Vereins-, Kinder-, Mitglieder-, Bank- oder Zugangsdaten in Issues, Screenshots oder Logauszügen veröffentlichen.**

Wer Code oder Dateien beisteuert, sollte die bestehende Herkunft, die nichtkommerziellen Nutzungsbedingungen und die Lizenzzuordnung beachten. Eine Pull-Request-Einreichung ersetzt keine Klärung eventuell fremder Urheberrechte.

## Lizenz und Urheberrecht

**Für nichtkommerzielle Zwecke kostenlos nutzbar und erweiterbar. Kommerzielle Verwendung ist nicht pauschal gestattet.**

Dieses Repository enthält Werke mit **unterschiedlicher rechtlicher Herkunft**:

1. **Originalprojekt (2012/2013):** Nicolas Jourdan und Sebastian Fuss. Das Original-README erlaubt die kostenfreie Nutzung und Erweiterung **für nichtkommerzielle Zwecke**. Die ursprünglichen Rechte werden durch die Weiterentwicklung **nicht übertragen, aufgehoben oder neu lizenziert**. [Original-README lesen](docs/ORIGINAL-README-2013.md).
2. **Eigene Ergänzungen ab 2026:** Soweit die entsprechenden Rechte bei Marcus Dziersan liegen, werden diese Beiträge unter der **[PolyForm Noncommercial License 1.0.0](LICENSE-POLYFORM-NONCOMMERCIAL.md)** zur nichtkommerziellen Nutzung, Änderung und Weitergabe angeboten.
3. **Drittanbieterbestandteile:** Insbesondere die lokale jQuery-Bibliothek und übernommene Bild-/Schriftressourcen unterliegen jeweils ihren **eigenen Lizenz- bzw. Urheberrechtsbedingungen**. Sie werden durch die vorstehende Lizenzzuordnung nicht umgestellt.

**Wichtig:** PolyForm Noncommercial betrifft nur jene Bestandteile, für die der jeweilige Lizenzgeber die Rechte besitzt. Wer eine kommerzielle Freigabe benötigt, muss die jeweils erforderlichen Rechte **bei allen betroffenen Rechteinhabern** separat einholen. Die bloße öffentliche Bereitstellung auf GitHub bedeutet keine kommerzielle Nutzungserlaubnis.

Die rechtliche Zuordnung und der vollständige Text stehen in **[LICENSE.md](LICENSE.md)**. Ergänzend dokumentiert [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md) externe Bestandteile. Nichtkommerzielle Software mit solchen Einschränkungen ist nach der Definition der Open Source Initiative **nicht OSI-Open-Source**.

---

**Vereinssystem** · Original: Nicolas Jourdan & Sebastian Fuss (2012/2013) · Weiterentwicklung: Marcus Dziersan (seit 2026) · [Repository](https://github.com/marcdziersan/Vereinssystem)
