# Bedienungsanleitung – Kasse und Beitragsverwaltung

## Kassenstart

Unter `kasse.php` kann ein Administrator Konten erstellen, zum Beispiel Bargeld, Bank, PayPal oder eine weitere Kategorie. Jedes Konto besitzt ein Eröffnungsdatum und einen Anfangsbestand. Bei einer neuen Installation mit leerem Kassenbestand werden für alle Konten `0,00` Euro empfohlen. Bei bestehenden Vereinen ist der nachgewiesene Bestand am Stichtag einzutragen.

## Buchungen

1. `kasse.php` öffnen und das Konto auswählen.
2. `Einnahme` oder `Ausgabe` festlegen; Datum, Geldbetrag, Kategorie und Beschreibung eintragen.
3. Optional Belegnummer zuordnen. Nach Absenden wird eine ID vergeben.
4. Für einen tatsächlichen Mitgliedsbeitrag **stattdessen** die Zahlung im Mitgliedskonto erfassen und dort das Kassenkonto wählen, sonst wird die Einnahme doppelt gezählt.
5. Korrektur einer falschen Kassenbuchung nur per **Storno mit Begründung**. Belege und Audit bleiben erhalten.

## Umbuchung

Bei einer Umbuchung werden zwei verschiedene aktive Konten angegeben. Der Gesamtbestand ändert sich nicht; Quelldatensatz und Zieldatensatz können nur gemeinsam storniert werden.

## CSV importieren

`kasse_import.php`: CSV (UTF-8, Semikolon) mit `datum;art;betrag;kategorie;beschreibung;belegnummer`. Nur reine Einnahmen/Ausgaben, keine Kassenumbuchung oder Beiträge. Nach Upload Vorschau sorgfältig prüfen und Import bestätigen. Der Import ist keine Bankabgleichsoftware.

## Berichte

`kasse.php`: Jahr/Konto wählen, dann CSV-Einzelbuchungen, PDF-Jahresbericht oder einzelne PDF-Buchungsnachweise herunterladen. Die Kategorienauswertung enthält nur Einnahmen und Ausgaben, keine Umbuchungen.

## Rechte

Level-5-Administratoren können Kassenrechte per `kasse_admin.php` vergeben. Für normales Kassenpersonal benötigt der Benutzer mindestens `kasse.ansehen`, für Buchungen zusätzlich `kasse.buchen`. Storno, Kontoanlage, Exporte, Importe und Belege sind zusätzliche Rechte.

## Beitragssätze

Tarife werden über `kasse_admin.php` mit einem `Gültig ab`-Datum angelegt. Die neuen Tarife beeinflussen nur neu erzeugte Sollstellungen. Bereits erzeugte Forderungen bleiben unverändert.