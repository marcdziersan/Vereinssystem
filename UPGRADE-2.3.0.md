# Upgrade auf Vereinssystem 2.3.0

**Vorab: MySQL/MariaDB-Datenbank, Webspace und alte Konfiguration vollständig sichern.**

1. Die vollständige neue Version in einem getrennten Testverzeichnis auspacken.
2. Vorhandene `inc/config.php` übernehmen; nicht ins öffentliche Repository stellen.
3. Auf einer **Datenbankkopie** als Mitglied mit Rechtelevel 5 anmelden und `upgrade.php` aufrufen.
4. Per POST die Migration bestätigen; sie fügt bei Bedarf drei 2.1-Tabellen und sieben neue 2.3-Tabellen hinzu. Das Skript benutzt `CREATE TABLE IF NOT EXISTS` und ist grundsätzlich erneut ausführbar. Es führt keine `DROP TABLE`-Aktionen aus.
5. In `kasse.php` neue Konten mit nachgewiesenen Anfangsbeständen eröffnen. Bank-/Bar-/PayPal-Buchungen werden **nicht** rückwirkend automatisch aus bestehenden Mitgliedszahlungen abgeleitet.
6. Beiträge, Konten, Umbuchungen, Storno, Rechte und Ausgaben mit Beispielwerten prüfen; Gegencheck mittels Kontoauszug und Kassenbelegen.
7. `VS_PRIVATE_STORAGE_DIR` auf einen sicheren absoluten Pfad **außerhalb von `DOCUMENT_ROOT`** setzen, wenn Beleguploads benötigt werden.
8. Erst nach Bestehen der Tests produktiv umstellen, dann `upgrade.php` und `install.php` auf dem öffentlichen Server entfernen.

**Hinweis:** MySQL-Schema-DDL ist nicht vollständig transaktional. Fehlschläge im Log beheben und den Upgrade-Schritt wiederholen. Alte Live-Buchungen niemals pauschal kopieren/importieren, um doppelte Einnahmen zu vermeiden. Diese Version ersetzt nicht den unabhängigen Live-Test unter MySQL.