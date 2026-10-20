# Vereinssystem MVC – Konsistenzprüfung und Umsetzungspakete

**Prüfstand:** 21.10.2026  
**Entwicklungsbranch:** `refactor/mvc-architecture` bei `ace3304d6fbae9bf02279e2be7ab9ac14b5d2121`  
**Referenzbranch:** `master` bei `c16feec22531892f4257b9a1b1b29a15da08b65d`  
**Grundsatz:** `master` bleibt unverändert. Jeder Zwischenschritt des MVC-Branches muss separat installierbar beziehungsweise aktualisierbar und praktisch abnehmbar sein.

## 1. Gesicherter Iststand

- Der MVC-Branch enthält **184 versionierte Dateien**. Der aufbewahrte Stand `Vereinssystem-MVC-3.0.0-alpha.9.zip` enthält ebenfalls 184 Dateien und ist als ZIP lesbar.
- Auf GitHub ist **3.0.0-alpha.9** bereits eingecheckt: `e17da08` (Berichte/Belege) und `ace3304` (Schema-Abgleich der Belege). Weitere Arbeit muss hiervon ausgehen, nicht von alpha.8.
- **134 PHP-Dateien** bestehen die Syntaxprüfung unter PHP 8.4. Die neun lokalen PHP-Prüfprogramme laufen durch: 16 Kernel-, 26 Auth-, 38 Mitglieder-, 27 Abteilungen/Orte-, 44 Events-, 51 Nachrichten-, 63 Finanz-, 66 Kassen- sowie 38 Bericht-/Belegprüfungen, zusammen **369 Assertions**. Diese Prüfprogramme verwenden teilweise Test-Repositories und ersetzen keinen vollständigen InnoDB-Abnahmelauf.
- Der lokale PHP-HTTP-Start liefert für `/install` und `/status` HTTP 200; unkonfigurierte Fachrouten leiten zum Installer weiter. Die Apache-/WAMP-Rewrite-Regeln liegen im Repository, ihre Installation muss weiterhin auf einer WAMP-Instanz abgenommen werden.
- **16 Tabellen** sind auf die sieben Migrationsskripte `001` bis `007` verteilt und in der Erstinstallation berücksichtigt. Die Definition `vs_belege` stimmt mit `master/sql/schema.sql` überein.
- Die 40 expliziten Fachrouten aus den News-, Event-, Finanz-, Kassen-, Beleg- und Berichtsrouten verwenden 25 Berechtigungskennungen; jede davon ist in `app/Auth/Roles.php` definiert. Frühere MVC-Controller kontrollieren Rechte/CSRF noch direkt im Controller statt über die gemeinsame Middleware.
- **Paketabweichung:** Das Alpha.9-ZIP und der GitHub-Commit haben dieselben 184 Pfade, aber unterschiedliche Git-Blob-Inhalte bei **`public/assets/vendor/jquery-3.6.1.min.js`** und **`public/assets/vendor/jquery-LICENSE.txt`**. Die übrigen Teilbäume sind byteidentisch. Beide JS-Dateien nennen jQuery 3.6.1, die konkrete Fassung und zugehörige Lizenz sollten vereinheitlicht werden.
- **Dokumentationsabweichung:** `docs/ARCHITECTURE.md` trägt im Titel weiterhin alpha.6; `README.md` beschreibt die Finanzroute an zwei Stellen. Die Fachmodule verwenden unterschiedliche Ordner-/Kommentierungsstile, obwohl alle in OOP/PDO arbeiten.

## 2. Offene Risiken und Abgrenzung

1. `CREATE TABLE IF NOT EXISTS` schafft fehlende Tabellen, **prüft aber keine abweichenden Spalten/Indizes bestehender Tabellen**. Eine bestätigte 2.4.0→3.0.0-Migration erfordert Schemaabgleich, Backup und echte Testdaten.
2. Die atomare Verbindung zwischen Beitragszahlungen, Kassenbuch und Stornos ist im Code vorhanden; für Produktionsfreigabe sind Transaktions-, Fremdschlüssel-, Parallelbuchungs- und Rollbackprüfungen gegen **MySQL/MariaDB mit InnoDB** wichtig.
3. Belegdateien liegen außerhalb von `public/` und werden über geschützte Routen ausgeliefert. Altbelege aus dem alten privaten Ablageort benötigen eine gesonderte Dateiübernahme. Die Upload-/Download-Berechtigungen und die HTTP-Sperren müssen im realen WAMP-Pfad kontrolliert werden.
4. Jahresauswertungen geben die **jeweils aktuell korrigierte Historie** wieder; ein späteres Storno verändert alte Berichte. Ein unveränderlicher Jahresabschluss ist noch keine implementierte Funktion.
5. Neue MVC-Module fehlen gegenüber dem alten Projekt unter anderem bei System-/Mail-Einstellungen, Nutzer-Passwortpflege, CSV-/Bankimport, Erinnerungsabläufen und automatischen Backups. Das sind eigenständige Folgepakete, keine verdeckten Bestandteile von alpha.9.

## 3. Kleine Stabilisierungspakete – jeweils einzeln abnehmbar

