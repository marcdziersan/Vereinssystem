# Jahreskassenberichte, CSV, PDF und private Belege (MVC alpha.9)

## Fachliches Ziel

Die Buchung selbst bleibt unverändert im Kassenbuch aus alpha.8. Das Modul fügt eine **lesende** Jahresauswertung und **separat geschützte** Dateianhänge zu vorhandenen Kassenbuchungen hinzu. Die Buchungs- und Stornotransaktionen werden dadurch nicht verändert.

## MVC/OOP-Verantwortlichkeiten

| Komponente | Aufgabe |
| --- | --- |
| `Models/CashReport.php` | unveränderliches Jahresergebnis mit Kontenzeilen, Journaleinträgen und Summen in Cent |
| `Reports/ReportRepository.php` | Interface für eine vollständige Jahresauswertung |
| `Reports/PdoReportRepository.php` | Prepared Statements für alle Konten, Vorjahresbewegungen und alle Buchungen im gewählten Jahr |
| `Services/ReportService.php` | Filtervalidierung sowie CSV- und PDF-Erzeugung ohne SQL/HTML |
| `Reports/PdfWriter.php` | kompakter, mehrseitiger PDF-1.4-Schreiber ohne externe Pakete |
| `Controllers/CashReportController.php` | HTML-/CSV-/PDF-Antworten und Download-Header |
| `Models/Receipt.php` | unveränderliche Metadaten eines hochgeladenen Belegs |
| `Receipts/ReceiptRepository.php`, `PdoReceiptRepository.php` | PDO-Metadaten in `vs_belege` sowie Auditprotokoll |
| `Services/ReceiptService.php` | Uploadprüfung, private Speicherung und Inhaltsintegrität |
| `Controllers/ReceiptController.php` | geschützte Upload-/Download-Anfragen |
| `resources/views/reports/cash.php` | Mobile-First-Berichtsansicht |

## Berechnungsregeln

1. Für jedes Konto beginnt der Jahresanfangsbestand mit dem historischen Konto-Anfangsbestand plus allen **aktiven** früheren Buchungen.
2. Ein erst im ausgewählten Jahr eröffnetes Konto zeigt seinen Anfangsbestand in der **separaten Spalte „Eröffnung im Jahr“**, nicht rückwirkend als Bestand zum 1. Januar.
3. Einnahmen, Ausgaben und beide Seiten einer Umbuchung werden je Konto getrennt ausgewiesen. Eine Umbuchung verändert den Gesamtbestand über alle Konten nicht.
4. Stornierte Datensätze werden aus Gründen der Nachvollziehbarkeit weiterhin im Journal und in Exports angezeigt, **wirken aber nicht auf die errechneten Salden**.
5. Jahresendbestand = Anfangsbestand + neue Anfangsbestände + Einnahmen − Ausgaben + Umbuchung_ein − Umbuchung_aus.
6. Im Bericht wird **kein `LIMIT 300`** verwendet; die 300-Zeilen-Grenze der normalen Kassenansicht beeinflusst die Jahresauswertung nicht. Umfangreiche Exporte beanspruchen PHP-Arbeitsspeicher.
7. Berichte bilden die **aktuell korrigierte Historie** ab. Ein späteres Storno einer alten Buchung verändert folglich auch deren bisherigen Jahresbericht; für unveränderliche Jahresabschlüsse wäre zusätzlich ein Abschluss-/Snapshot-Modul nötig.

## Export

`GET /cash/report`, `GET /cash/report/csv` und `GET /cash/report/pdf` benötigen mindestens Vorstandsberechtigungen. Konto und Jahr sind explizit validiert.

- CSV: UTF-8 mit BOM, Semikolon, Kennzeichnung stornierter Datensätze und neutralisierte Formeleingaben in Freitextfeldern. Für Tabellenkalkulationen geeignet.
- PDF: A4, Seitenumbrüche, Kontensalden und vollständige Buchungsliste, erzeugt mit dem kommentierten `PdfWriter` in PHP. Die PDF-Zeichensätze werden auf Windows-1252 transliteriert; Sonderzeichen außerhalb dieses Zeichensatzes sind möglicherweise vereinfacht dargestellt.
- HTTP-Header: `Content-Disposition: attachment`, `Cache-Control: private, no-store` und `X-Content-Type-Options: nosniff` (letzteres zentral in `Response`).

## Private Kassenbelege

Die Metadaten werden in der kompatiblen Tabelle `vs_belege` gespeichert. Falls alte `master`-Versionen Belege im per `VS_PRIVATE_STORAGE_DIR` konfigurierten Dateispeicher abgelegt haben, sind die Dateien zusätzlich gesichert nach `storage/receipts/` zu kopieren (die zufälligen Speichernamen unverändert lassen); **nur** die Tabellenmigration stellt frühere Belegdateien nicht automatisch wieder her. Die eigentlichen Dateien liegen **außerhalb** von `public/` in `storage/receipts/`. Die Root-`.htaccess` und die lokale `storage/receipts/.htaccess` verbieten direkte HTTP-Aufrufe.

- POST `/cash/receipt/upload`: Anmeldung, Rolle >= 4 und CSRF-Token sind erforderlich. Upload nur über PHP-`move_uploaded_file`, maximal **8 MiB** und nur Inhaltstypen **PDF, JPEG, PNG, WebP** nach `finfo`.
- Jeder Dateiname wird serverseitig durch einen zufälligen 64-stelligen Hexspeichernamen ersetzt. Der Originalname dient nur der Anzeige und wird in HTML escaped.
- SHA-256, Dateigröße und Metadaten werden gespeichert. Bei fehlgeschlagenem Datenbank-Insert wird die Datei gelöscht.
- GET `/cash/receipt/download`: erneute Anmeldung und Rechteprüfung, ID-Lookup statt URL-Pfad; Dateiname, Größe und SHA-256 werden vor Auslieferung verifiziert. Downloads sind immer `attachment` und niemals HTML/inline gerendert.
- Das Audit `vs_audit` vermerkt erfolgreiche Beleguploads. Alte Buchungen und alte Belege werden weder überschrieben noch durch diese Migration gelöscht.

Für produktive Backups **MySQL-Datenbank und `storage/receipts/` immer gemeinsam sichern**, weil die Metadaten allein keine Datei wiederherstellen können. Das System enthält derzeit kein automatisches Backupmodul.

## WAMP-Installation und Update

Der Einstieg bleibt `http://localhost/verein/` mit der Root-`.htaccess`. Eine Neuinstallation erstellt **16 Tabellen**, darunter `vs_belege`.

Bestehende alpha.8-Installation: zuerst Backup von Datenbank und privatem Speicher anfertigen, danach neue Dateien hochladen und im Projektordner ausführen:

```sh
php bin/migrate-receipts.php
```

Das Skript verwendet ein `CREATE TABLE IF NOT EXISTS` und löscht weder alte Buchungen noch alte Belege. Die neue MVC-Routelogik benötigt keinen Legacy-Endpunkt.

## Weitere Ausbaustufen

Bankimport, automatische Kontenabstimmung, Beleg-Löschregeln, revisionstaugliche Jahresabschlüsse und verbands- beziehungsweise steuerrechtliche Spezialexporte sind **nicht** Bestandteil dieser Phase.

Für Uploads der Maximalgröße müssen `upload_max_filesize` und `post_max_size` in WAMP entsprechend groß konfiguriert sein (`post_max_size` größer als 8 MiB).
