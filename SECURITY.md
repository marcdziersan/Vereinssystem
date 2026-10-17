# Sicherheitshinweise

- **Keine Echtdaten verwenden, bevor die Installation und die Berechtigungen am Server getestet sind.** Dieses Projekt stammt aus 2013 und wurde für PHP 8/PDO umgebaut, nicht neu auditiert.
- Webserver nur über HTTPS bereitstellen. `inc/config.php` wird vom Installer geschrieben, enthält das Datenbankpasswort und darf niemals als Download ausgeliefert werden. `inc/.htaccess` blockiert unter Apache direkten Zugriff auf den Ordner.
- `install.php` nach erfolgreicher Installation löschen. Die Datei sperrt sich zusätzlich bei bestehender `inc/config.php`.
- Der initiale Admin benötigt ein mindestens 12-stelliges Passwort; Altpasswörter werden beim Login automatisch von MD5 auf `password_hash()` migriert.
- Datensicherung der gesamten Datenbank vor jedem Update; der Installer ist nicht für Downgrades oder komplexe Schemamigrationen geeignet.
- Der PDO-Adapter unterstützt explizite Parameter (`db_query('... WHERE id=?',[$id])`). Vorhandene String-SQL-Ausdrücke laufen mit PDO-Quoting und einem sitzungsbezogenen, weniger strikten MySQL-Kompatibilitätsmodus. Für einen produktiven Sicherheitshärtungsschritt sollten alle Altabfragen vollständig parametrisiert und die SQL-Abfragen SQL-Mode-konform umgeschrieben werden.
- Der Login nutzt eine begrenzte Session-basierte Rate-Limit-Sperre, keinen zentralen IP-Schutz. Vorgeschaltet kann eine serverseitige Login-Rate-Limit-Regel sinnvoll sein.
- POST-Formulare verwenden CSRF-Tokens; die frühere Diensteverwaltung mit zustandsändernden GET-Aktionen wurde auf POST umgestellt.
- Für Mitglieder-, Eltern-, Gesundheits- oder Bankdaten gelten entsprechende datenschutzrechtliche Pflichten. Nicht freigeben, bevor Datenschutz-/Rollenprüfung erfolgt ist.
