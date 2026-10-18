# Quellcodedokumentation und Dateikatalog

Version: **2.3.0**. Alle hier aufgeführten PHP-Dateien besitzen einheitliche LF-Zeilenenden ohne nachgestellte Leerzeichen. Neue Module verwenden Funktionskommentare und typisierte Funktionsparameter.

**Hinweis zur Quellformatierung:** Die historischen PHP/HTML-Mischdateien enthalten eng verschachtelte HTML-Ausgabe und SQL-Builder aus 2013. Aus Gründen der Funktions- und Datenkompatibilität wurden sie bewusst **nicht blind automatisch umstrukturiert**. Ihr bestehender Stil wurde bereinigt; eine vollständige manuelle PSR-12-Neufassung jedes Altmoduls ist damit nicht behauptet.

## Vollständiger PHP-Modulkatalog

| Modul | Zweck | Deklarierte Funktionen |
|---|---|---|
| `abteilung_add.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_delete.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_edit.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_mitglieder.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_mitgliedschaft_add.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_mitgliedschaft_delete.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_mitgliedschaft_edit.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_mitgliedschaft_show.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilung_process.php` | Abteilungen und Mitgliedszugehörigkeiten | `verify_abteilungsdaten()` |
| `abteilung_show.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `abteilungen.php` | Abteilungen und Mitgliedszugehörigkeiten | prozedurales PHP / HTML |
| `captcha.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `captcha_test.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `cronjob.php` | E-Mails / Hintergrundaufgaben | prozedurales PHP / HTML |
| `dienste_add.php` | Helferdienste | prozedurales PHP / HTML |
| `dienste_process.php` | Helferdienste | prozedurales PHP / HTML |
| `dienste_show.php` | Helferdienste | `belegt()` |
| `einstellungen.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `einstellungen_process.php` | Vereinsverwaltung / Systemfunktion | `verify_einstellungen()` |
| `finanzen.php` | Mitgliedsbeiträge | prozedurales PHP / HTML |
| `finanzen_process.php` | Mitgliedsbeiträge | prozedurales PHP / HTML |
| `finanzkonto.php` | Mitgliedsbeiträge | prozedurales PHP / HTML |
| `inc/action_leiste_abteilungen.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/action_leiste_events.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/action_leiste_index.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/action_leiste_members.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/action_leiste_news.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/action_leiste_orte.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/cash.php` | Gemeinsame PHP-Module | `vk_ready()`, `vk_can()`, `vk_require()`, `vk_audit()`, `vk_parse_signed()`, `vk_amount()`, `vk_text()`, `mb_strlen_safe()`, `vk_account()`, `vk_accounts()` |
| `inc/checkuser.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/connect.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/database.php` | Gemeinsame PHP-Module | `__construct()`, `count()`, `assoc()`, `row()`, `seek()`, `db_pdo()`, `db_query()`, `db_fetch_assoc()`, `db_fetch_row()`, `db_num_rows()` |
| `inc/finance.php` | Gemeinsame PHP-Module | `vf_db()`, `vf_one()`, `vf_all()`, `vf_exec()`, `vf_escape()`, `vf_money()`, `vf_parse_amount()`, `vf_date()`, `vf_period()`, `vf_year()` |
| `inc/footer.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/head.inc.php` | Gemeinsame PHP-Module | prozedurales PHP / HTML |
| `inc/pdf.php` | Gemeinsame PHP-Module | `vk_pdf_escape()`, `vk_pdf_bytes()`, `mb_strlen_safe_pdf()` |
| `inc/session.php` | Gemeinsame PHP-Module | `vs_session_start()` |
| `inc/validation.php` | Gemeinsame PHP-Module | `vs_valid_choice()` |
| `index.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `install.php` | Installation | `e()` |
| `kasse.php` | Kassen- und Rechteverwaltung | `kh()`, `ktoken()` |
| `kasse_admin.php` | Kassen- und Rechteverwaltung | `ka_h()` |
| `kasse_beleg.php` | Kassen- und Rechteverwaltung | `vk_private_dir()` |
| `kasse_export.php` | Kassen- und Rechteverwaltung | prozedurales PHP / HTML |
| `kasse_import.php` | Kassen- und Rechteverwaltung | prozedurales PHP / HTML |
| `kasse_process.php` | Kassen- und Rechteverwaltung | prozedurales PHP / HTML |
| `kasse_protokoll.php` | Kassen- und Rechteverwaltung | prozedurales PHP / HTML |
| `login.php` | Anmeldung | prozedurales PHP / HTML |
| `login_process.php` | Anmeldung | prozedurales PHP / HTML |
| `logout.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `member_add.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `member_changepword.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `member_edit.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `member_finanzen.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `member_process.php` | Mitglieder-/Benutzerverwaltung | `vs_password_matches()`, `verify_mitgliedsdaten()` |
| `member_show.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `member_systemedit.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `members.php` | Mitglieder-/Benutzerverwaltung | prozedurales PHP / HTML |
| `news.php` | Nachrichten | prozedurales PHP / HTML |
| `news_add.php` | Nachrichten | prozedurales PHP / HTML |
| `news_delete.php` | Nachrichten | prozedurales PHP / HTML |
| `news_edit.php` | Nachrichten | prozedurales PHP / HTML |
| `news_process.php` | Nachrichten | `verify_newsdaten()` |
| `news_show.php` | Nachrichten | prozedurales PHP / HTML |
| `norights.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `ort_add.php` | Orte und Anschriften | prozedurales PHP / HTML |
| `ort_delete.php` | Orte und Anschriften | prozedurales PHP / HTML |
| `ort_edit.php` | Orte und Anschriften | prozedurales PHP / HTML |
| `ort_process.php` | Orte und Anschriften | `verify_ortsdaten()` |
| `ort_show.php` | Orte und Anschriften | prozedurales PHP / HTML |
| `orte.php` | Orte und Anschriften | prozedurales PHP / HTML |
| `rechtesystem.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `statusbar.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `table.php` | Vereinsverwaltung / Systemfunktion | prozedurales PHP / HTML |
| `tests/cash_smoke.php` | Datenbankfreie Smoke-/Regressionsprüfung | `check_cash()`, `must_fail()` |
| `tests/finance_smoke.php` | Datenbankfreie Smoke-/Regressionsprüfung | `verify()`, `rejection()` |
| `tests/smoke.php` | Datenbankfreie Smoke-/Regressionsprüfung | `ok()` |
| `upgrade.php` | Schema-Migration | prozedurales PHP / HTML |
| `veranstaltung_add.php` | Veranstaltungen | prozedurales PHP / HTML |
| `veranstaltung_delete.php` | Veranstaltungen | prozedurales PHP / HTML |
| `veranstaltung_edit.php` | Veranstaltungen | prozedurales PHP / HTML |
| `veranstaltung_show.php` | Veranstaltungen | prozedurales PHP / HTML |
| `veranstaltungen.php` | Veranstaltungen | prozedurales PHP / HTML |
| `veranstaltungen_process.php` | Veranstaltungen | `verify_ortsdaten()` |

## Neue PHP-Module

- `inc/cash.php`: Domain-Logik und PDO-Transaktionen für Konten, Einnahmen, Ausgaben, atomare Umbuchungen, Storno, Beitragstarife, Audit und Zugriffsrechte.
- `inc/pdf.php`: eigenständige Text-PDF-Erzeugung ohne JavaScript oder Fremdbibliotheken.
- `kasse.php`: geschützte responsive Kassenbuchoberfläche.
- `kasse_process.php`: ausschließlich POST mit CSRF und kurzlebiger Operationsnonce.
- `kasse_import.php`: zweistufiger CSV-Import mit validierter Vorschau und persistentem SHA-256-Duplikatschlüssel.
- `kasse_export.php`: abgesicherter CSV-/PDF-Download und Buchungsquittungen.
- `kasse_beleg.php`: privater Upload/Download mit MIME-, SHA-256- und Pfad-Prüfung.
- `kasse_admin.php`: Rechteverwaltung (Admin Level 5) und versionierte Beitragssätze.
- `kasse_protokoll.php`: schreibgeschützte Audit-Historie.

## Wartungsregeln

- Keine Datenbankpasswörter einchecken. `inc/config.php` bleibt eine lokale Datei.
- Geschriebene Geldbeträge bleiben INTEGER-Cent; SQL-Abfragen neuer Module sind parametrisiert.
- Kassenbuchung + Audit benötigen eine gemeinsame Transaktion.
- Eine Umbuchung besteht aus zwei verbundenen Datensätzen und darf nur gemeinsam storniert werden.
- Bestehende Sollstellungen sind unveränderliche historische Beträge.
- Uploads erfordern einen separaten Speicher außerhalb des öffentlich erreichbaren Webroots.
- Das gesamte originale HTML wurde nicht neu designt. Moderne Module sind responsiv, Altseiten verbleiben in der historisch gewachsenen Struktur.