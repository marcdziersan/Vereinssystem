# SQL-Migrationsregister – 2.3.1

## Ziel

Neue Abfragen verwenden native PDO-Parameter. Die historische Übergangsfunktion `db_escape()` wird nur noch für bestehende Query-Builder unterstützt; `stripslashes(db_escape(...))` ist verboten. Nach der letzten Umstellung kann der ältere SQL-Mode entfernt werden.

## Noch bestehende historische SQL-Builder

| Datei | `db_escape()`-Vorkommen |
|---|---:|
| `abteilung_add.php` | 6 |
| `abteilung_edit.php` | 6 |
| `abteilung_mitgliedschaft_add.php` | 6 |
| `abteilung_process.php` | 18 |
| `abteilungen.php` | 4 |
| `cronjob.php` | 1 |
| `dienste_add.php` | 2 |
| `dienste_process.php` | 10 |
| `einstellungen_process.php` | 20 |
| `inc/database.php` | 2 |
| `member_add.php` | 4 |
| `member_edit.php` | 12 |
| `member_process.php` | 52 |
| `news_process.php` | 10 |
| `ort_process.php` | 16 |
| `orte.php` | 6 |
| `veranstaltung_add.php` | 2 |
| `veranstaltung_edit.php` | 2 |
| `veranstaltungen.php` | 1 |
| `veranstaltungen_process.php` | 38 |

## Abfolge

1. Leseabfragen mit Suche und Navigationsparametern auf `db_query($sql, [$params])` umstellen.
2. Historische INSERT/UPDATE/DELETE-Aktionen mit dynamischen SQL-Strings auf vorbereitete Statements und explizite Feldlisten übertragen.
3. HTML-/JavaScript-Ausgabe bei der Umstellung konsequent kontextgerecht escapen.
4. Alle historischen `GROUP BY`-/impliziten MySQL-Defaults prüfen, erst danach `sql_mode=NO_ENGINE_SUBSTITUTION` ablösen.
5. Änderungen an Mitgliedern, Abteilungen und Diensten parallel in der Browseroberfläche kontrollieren.

**Inventur:** 20 Dateien mit insgesamt 218 historischen `db_escape()`-Aufrufstellen. Das ist ein Ablöseplan, keine Behauptung, diese Stellen seien bereits vollständig migriert.
