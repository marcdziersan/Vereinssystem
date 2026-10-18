# Quellcodekatalog – Vereinssystem 2.3.1

Dieser Katalog beschreibt die Funktion **jedes** vorhandenen PHP-Moduls, außerdem dessen funktionsbasierte Schnittstellen. Er ergänzt Inline-PHPDoc und `docs/ARCHITECTURE.md`.

| Pfad | Aufgabe | Funktionen im Modul |
|---|---|---|
| `abteilung_add.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_delete.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_edit.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_mitglieder.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_mitgliedschaft_add.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_mitgliedschaft_delete.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_mitgliedschaft_edit.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_mitgliedschaft_show.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilung_process.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | `verify_abteilungsdaten()` |
| `abteilung_show.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `abteilungen.php` | Abteilungen: Stammdaten, Mitgliedschaft, Umlagen | Prozedurales PHP/HTML |
| `captcha.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `captcha_test.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `cronjob.php` | Hintergrundverarbeitung: Geburtstage, Jubiläen, Dienste | Prozedurales PHP/HTML |
| `dienste_add.php` | Helferdienst- und Kuchenverwaltung | Prozedurales PHP/HTML |
| `dienste_process.php` | Helferdienst- und Kuchenverwaltung | Prozedurales PHP/HTML |
| `dienste_show.php` | Helferdienst- und Kuchenverwaltung | `belegt()` |
| `einstellungen.php` | Systemeinstellungen | Prozedurales PHP/HTML |
| `einstellungen_process.php` | Systemeinstellungen | `verify_einstellungen()` |
| `finanzen.php` | Beitragsverwaltung: Sollstellungen, Zahlungen, Guthaben | Prozedurales PHP/HTML |
| `finanzen_process.php` | Beitragsverwaltung: Sollstellungen, Zahlungen, Guthaben | Prozedurales PHP/HTML |
| `finanzkonto.php` | Beitragsverwaltung: Sollstellungen, Zahlungen, Guthaben | Prozedurales PHP/HTML |
| `inc/action_leiste_abteilungen.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/action_leiste_events.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/action_leiste_index.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/action_leiste_members.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/action_leiste_news.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/action_leiste_orte.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/cash.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `mb_strlen_safe()`, `vk_account()`, `vk_accounts()`, `vk_amount()`, `vk_audit()`, `vk_balances()`, `vk_book()`, `vk_can()`, `vk_create_account()`, `vk_entries()`, `vk_insert_locked()`, `vk_link_payment_locked()`, `vk_parse_signed()`, `vk_rate()`, `vk_ready()`, `vk_require()`, `vk_set_account()`, `vk_set_rate()`, `vk_text()`, `vk_transfer()`, `vk_void()`, `vk_void_linked_payment_locked()` |
| `inc/checkuser.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/connect.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/database.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `__construct()`, `assoc()`, `count()`, `db_data_seek()`, `db_errno()`, `db_error()`, `db_escape()`, `db_fetch_assoc()`, `db_fetch_row()`, `db_insert_id()`, `db_num_rows()`, `db_pdo()`, `db_query()`, `row()`, `seek()`, `vs_action_form()`, `vs_sanitize_text()`, `vs_strftime()` |
| `inc/finance.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `vf_add_payment()`, `vf_all()`, `vf_allocate_locked()`, `vf_allocations_for_year()`, `vf_charges()`, `vf_date()`, `vf_db()`, `vf_escape()`, `vf_exec()`, `vf_generate_month()`, `vf_member()`, `vf_money()`, `vf_one()`, `vf_parse_amount()`, `vf_payments()`, `vf_period()`, `vf_ready()`, `vf_reconcile()`, `vf_require_ready()`, `vf_totals()`, `vf_void_charge()`, `vf_void_payment()`, `vf_year()` |
| `inc/footer.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/head.inc.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | Prozedurales PHP/HTML |
| `inc/pdf.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `mb_strlen_safe_pdf()`, `vk_pdf_bytes()`, `vk_pdf_escape()` |
| `inc/security.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `mb_substr_safe()`, `vs_mail_header_value()`, `vs_safe_html()`, `vs_safe_html_node()`, `vs_safe_link()` |
| `inc/session.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `vs_session_start()` |
| `inc/validation.php` | Zentrale Bibliothek bzw. wiederverwendbares HTML-Modul | `vs_valid_choice()` |
| `index.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `install.php` | Installation und Datenbankmigration | `e()` |
| `kasse.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | `kh()`, `ktoken()` |
| `kasse_admin.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | `ka_h()` |
| `kasse_beleg.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | `vk_private_dir()` |
| `kasse_export.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | Prozedurales PHP/HTML |
| `kasse_import.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | Prozedurales PHP/HTML |
| `kasse_process.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | Prozedurales PHP/HTML |
| `kasse_protokoll.php` | Mehrkonten-Kassenbuch: Aktionen, Berichte, Belege, Rechte | Prozedurales PHP/HTML |
| `login.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `login_process.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `logout.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `member_add.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `member_changepword.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `member_edit.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `member_finanzen.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `member_process.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | `verify_mitgliedsdaten()`, `vs_password_matches()` |
| `member_show.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `member_systemedit.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `members.php` | Mitglieder: Stammdaten, Profil, Familie, Rechte und Beiträge | Prozedurales PHP/HTML |
| `news.php` | Nachrichten: Verwaltung und Sichtbarkeit | Prozedurales PHP/HTML |
| `news_add.php` | Nachrichten: Verwaltung und Sichtbarkeit | Prozedurales PHP/HTML |
| `news_delete.php` | Nachrichten: Verwaltung und Sichtbarkeit | Prozedurales PHP/HTML |
| `news_edit.php` | Nachrichten: Verwaltung und Sichtbarkeit | Prozedurales PHP/HTML |
| `news_process.php` | Nachrichten: Verwaltung und Sichtbarkeit | `verify_newsdaten()` |
| `news_show.php` | Nachrichten: Verwaltung und Sichtbarkeit | Prozedurales PHP/HTML |
| `norights.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `ort_add.php` | Anschriften und Ortsverzeichnis | Prozedurales PHP/HTML |
| `ort_delete.php` | Anschriften und Ortsverzeichnis | Prozedurales PHP/HTML |
| `ort_edit.php` | Anschriften und Ortsverzeichnis | Prozedurales PHP/HTML |
| `ort_process.php` | Anschriften und Ortsverzeichnis | `verify_ortsdaten()` |
| `ort_show.php` | Anschriften und Ortsverzeichnis | Prozedurales PHP/HTML |
| `orte.php` | Anschriften und Ortsverzeichnis | Prozedurales PHP/HTML |
| `rechtesystem.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `statusbar.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `table.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | Prozedurales PHP/HTML |
| `tests/cash_smoke.php` | Technische CLI- und Sicherheitschecks | `check_cash()`, `must_fail()` |
| `tests/finance_smoke.php` | Technische CLI- und Sicherheitschecks | `rejection()`, `verify()` |
| `tests/praxis_db.php` | Technische CLI- und Sicherheitschecks | Prozedurales PHP/HTML |
| `tests/security_231.php` | Technische CLI- und Sicherheitschecks | `expect231()` |
| `tests/smoke.php` | Technische CLI- und Sicherheitschecks | `ok()` |
| `upgrade.php` | Installation und Datenbankmigration | Prozedurales PHP/HTML |
| `veranstaltung_add.php` | Veranstaltungen: Organisation und Termine | Prozedurales PHP/HTML |
| `veranstaltung_delete.php` | Veranstaltungen: Organisation und Termine | Prozedurales PHP/HTML |
| `veranstaltung_edit.php` | Veranstaltungen: Organisation und Termine | Prozedurales PHP/HTML |
| `veranstaltung_show.php` | Veranstaltungen: Organisation und Termine | Prozedurales PHP/HTML |
| `veranstaltungen.php` | Veranstaltungen: Organisation und Termine | Prozedurales PHP/HTML |
| `veranstaltungen_process.php` | Vereinsverwaltung: Oberfläche oder Verwaltungsaktion | `verify_ortsdaten()` |