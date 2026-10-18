# Upgrade Vereinssystem 2.0.x → 2.1.0

**Unbedingt zuerst eine vollständige Datenbank- und Dateisicherung anlegen.**

1. Neue Programmdateien in eine Testinstallation hochladen. `inc/config.php` behalten und nie mit einer Beispielkonfiguration überschreiben.
2. Unter PHP 8 mit `pdo_mysql` und MySQL/MariaDB anmelden.
3. Als Administrator mit Rechtelevel 5 die Seite `upgrade.php` öffnen und die Migration **per POST** starten.
4. Die drei Tabellen `vs_forderungen`, `vs_zahlungen` und `vs_zuordnungen` werden mit `CREATE TABLE IF NOT EXISTS` ergänzt. Alte Tabellen bleiben erhalten. Der Vorgang kann wiederholt werden.
5. Unter Einstellungen Beitragssätze prüfen. Unter Finanzen einen Testmonat auswählen, die Sollstellungen erzeugen, Teilzahlung und Guthaben erfassen, Storno durchführen und Soll/Ist vergleichen.
6. Einen Test auch mit ruhenden Mitgliedern, Minderjährigen und Abteilungsumlagen durchführen.
7. Erst danach auf dem Produktivserver aktualisieren; `upgrade.php` dort anschließend entfernen.

**Wichtig:** Die Migration selbst erzeugt keine Forderungen für vergangene Monate. Werden vergangene Monate bewusst erzeugt, gelten die **aktuellen** Beitragssätze und Mitgliedsdaten; sie entsprechen möglicherweise nicht dem historischen Stand.

Eine Neuinstallation verwendet bereits `sql/schema.sql` mit elf Tabellen und benötigt `upgrade.php` nicht.