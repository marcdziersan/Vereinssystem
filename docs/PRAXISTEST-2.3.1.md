# Vereinssystem 2.3.1 – Praxisabnahme mit WAMP oder Hosting

**Ziel:** Den tatsächlichen Ablauf im Browser prüfen, nicht nur den Erfolg einzelner Prüfscripte. Diese Anleitung ist die praktische Abnahme für eine **gesonderte Installation mit kopierten oder frei erfundenen Mitgliedsdaten**. Die vorhandene Produktionsdatenbank bleibt unverändert.

## Vorbereitung

1. Datenbank UND Webspace sichern und Kopie der Datenbank auf einem Testserver / WAMP anlegen.
2. PHP 8, `pdo_mysql`, MySQL/MariaDB, `mbstring` (optional), HTTPS bei Internetbetrieb konfigurieren.
3. Projektdateien hochladen, **eigene** `inc/config.php` aus dem Produktivsystem nicht veröffentlichen und nicht in Git übernehmen.
4. Bei Neuinstallation `install.php` aufrufen. Bei bestehender 2.x-Testdatenbank `upgrade.php` als Admin ausführen; danach beide Installer vom öffentlichen Webserver entfernen.
5. `php tests/praxis_db.php` über die Kommandozeile starten. Es werden keine Daten verändert und lediglich Konsistenzkennzahlen ausgegeben.
6. Drei Testkonten vorbereiten: Administrator (Level 5), Kassenwart (gesonderte Rechte), normales Mitglied ohne Finanzrechte.
7. Zwei Kassenkonten anlegen: Bank (100,00 € Anfangsbestand), Barkasse (20,00 €). Alle Sollstellungen und Zahlungen unten nur in dieser Testdatenbank anlegen.

## Browser-Checkliste (Beispielbeträge)

| # | Aktion | Sichtbares Soll-Ergebnis |
|---|---|---|
| 1 | Erstanmeldung / Logout / erneuter Login | Nur angemeldete Nutzer sehen private Module; Sitzung beim Logout beendet |
| 2 | Mitglied anlegen und bearbeiten | Daten nach Seitenwechsel unverändert gespeichert, HTML-Sonderzeichen als Text dargestellt |
| 3 | Beiträge Kind/Erwachsene festlegen | Tarife werden korrekt angezeigt; bereits erzeugte Forderungen unverändert |
| 4 | Monats-Sollstellung 15,00 € erzeugen | Soll 15,00 €, Ist 0,00 €, Offen 15,00 € |
| 5 | Dieselbe Sollstellung erneut starten | Keine doppelte Forderung |
| 6 | 10,00 € auf Bank als Mitgliedsbeitrag buchen | Soll 15,00 €, Ist 10,00 €, Offen 5,00 €; Bank +10,00 € **genau einmal** |
| 7 | Weitere 8,00 € buchen | 5,00 € dem Soll zugeordnet, 3,00 € Guthaben; Bank +8,00 € |
| 8 | 25,00 € Bank → Barkasse umbuchen | Bank −25,00 €, Barkasse +25,00 €; Gesamtbestand unverändert |
| 9 | 4,00 € Barausgabe erfassen | Barkasse −4,00 €, Kassenbestand insgesamt −4,00 € |
| 10 | Umbuchung mit Begründung stornieren | Beide Seiten verschwinden aus dem aktiven Saldo; Storno bleibt im Protokoll |
| 11 | Mitglieds-Zahlung stornieren | Beitrag wieder offen; verbundener Kasseneingang ebenfalls storniert |
| 12 | CSV mit 2 Positionen prüfen und bestätigen | Vorschau ohne Buchung, Bestätigung erzeugt exakt 2 Datensätze |
| 13 | Dieselbe CSV erneut importieren | Keine zweite Übernahme dieser Positionen |
| 14 | Testbeleg hochladen und herunterladen | Datei nur mit Belegrecht erreichbar; gespeichert **außerhalb** des Webroots |
| 15 | Jahres-PDF und CSV exportieren | Jahr, Konten und Beträge stimmen mit Browseransicht überein |
| 16 | Als normales Mitglied fremdes Finanzkonto öffnen | Kein Zugriff auf fremde persönliche Finanzdetails |
| 17 | Kassenwart ohne Rechte auf Konten/Import | Buchen möglich (falls berechtigt), andere Aktionen serverseitig verweigert |
| 18 | Nachricht mit `<img onerror=...>` versuchen | Keine JavaScript-Ausführung beim Anzeigen oder Bearbeiten |
| 19 | Mit leerem `cakeworkcount` auf Dashboard gehen | Kein Division-durch-null-Absturz |
| 20 | `upgrade.php` erneut in der **Testinstallation** ausführen | Keine neuen Doppel-Tabellen, bestehende Datensätze erhalten |

## Technische CLI-Prüfungen (optional)

```bash
php tests/smoke.php
php tests/finance_smoke.php
php tests/cash_smoke.php
php tests/security_231.php
php tests/praxis_db.php
```

Der letzte Aufruf prüft Datenbankstruktur und referentielle Konsistenz nur lesend. Er ersetzt keine echten Buchungen im Browser.

## Fehler dokumentieren

Für reproduzierbare Fehler: Bildschirm/URL, Aktion, erwartetes/erhaltenes Ergebnis, PHP- und DB-Version, Serverlog-Ausschnitt ohne personenbezogene Inhalte. **Keine Kennwörter, echten Mitglieder- oder Kontodaten teilen.**

## Rücknahme

Version 2.3.1 führt **keine neuen Datenbanktabellen** ein. Bei einem Funktionsproblem zuerst den Dateistand der 2.3.0-Sicherung wiederherstellen; bei bereits erstellten Finanzbuchungen nicht einfach auf einen älteren Datenbankstand zurückspringen, da dies Buchungen verlieren würde. Jede Rücknahme muss Daten und Code gemeinsam berücksichtigen.
