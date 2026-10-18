# Update auf Vereinssystem 2.3.1

Dieses Wartungsrelease baut auf **2.3.0** auf und enthält **keine neuen Tabellen**. Die Finanztabellen aus 2.1.0 und 2.3.0 bleiben erhalten.

1. Vorhandene Webdateien und MySQL-Datenbank sichern.
2. Dateien aus dem ZIP ersetzen, die vorhandene **`inc/config.php` niemals überschreiben**.
3. Bei einer schon vollständig auf 2.3.0 migrierten Installation ist kein zusätzlicher Schema-Schritt nötig; `upgrade.php` kann ausschließlich in der Testinstallation zur Prüfung wiederholt werden.
4. Anmeldung, Nachrichten, Mitglieder-/Abteilungssuche, Soll/Ist, Buchung und Storno in der Testinstallation prüfen. Ablauf siehe `docs/PRAXISTEST-2.3.1.md`.
5. CLI-Selbstcheck: `php tests/praxis_db.php` (nur lesend).
6. `install.php` und `upgrade.php` im öffentlichen Betrieb löschen.

**Geändertes Verhalten:** Browser-Aufrufe des `cronjob.php` zeigen zunächst ein ausdrücklich zu bestätigendes Admin-Formular. E-Mails werden erst durch POST ausgelöst. Bereits gespeicherte historische Nachrichten-HTMLs werden beim Anzeigen auf sichere Tags/Attribute begrenzt.

**Datenhaltung:** Die gespeicherten Forderungen, Zahlungseingänge, Kassenbewegungen und Stornos werden bei diesem Update nicht umgeschrieben.