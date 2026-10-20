# Alpha.9 – Belege, Kassenberichte und Exporte

## Was wurde neu eingeführt?

Das bestehende `vs_kassenbuch` bleibt erhalten. Neue Belegmetadaten liegen in `vs_kassenbelege`, die Binärdaten in `storage/receipts/`. Belegnummern (`vs_kassenbuch.belegnummer`) bleiben unabhängig von hochgeladenen Dateien. Mehrere Anhänge je Buchung sind möglich.

### Upload- und Zugriffsschutz

- Jede Datei muss aus einem echten PHP-HTTP-Upload stammen (`is_uploaded_file`, `move_uploaded_file`).
- 5 MiB maximale Dateigröße; `fileinfo` validiert PDF/JPEG/PNG/WebP anhand des Inhalts, Bilder werden zusätzlich auf gültige Bildstruktur geprüft.
- Verzeichnis und Dateinamen sind nicht öffentlich; 128-Bit-zufälliger Speichername, keine Original-Erweiterung im Dateipfad.
- Für jede Datei werden Anzeigename, geprüfter MIME-Typ, Größe, SHA-256, Buchungs-ID, Urheber und Zeitpunkt gespeichert. Ein Audit-Eintrag dokumentiert das Anhängen.
- Upload: `POST /cash/receipts/upload` (Rechte-Middleware + CSRF). Download: `GET /cash/receipts/download?id=...` (Rechte-Middleware, Integritätsprüfung, `Content-Disposition: attachment`, `nosniff`, `Cache-Control: no-store`).
- Fehlgeschlagene Datenbank-Inserts entfernen die gerade angelegte Datei wieder. Ein unerwarteter Serverabsturz zwischen Dateispeicherung und Datenbankeintrag kann isolierte Dateien hinterlassen; Datensicherung umfasst daher **immer Datenbank und `storage/receipts/` gemeinsam**.
- Auf WAMP: Apache `AllowOverride All` und `mod_rewrite`; die Zugriffssperre in `storage/.htaccess` muss aktiv sein. Für PHP `file_uploads=On`, `fileinfo` und geeignete `upload_max_filesize`-/`post_max_size`-Grenzen konfigurieren.

### Jahresbericht und Rechenregeln

- Auswertung über **sämtliche** Buchungen des Jahres (`CashReportRepository::snapshot`), nicht die 300 Einträge der Journalvorschau.
- Die PDO-Implementierung liest Konten und Buchungen in **einem konsistenten Transaktions-Snapshot**.
- Jahresanfang: Anfangsbestände der vor Jahresbeginn eröffneten Konten **plus Buchungen aus Vorjahren** (Stornos nicht saldowirksam).
- Neu im Jahr eröffnete Konten: deren Anfangsbestand erscheint als **Kontoeröffnung im Berichtsjahr**, nicht als gewöhnliche Einnahme.
- Einnahmen/Ausgaben sowie Umbuchungen sind getrennte Betragsgruppen. Umbuchungen zwischen Konten ergeben bei der Gesamtbetrachtung netto null.
- Rückwirkend stornierte Buchungen bleiben im Journal sichtbar, jedoch nicht im Saldo. Der Bericht bildet den **aktuellen Buchungsstand** ab, keine unveränderliche historische Stichtagssicherung.
- Konto- und Jahresfilter, monatliche Summen, Kategorien und vollständiges Jahresjournal sind verfügbar.

### CSV und PDF

- `GET /cash/export/csv`: vollständig und UTF-8 (mit BOM, Semikolon, Dezimalkomma). Zeichenketten mit potenzieller Tabellenkalkulationsformel werden als Text markiert.
- `GET /cash/export/pdf`: selbst erzeugter PDF-1.4-A4-Bericht mit mehrseitigem Textsatz. Ohne Composer. Das ist ein **technischer Ausdruck**, keine signierte oder zertifizierte Buchführungsunterlage.
- Beide Downloads erfordern `cash.export` und setzen `Cache-Control: no-store`.

### Installation oder Upgrade

- Browser-Erstinstallation richtet **16 Tabellen** ein: `007_receipts.sql` ergänzt genau `vs_kassenbelege`.
- Bestehende alpha.8-Installation: Datenbank und private Anhänge sichern, Programmdateien aktualisieren, anschließend im Projektroot ausführen:

```sh
php bin/migrate-receipts.php
```

- Das Skript verwendet `CREATE TABLE IF NOT EXISTS`; es löscht keine bisherigen Belege oder Buchungen.
- Die Finanzberichte benötigen die Tabellen aus alpha.8 (`vs_konten`, `vs_kassenbuch`, `vs_audit`).
- Datenbank-Restore **ohne** dazugehörigen Speicherordner kann Belegverweise unbrauchbar machen; Speicher-Restore ohne Metadaten hinterlässt nicht zugeordnete Dateien.

### Code-Struktur

| Datei | Aufgabe |
| --- | --- |
| `app/Models/CashReceipt.php`, `CashAnnualReport.php` | Unveränderliche Datenobjekte |
| `app/Reports/CashReportRepository.php`, `PdoCashReportRepository.php` | Lesekontrakt und SQL-Snapshot |
| `app/Services/CashReportService.php` | Buchhalterische Jahresberechnung |
| `app/Reports/CsvExporter.php`, `PdfExporter.php` | Maschinenlesbarer CSV-Export und druckbarer PDF-Bericht |
| `app/Receipts/ReceiptRepository.php`, `PdoReceiptRepository.php` | Metadaten, Zeilensperren und Audit |
| `app/Receipts/ReceiptStorage.php`, `app/Services/ReceiptService.php` | Binärdatei- und Geschäftsvalidierung |
| `app/Controllers/CashDocumentsController.php` | HTTP-Downloads, Upload und Berichtssicht |
| `resources/views/cash/reports.php`, `resources/views/cash/index.php` | Mobile UI |
| `database/migrations/007_receipts.sql`, `bin/migrate-receipts.php` | Installation/Upgrade |
