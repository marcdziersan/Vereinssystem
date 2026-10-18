# Architektur und Buchungsregeln 2.3.0

## Schichten

- **Alte Vereinsmasken**: klassisches PHP/HTML (2013), initialisieren über `inc/connect.inc.php` und `inc/checkuser.inc.php`; historischer PDO-Adapter `inc/database.php`.
- **Beitragsdomäne**: `inc/finance.php` speichert Sollstellungen (`vs_forderungen`), tatsächliche Zahlungen (`vs_zahlungen`) und Verrechnungen (`vs_zuordnungen`).
- **Kassendomäne**: `inc/cash.php` schreibt Konten (`vs_konten`), Kassenbuchungen (`vs_kassenbuch`), Beleg-Metadaten (`vs_belege`), Rechte (`vs_berechtigungen`), Audit (`vs_audit`), Tarife (`vs_tarife`) und Migrationen (`vs_schema_version`).
- **HTTP-Endpunkte**: `kasse.php` ist lesend, `kasse_process.php` ist nur POST; `kasse_import.php` validiert Importdaten zweistufig; `kasse_export.php` und `kasse_beleg.php` erfordern gesonderte Berechtigungen.

## Unverzichtbare Invarianten

1. **Cent** als ganze Zahlen; keine Fließkomma-Beträge für Buchungsarithmetik.
2. **Umbuchung:** zwei Buchungen mit identischer `umbuchung_key`, je eine `umbuchung_aus` und `umbuchung_ein`. Beide gemeinsam in einer Transaktion.
3. **Beitragszahlung:** genau eine verknüpfte `vs_kassenbuch.zahlung_id` pro `vs_zahlungen.id`. Die SQL-Spalte hat einen eindeutigen Index. Bei Storno bleiben beide Datensätze erhalten und werden gemeinsam gekennzeichnet.
4. **Storno:** `storniert_am`, `storniert_von`, `storno_grund`; physische Löschung regulärer Geldbewegungen ist nicht Teil der HTTP-Oberfläche. Der Jahressaldo berücksichtigt nur nicht stornierte Kassenbuchungen.
5. **Saldo:** Anfangsbestand (ab Eröffnungsdatum) + externe Einnahmen + interne Eingänge − externe Ausgaben − interne Ausgänge. Umbuchungen haben über alle Konten die Summe null.
6. **Audit:** Änderungen protokollieren handelnden Nutzer, Aktion, Objekt-ID und Minimaldetails innerhalb der gleichen Transaktion; die Audit-Tabelle ist nicht manipulationssicher gegen DB-Administratoren.
7. **Rechte:** numerische Altrechte bleiben bestehen; Kassenrechte werden separat vergeben. Nur Level 5 darf delegieren, er selbst darf alle Kassenaktionen.
8. **Beitragstarife:** `vs_tarife` nach `gueltig_ab`. Ein neuer Stichtag wirkt nur auf zukünftige Neuberechnungen; existierende Monatsforderungen bleiben unverändert.
9. **Historische Daten:** kein automatisches Erfinden früherer Kassenbuchungen aus 2.1-Zahlungen. Alte Bankdaten und tatsächliche Buchungen sind separat abzugleichen.
10. **Belege:** Der Dateispeicher liegt unter Windows oder Linux außerhalb des Webroots und ist nur über den authentifizierten Download mit Berechtigungsprüfung abrufbar. MIME + SHA256 werden geprüft.

## SQL-Transaktionen

Buchungen, Zahlungen und Umbuchungen beginnen mit `beginTransaction()`, sperren Konten mit `SELECT ... FOR UPDATE` und schreiben Datensätze sowie Audit in einem Commit. Fehler lösen Rollback aus. Auf der Mitgliedsseite verwendet die Beitragsdomäne die Mitgliedszeile als exklusive Sperre für parallele Zahlungsverrechnungen.

## Migrationsablauf

Neuinstallation: `sql/schema.sql` mit acht Originaltabellen, drei Finanztabellen (2.1) und sieben Kassen-/Verwaltungstabellen (2.3). Update: `sql/finance_2_1_0.sql` und `sql/finance_2_3_0.sql`, danach Schema-/Einstellungs-Version auf 2.3.0 setzen. **DDL-Statements sind bei MySQL nicht atomar**. Vorher Backup, danach Anmeldungs-/Buchungstest.

## Nicht enthalten / Einschränkungen

- Keine automatische Bankfeed-/SEPA-Verbindung oder Zuordnung importierter Buchungen zu Vereinsmitgliedern.
- Kein revisionsrechtlich garantiertes unveränderliches Journal; keine elektronische PDF-Signatur.
- Die historischen Altseiten und deren Rechte-/SQL-Builder sind noch nicht vollständig neu geschrieben.
- Der private Speicherpfad benötigt Serverkonfiguration; Belegupload ist andernfalls absichtlich blockiert.
- Vor Produktivbetrieb ist ein Datenbank-Integrations-/Sicherheitstest erforderlich.

## Wartungsstand 2.3.1

- Nachrichten werden beim Speichern und Anzeigen über `inc/security.php` als HTML-Allowlist ausgegeben. Eventhandler, Skripte, fremde Attribute und unsichere Links werden verworfen. Ohne PHP-DOM-Erweiterung erfolgt sichere Textausgabe.
- Bei Mitgliedern, Nachrichten und Abteilungsmitgliedern erfolgt die Suche über PDO-Platzhalter. Zusätzliche Profil-Detailabfragen wurden ebenfalls parametrisiert.
- Das alte `db_escape()` besteht nur als **Übergangshilfe** für historische SQL-Builder. Vorhandene `stripslashes(db_escape(...))`-Kombinationen wurden entfernt; neu geschriebener Code nutzt ausschließlich bindbare PDO-Parameter.
- Der Cronjob kann im Browser nur nach einer expliziten Admin-POST-Aktion gestartet werden. CLI-Cronjobs bleiben unverändert nutzbar.
- Die Datenbank-Selbstprüfung unter `tests/praxis_db.php` ist nur über CLI erreichbar und schreibt keine Daten.
- Das MySQL-`sql_mode=NO_ENGINE_SUBSTITUTION` bleibt für historische Datenschemata aktuell erforderlich; seine Abschaffung benötigt ein separat versioniertes Datenbank- und Query-Refactoring.
