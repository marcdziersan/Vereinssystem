# MVC 3.0.0-alpha.10.2 – Systemeinstellungen und Änderungsprotokoll

## Umfang

- Ausschließlich Administration (Rechtestufe 5) kann über `/admin/settings` Vereinsanschrift, Website, Vereinskontakt, E-Mail-Absender sowie **Vorbereitungswerte** für PHP-mail()/SMTP einstellen.
- Benachrichtigungstypen (Beitrags-/Terminerinnerungen, Geburtstage) sind ausschließlich konfigurierbare Optionen; sie starten **keinen** Versand.
- `/admin/settings/logs` zeigt eine chronologische, nur administrativ lesbare Änderungshistorie (Akteur-ID, Versionsnummer, Zeitpunkt, geänderte nichtgeheime Felder).
- HTTP-Methoden GET/POST, Login, Admin-Middleware und CSRF-Schutz werden serverseitig geprüft.
- `SettingsService` validiert streng eine feste Feldmenge und verwirft unbekannte Felder. Werte werden in Views HTML-escaped.
- `PdoSettingsRepository` speichert die Einstellungen atomar mit einer Versionsnummer; zwei parallele Bearbeitungen derselben Version können nicht stillschweigend überschrieben werden. Bei Änderung entsteht genau ein Audit-Datensatz in derselben InnoDB-Transaktion. Unverändertes Speichern erzeugt keinen weiteren Audit-Eintrag.
- SMTP-Passwörter, API-Schlüssel und andere Geheimnisse werden **nicht** eingegeben, protokolliert oder in `vs_system_settings` gespeichert. Ein späteres Versandmodul muss SMTP-Zugangsdaten gesondert aus einer geschützten Server-Konfiguration beziehen.

## Einrichtung

**Neuinstallation:** Der Browser-Installer legt nun 18 Tabellen an, darunter `vs_system_settings` und `vs_system_audit`. In der ersten Anzeige sind neutrale Standardwerte sichtbar; die erste Änderung schreibt den initialen Datensatz. Andere Module übernehmen die Angaben noch nicht automatisch in PDFs oder Mail-Nachrichten.

**Bestehende Alpha 10.1:** Zuerst eine vollständige Sicherung von MySQL/MariaDB und privaten Belegdateien anlegen. Danach neue Projektdateien kopieren und im WAMP-Projektordner ausführen:

```sh
php bin/migrate-settings.php
php bin/schema-check.php
```

Die Migration legt nur fehlende Tabellen an; Abweichungen an vorhandenen Tabellen führen zum Abbruch. Sie löscht oder überschreibt keine bisherigen Mitgliedsdaten. Wird sie übersprungen, ist das Einstellungsmodul nicht verfügbar; bestehende Kernmodule bleiben unverändert.

## Datenmodell

- `vs_system_settings`: genau ein konfigurierbarer Datensatz `id=1`, `values_json` (keine Geheimnisse), `revision`, `updated_by`, `updated_at`.
- `vs_system_audit`: append-only Änderungsverlauf mit `actor_id`, `revision`, `changes_json` (vorher/nachher) und `created_at`. Keine Lösch- oder Änderungsroute.
- Revisionen beginnen bei 0 (noch nicht gespeichert); der erste geänderte Stand erhält Revision 1.
- Keine Abhängigkeit von Composer oder Drittanbieter-Bibliotheken; PHP 8+ und PDO MySQL/MariaDB.

## Qualitätsgrenzen

`php tests/settings_smoke.php` überprüft Validierung, Revisionskonflikte, Audit-Verhalten und HTTP-Zugriffsregeln gegen einen In-Memory-Repository-Doppelgänger. Alle bestehenden PHP-Prüfprogramme werden ebenfalls ausgeführt.

Echte InnoDB-Transaktionen (einschließlich gleichzeitig bearbeitender Browser), SMTP-Serverkonnektivität, Apache/WAMP-Konfiguration und der reale Versand sind **nicht** durch diese lokale Testumgebung abgenommen. SMTP-Versand, Cron-Jobs und Erinnerungsläufe gehören frühestens in Phase 10.4; die eigentliche Einbindung ist noch nicht implementiert.