| Paket | Abgegrenzter Auftrag | Fertig, wenn … |
| --- | --- | --- |
| **9.1 – Release-Synchronisierung** | Die beiden jQuery-Dateien samt Lizenz zwischen Repository und ZIP vereinheitlichen; Dokumentationsversionsstände und doppelte README-Einträge bereinigen; Datei-/Hashmanifest erstellen. | Das neue ZIP entspricht **bytegenau** dem getaggten Commit, alle bisherigen PHP-Prüfungen bleiben erfolgreich. **Keine SQL-Änderung.** |
| **9.2 – Installer/Migrationen** | Ist-Schema prüfen, sichere additive Änderungen planen, DB-Versionsstand dokumentieren und Neuinstallation gegen Upgrade aus 2.4.0 abgrenzen. | Leere und kopierte bestehende MySQL-Datenbank starten korrekt, fehlende/abweichende Felder werden erkannt und kein Altbestand überschrieben. |
| **9.3 – Berechtigungs-Middleware** | Mitglieder, Orte, Abteilungen, Dashboard, Login und Rechteverwaltung mit einer durchgängigen Route-Policy versehen; Controller-Prüfungen sinnvoll beibehalten, wo objektbezogen nötig. | Rechte- und CSRF-Matrix für GET/POST liefert nachvollziehbar 200/303/403/419; direkte URL-Aufrufe umgehen die Rechte nicht. |
| **9.4 – Beitrags-/Kassen-InnoDB** | Zusammenhängende echte Datenbankfälle: Soll, Teilzahlung, Guthaben, Kontoauswahl, Umbuchung, Storno, Fehler-Rollback, Mehrfach- und Parallelaufrufe. | Erwartete Cent-Salden, Zuordnungen, Gegenbuchungen und Auditdaten stimmen nach jeder Aktion sowie nach simulierten Fehlern. |
| **9.5 – Private Belege** | Dateiprüfung, 8-MiB-Limit, MIME/Hash, Zugriffsverweigerung, Ablage-/DB-Rollback, Übernahme vorhandener Belegdateien. | Nur berechtigte Benutzer laden hoch/herunter; direkte `/storage`-Aufrufe liefern 403; Backup und Wiederherstellung von DB + Dateien funktionieren. |
| **9.6 – Berichte/Exporte** | Konten- und Jahresabgleich bei unterjähriger Eröffnung, inaktiven Konten, 301+ Buchungen, Stornos, Umbuchungen, Umlauten und großen Dateien. | Browserbericht, CSV und PDF zeigen dieselben Salden, sind tatsächlich lesbar und verändern keine Buchung. |
| **9.7 – Mobile UI und Quellcodedoku** | Smartphone-Navigation, lange Formulare, Tabellen, Feedback/Fehlermeldungen, Tastaturbedienung; PHP/CSS/JS-/HTML-Kommentare und Architekturablage vereinheitlichen. | Alle vorhandenen Module lassen sich bei 390 px und am Desktop bedienen, keine Fachfunktion wird verdeckt und die Dokumentation verweist auf die aktuelle Struktur. |
| **9.8 – Stabilitätsfreigabe** | Vollständige WAMP-Abnahme aus frischem ZIP, Installation und Upgrade, Versions-/Lizenz-/Artefaktvergleich, Changelog und Rückfallstrategie. | Ein reproduzierbares Release mit abgehakter Abnahmematrix ist erstellt; erst danach gilt alpha.9 als technisch abgeschlossen. |

**Arbeitsregel:** Pro Paket ein kleiner Commit oder zusammengehörige Commits, ein separates Änderungsprotokoll und ein vollständiger installierbarer ZIP-Stand. Nächstes Paket erst nach erfolgreicher Prüfung des vorherigen. Bei einem Fehler bleibt das letzte funktionierende ZIP verfügbar.

## 4. Weitere Fachpakete nach stabiler alpha.9

| Paket | Inhalt | Abgrenzung |
| --- | --- | --- |
| **10.1 – Kontopflege** | Eigenes Passwort ändern, Zugangsdaten und Profileinstellungen als MVC-Module. | Keine Erweiterung der Beitragsbuchhaltung. |
| **10.2 – Systemeinstellungen** | Vereineinstellungen, E-Mail-/Benachrichtigungskonfiguration, Protokolle. | Versandjobs kommen erst in 10.4. |
| **10.3 – CSV-/Bankimport** | Importvorschau, Zuordnung zu Kassenkonten, Dublettenerkennung und kontrollierter Abgleich. | Erst nach stabiler Kasse. |
| **10.4 – Zeitgesteuerte Aufgaben** | CLI-/Cron-Aufgaben, Erinnerungsläufe, Fehlerprotokoll, Wiederholschutz. | Keine Browserausführung ungeschützter Jobs. |
| **10.5 – Backup/Recovery** | Gemeinsame Sicherung von DB und privaten Belegen, gesicherte Wiederherstellung und Versionsprüfung. | Kein Überschreiben ohne bestätigten Wiederherstellungslauf. |
| **10.6 – Jahresabschluss** | Prüfbarkeit und optional unveränderliche Abschluss-Snapshots. | Getrennt von laufenden Jahresberichten. |
| **RC-Vorbereitung** | Vollständige Soll-/Ist-Funktionsinventur gegenüber `master`, Fehlerkorrekturen, konsistenter Mobile-First-UX und dokumentierter Umstieg. | Erst danach Release Candidate für 3.0.0. |

## 5. Unmittelbar als Nächstes

**Paket 9.1**: Reproduzierbare Release-Dateien und Dokumentationsstand. Erst nach dessen Abnahme beginnt Paket 9.2 (Datenbank/Installer). Auf `master` wird nichts geändert.
