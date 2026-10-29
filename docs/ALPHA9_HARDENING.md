# Alpha 9 – Nachhärtung und Freigabegrenzen

**Zielbranch:** `refactor/mvc-architecture` (kein dritter Entwicklungsbranch). `master` wird nicht verändert. Der Stand enthält zusätzlich die 10.1-Kontopflege.

## Umgesetzte technische Maßnahmen

| Paket | Änderung | Nachprüfbarer Nachweis |
|---|---|---|
| 9.1 Release | Version, Architekturverweise und GitHub-Archiv an denselben Commit gebunden; `bin/release-audit.php` berechnet Datei-SHA-256 und prüft PHP-Syntax | `php bin/release-audit.php --manifest=release-manifest.json` |
| 9.2 Installer | `SchemaInspector` erkennt abweichende Spalten, SQL-Typen, InnoDB-Engine und fehlende/entwertete Indizes vor der Installation (bestehende Tabellen) und danach (alle 16 Tabellen). Keine automatischen `ALTER`/`DROP`. Bestehender Datenbestand ohne aktiven Administrator wird nicht stillschweigend als installiert ausgegeben | `php bin/schema-check.php` und `php tests/schema_smoke.php` |
| 9.3 Rechte | Einheitliche Authentifizierungs-, Berechtigungs- und CSRF-Middleware auch für ältere MVC-Module. Objektbezogene Freigaben bleiben im Controller erhalten | HTTP-Routenmatrix im Quellcode; vorhandene Auth-/Mitglieder-/Orte-Tests |
| 9.4 Kasse/Finanzen | Bestehende atomare PDO-/InnoDB-Buchungen bewusst nicht umgeschrieben; sichere Schema-Prüfung vorgeschaltet. Bestehende Fachprüfungen sind weiterhin im Paket | `tests/finance_smoke.php`, `tests/cash_smoke.php`; echte Datenbanklastfälle unten offen |
| 9.5 Belege | Belegverzeichnis wird auf echte private `storage/`-Lage geprüft; Symlink-/Webroot-Ziele abgewiesen; Berechtigungen und CSRF bleiben vorgeschaltet | `StoragePolicy` und `tests/receipt_storage_smoke.php`; Webserverprüfung unten offen |
| 9.6 Export | Vorhandene Read-Only-Transaktion und vollständige CSV/PDF-Datenerfassung beibehalten; Fachtests weiterhin enthalten | `tests/report_receipt_smoke.php`; Browser-/PDF-Abgleich unten offen |
| 9.7 Mobile UI | Einheitliche Einstellungen-Oberfläche; veraltete Architektur-/Kontoseitentexte bereinigt | Browserprüfung mit Desktop und 390px noch nötig |
| 9.8 Freigabe | Datei-Hashmanifest, PHP-Lint und dokumentierte Restabnahme | `bin/release-audit.php`; kein produktiver Release-Status |

## Reproduzierbarer lokaler Check

```cmd
php bin/release-audit.php --manifest=release-manifest.json
php tests/kernel_smoke.php
php tests/auth_smoke.php
php tests/member_smoke.php
php tests/alpha4_smoke.php
php tests/event_smoke.php
php tests/news_smoke.php
php tests/finance_smoke.php
php tests/cash_smoke.php
php tests/report_receipt_smoke.php
php tests/schema_smoke.php
php tests/receipt_storage_smoke.php
php tests/account_smoke.php
php bin/schema-check.php
```

`release-manifest.json` enthält bewusst keine lokalen Geheimnisse oder privaten Belege; das Manifest sollte **nicht** zum Projekt veröffentlicht werden, wenn es lokal erzeugt wird. Vor jeder Änderung an einer bestehenden Datenbank zuerst SQL-Dump und `storage/receipts/` gemeinsam sichern. Das Prüfskript `schema-check.php` ist ausschließlich lesend. Die sechs fachlichen CLI-Migrationen prüfen bestehende Tabellen vor und nach additiven CREATEs und brechen bei Abweichungen ab. Ist das Schema nicht kompatibel, bricht der Installer kontrolliert ab. Es wird keine automatische 2.4.0-Datenmigration behauptet.

## Offene Abnahmearbeiten (nicht als bestanden deklariert)

1. **WAMP/Apache:** Root-Rewrite, sämtliche direkten `/storage/`- und `/config/`-Aufrufe sowie geschützte Belegdownloads gegen echte HTTP-Antworten prüfen.
2. **MySQL/MariaDB:** Leere Neuinstallation, echte Datenbankkopie aus 2.4.0, Schemaabgleich und Administrator-Login auf dem Zielhost prüfen.
3. **InnoDB:** Parallele Zahlungen, doppelte Sollstellung, Teilzahlungen, Konto-Umbuchungen, Stornos, Fremdschlüsselfehler und Rollback per tatsächlichem Datenbanktest abgleichen.
4. **CSV/PDF:** 301+ Buchungen, zwei Kalenderjahre, unterjährig eröffnete und deaktivierte Konten, internationale Zeichen und große Exporte in den Zielprogrammen öffnen und Zahlen abgleichen.
5. **Belege/Restore:** Datenbank und Dateispeicher gemeinsam sichern, wiederherstellen und SHA-256-Integrität an echten Dateien kontrollieren.
6. **UI:** Ansichten auf 390px sowie Desktop inklusive Tastaturbedienung und langen Tabellen überprüfen.

Die Freigabe als produktiver RC erfolgt ausdrücklich erst nach dieser Abnahme. Offene Punkte können nicht durch in-memory-PHP-Prüfungen ersetzt werden.
