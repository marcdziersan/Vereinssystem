# MVC-Beitragsbuchhaltung – 3.0.0-alpha.7

## Fachliche Regeln

Die Beitragsverwaltung ist eine eigene OOP-Fachdomäne. Der Controller verarbeitet ausschließlich HTTP, der `FinanceService` validiert Eingaben und Geschäftsaktionen, `FinanceRepository` definiert die Schnittstelle und `PdoFinanceRepository` speichert sie transaktional in MySQL/InnoDB. Datenobjekte liegen unter `app/Models`. Die Route-Middleware prüft den Zugriff **vor** jeder Buchung.

## Datenhaltung

| Tabelle | Bedeutung |
|---|---|
| `vs_tarife` | Beitragsart, Abteilung, Gültigkeitsbeginn, Cent und Ersteller. Datierte Einträge sind unveränderlich. |
| `vs_forderungen` | Monatliche Sollbeträge in Cent; eindeutige Kombination aus Mitglied, Periode, Art und Abteilung. |
| `vs_zahlungen` | Tatsächliche Zahlungseingänge mit Datum, Weg und optionaler Referenz. |
| `vs_zuordnungen` | Verrechnung einer Zahlung auf eine oder mehrere Forderungen. |

Alle vier Tabellen stammen strukturell aus `master` und werden **additiv** eingerichtet. Keine bestehenden Mitglieder oder Finanztabellen werden gelöscht.

## Soll/Ist und Guthaben

- **Soll (ausgewähltes Jahr):** Summe aktiver Forderungen für Perioden Januar bis Dezember.
- **Ist:** Nur Zahlungsverrechnungen aus **nicht stornierten** Zahlungen auf die aktiven Forderungen des ausgewählten Jahres.
- **Offen:** `max(0, Soll − Ist)`.
- **Guthaben:** Alle aktiven Zahlungseingänge des Mitglieds **über sämtliche Jahre** minus die bereits zugeordneten Centbeträge. Guthaben ist daher bewusst nicht auf das angezeigte Jahr begrenzt.

Eine Zahlung muss nicht vollständig zugeordnet sein. Ein späteres `reconcile()` verteilt freies Guthaben auf offene Forderungen nach Fälligkeit. Eine Zahlung kann bevorzugt auf eine konkrete Forderung desselben Mitglieds gebucht werden. Die Funktion nimmt nur offene Beträge; Überzahlungen werden nicht stillschweigend gekappt, sondern zu Guthaben.

## Tarif-/Beitragsbildung

Ein neuer Tarif gilt ab seinem `gueltig_ab`-Datum für folgende Monatserste. Der jeweils gültige Kinder-/Erwachsenentarif wird anhand des Alters am Monatsersten bestimmt (unter 18 Jahren: Kinder). Ruhende Mitgliedschaften (`mitgliedschaft=2`) und inaktive Mitglieder werden übersprungen. Für aktive Abteilungszugehörigkeiten wird zuerst ein datierter Abteilungstarif (auch **0,00 €** zum Aussetzen), andernfalls der Betrag aus `abteilungen.aktivenumlage` in Cent verwendet. Fehlt ein Grundtarif, wird keine Basisforderung für diesen Monat erzeugt.

Einmal angelegte Forderungsbeträge werden nicht durch spätere Tarifänderungen rückwirkend geändert. Der eindeutige Datenbankschlüssel verhindert Doppelbuchungen, **auch bei bereits stornierten alten Forderungen**. Ein erneuter Monatslauf ist daher idempotent.

## Buchungsintegrität

- Ein Mitgliederdatensatz wird mit `SELECT ... FOR UPDATE` gesperrt, bevor Forderungen, Zahlungen, Zuordnungen oder Stornos verändert werden. Dadurch werden konkurrierende Buchungen pro Mitglied serialisiert.
- Jede zusammengesetzte Buchung findet innerhalb einer InnoDB-Transaktion statt. Fehler führen zum Rollback.
- Es werden ausschließlich native vorbereitete PDO-Statements verwendet (keine interpolierten Formulareingaben).
- Stornierte Zahlungen werden nicht mehr als Ist verbucht; ihre Historie bleibt erhalten.
- Bereits bezahlte Forderungen dürfen nicht storniert werden. Zuerst muss die zugehörige Zahlung storniert werden.
- Ist eine Zahlung in der alten Tabelle `vs_kassenbuch` verknüpft, verhindert das neue Beitragsmodul ihr Einzelstorno. Die synchrone Kassenstornierung wird erst in der Kassenphase implementiert.
- Keine Fremdbelege, Bankzugänge, Kontoumbuchungen oder SEPA-Vorgänge werden in alpha.7 erzeugt.

## Rechte und Datenzugriff

| Fähigkeit | Rechtestufe |
|---|---|
| Eigenes Beitragskonto anzeigen | 1 |
| Fremdes Konto anzeigen, Forderung oder Zahlung buchen, Guthaben verrechnen | 4 |
| Neue Tarife, Monats-Sollstellung, Zahlung/Forderung stornieren | 5 |

Ein Mitglied darf auch durch manuelle Änderung der URL **keine anderen Beitragskonten** anzeigen. Sämtliche schreibenden Routen sind POST-only und verlangen serverseitiges Rollenrecht und CSRF-Token.

## Einrichten und aktualisieren

Neue Installation über `http://localhost/verein/` (Root-`.htaccess`). Bestehende alpha.6-Installationen: Datenbank sichern, Dateien ersetzen und `php bin/migrate-finance.php` in der PHP-Konsole ausführen. Die Migration erzeugt vier Tabellen, wenn sie noch fehlen; alte Daten bleiben erhalten.

## Nachvollziehbarer Praxisfall

1. Mitglied A: Forderung Oktober 15,00 € → Soll 15 €, Ist 0 €, Offen 15 €.
2. Eingang 10,00 € → Soll 15 €, Ist 10 €, Offen 5 €.
3. Eingang 7,00 € → Soll 15 €, Ist 15 €, Offen 0 €, Guthaben 2 €.
4. Neue Forderung November 1 € und Guthabenverrechnung → Guthaben 1 €.
5. Storno der ersten 10-€-Zahlung → Oktober wieder teilweise offen; Stornogrund und Ursprung bleiben erhalten.

Diese Fälle sind in `tests/finance_smoke.php` zusätzlich als auslösbare fachliche Prüfungen hinterlegt.
