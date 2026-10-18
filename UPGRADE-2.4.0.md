# Upgrade auf Vereinssystem 2.4.0

**Art:** UI-Update ohne Veränderung der 18 vorhandenen Datenbanktabellen.

1. Projektdateien und Datenbank vollständig sichern.
2. Dateien aus dem 2.4.0-Paket in die vorhandene Webinstallation übertragen, einschließlich `css/app.css`, `css/setup.css`, `assets/js/app.js`, `inc/ui.php`.
3. Die vorhandene Konfiguration `inc/config.php` **nicht ersetzen**.
4. Keine erneute Erstinstallation ausführen. `install.php` und `upgrade.php` nach der Einrichtung weiterhin nicht öffentlich bereitstellen.
5. Browser neu laden (gegebenenfalls Cache leeren). Der Asset-Cache-Buster lautet `v=2.4.0`.
6. Mit Benutzerkonten verschiedener Rechtelevel die Navigation und den Zugriff auf Mitgliederdaten und Kasse prüfen.
7. Die Bedienwege aus [docs/UX_UI_2.4.0.md](docs/UX_UI_2.4.0.md) auf Smartphone, Tablet und Desktop durchgehen.

Die vorhandenen PHP-Aktionen, Datenfelder, Belege, Zahlungen und Benutzerrechte werden nicht geändert. Dieses Release ist kein Datenbank- oder Finanzupgrade.
