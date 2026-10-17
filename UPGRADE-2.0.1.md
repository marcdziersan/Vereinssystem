# Update auf 2.0.1

1. Vor dem Update **MySQL/MariaDB-Datenbank und Webspace vollständig sichern**.
2. Bereits installierte Konfiguration `inc/config.php` beibehalten. Sie enthält vertrauliche Zugangsdaten und darf niemals committed werden.
3. Neue Projektdateien hochladen. Bei bestehender Einrichtung `install.php` nicht neu aufrufen. Anschließend vom öffentlich erreichbaren Server löschen.
4. In einer Testumgebung Login, Mitglied anlegen/bearbeiten, Beiträge, Abteilungen, Veranstaltungen, Dienste und Berechtigungen im Browser prüfen.
5. PHP 8 mit `pdo_mysql` und MySQL/MariaDB erforderlich. Kein neuer Datenbankschema-Migrationsschritt in 2.0.1.

**Bekannte Grenzen:** Die historischen String-SQL-Abfragen brauchen weitere Modernisierung. Eine echte Datenbankintegration wurde in der Entwicklungsumgebung nicht ausgeführt. Die Soll-/Ist-Beitragsbuchhaltung folgt erst mit 2.1.
