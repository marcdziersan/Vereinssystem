# Vereinssystem – Änderungsprotokoll

## 2.1.0 – 18.10.2026

### Neu
- Eigenständige Beitragsbuchhaltung mit **Monatsforderungen** (`vs_forderungen`), **Zahlungseingängen** (`vs_zahlungen`) und **Zahlungszuordnungen** (`vs_zuordnungen`).
- Verwaltungsübersicht mit Jahres-Soll/Ist, offenen Beiträgen, Guthaben und Mitgliedersuche (`finanzen.php`).
- Persönliches Mitgliedskonto mit Beitragspositionen, Zahlungshistorie und Zahlungseingabe (`finanzkonto.php`).
- Zahlungen als Teilzahlungen und als Verrechnung über mehrere Monate; unverrechnete Beträge als Guthaben.
- Optionale Erfassung des tatsächlichen Zahlers (z. B. Elternteil), Zahlungsweg, Buchungsdatum, Referenz und Bemerkung.
- Nachträgliche Guthabenverrechnung auf offene Forderungen.
- Storno von Zahlungen und unbezahlten Forderungen mit Begründung, Bearbeiter und Zeitstempel; keine spurenlose Löschung.
- Wiederholbare Schema-Migration für bestehende Installationen mit Admin-Login und POST-/CSRF-Schutz (`upgrade.php`).
- Neue responsive Finanzmasken über `css/finance.css` (Altmasken bleiben unverändert).
- Tests zu Betrags-, Datums-, Monats- und SQL-Schema-Validierung (`tests/finance_smoke.php`).

### Geändert
- Vollständiger Erstinstaller enthält nun elf statt acht Tabellen; Version auf 2.1.0 angehoben.
- Hauptnavigation enthält Finanzen, bestehende Mitgliederfinanzübersicht verweist zur Buchungsansicht.
- Finanzabfragen mit vorbereiteten PDO-Statements; transaktionale Zahlungs-/Zuordnungslogik.
- README um vollständige Buchungsanleitung, Rechte, Grenzen, Herkunft und Update-Pfad ergänzt.

### Grenzen / Migration
- Vor dem Upgrade **Backup** anlegen. Neue Forderungen werden nicht rückwirkend automatisch erzeugt.
- Rückwirkend erzeugte Forderungen basieren auf **heutigen** Stammdaten/Beitragssätzen; historische Tarife sind noch nicht versioniert.
- Buchungen werden manuell erfasst; kein Bankabgleich, SEPA-Mandat, Mehrkontenkassenbuch oder automatischer Mailversand für Rückstände.
- Vollständiger Live-Test mit MySQL/MariaDB und Echthoster **noch nicht abgeschlossen**; PHP-Syntax und datenbankfreie Smoke-Checks sind ausgeführt.

## 2.0.1 – 18.10.2026
- PDO-Repositoryreparatur, sichere Session-Verwaltung, SQL-Schema und lokales jQuery ergänzt.
- Numerische Wertebereichsprüfungen für Mitgliedsart, Abrechnung und Rechte korrigiert.
- Technische Dokumentation und Konfigurationsschutz ergänzt.

## 2.0.0 – 17.10.2026
- Umstellung des ursprünglichen Systems auf PHP 8 und PDO-Kompatibilitätsschicht.
- Geführte `install.php`, Passwortmigration von MD5 zu `password_hash`, Sessions, CSRF-Schutz.

## 2012/2013 – Original
- VereinsSystem von Nicolas Jourdan und Sebastian Fuss: Mitglieder, Abteilungen, Adressen, Veranstaltungen, Dienste und Nachrichten.
- Historische Erlaubnis zur kostenlosen nichtkommerziellen Nutzung und Erweiterung bleibt gültig.