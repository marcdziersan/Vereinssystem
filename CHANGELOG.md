# Änderungsprotokoll

## 2.0.1 – 18.10.2026
- Fehlende PDO-Kerndateien, sichere Session-Verwaltung, SQL-Schema und lokales jQuery im GitHub-Repository ergänzt.
- Alte MySQL-Verbindung durch PDO-Konfiguration ersetzt.
- Wertebereichsprüfung für Mitgliedsart, Abrechnung und Rechte korrigiert.
- Neue separate Ganzzahlvalidierung.
- Versionsstand und Dokumentation aktualisiert.
- `inc/config.php` in .gitignore ausgeschlossen.
- Keine Änderungen an bestehenden Tabellen oder gespeicherten Vereinsdaten.
- Live-Integration mit MySQL/MariaDB noch nicht nachgewiesen.

## 2.0.0 – 17.10.2026
- PHP 8 und PDO-Kern; Installationsassistent, Passwortmigration, Session- und CSRF-Schutz.

**Hinweis:** Soll-/Ist-Kassenbuch und Zahlungsbuchungen sind nicht Bestandteil von 2.0.1.
