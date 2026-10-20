# VS UI Kit – Mobile First

Die eigenständige CSS-/JavaScript-Basis ist als kleines internes Bootstrap-ähnliches UI-System angelegt. Sie ist bewusst **kein Fork von Bootstrap** und verwendet weder jQuery Mobile noch NPM.

## Gestaltungsregeln

- **Mobile First:** einfache Spalte bei kleinen Bildschirmen, ab `640px` zwei und ab `940px` drei Gridspalten.
- **Klassenpräfix `vs-`:** verhindert Konflikte mit späteren Fachmodulen.
- **Design-Tokens:** Farben, Abstände, Radien und Fokusfarbe stehen zentral in `:root`.
- **Zugänglichkeit:** 44px+ Touch-Ziele, Tastaturfokus, Skip-Link und reduzierte Animationen bei entsprechender Systemeinstellung.
- **Dark Mode:** Standard ist Systempräferenz; eine manuelle Wahl wird lokal im Browser gespeichert. Keine externen Tracking-Skripte.
- **Progressive Enhancement:** Serverseitiger Inhalt ist ohne JavaScript zugänglich. Interaktivität ist optional.

## Komponenten (Start)

| CSS-Klasse | Zweck |
| --- | --- |
| `.vs-container` | Begrenzter, zentrierter Inhaltsbereich |
| `.vs-hero` | Intro-/Seitenheader |
| `.vs-grid`, `.vs-grid--3` | Responsives Kartenraster |
| `.vs-card` | Visuelle Inhaltskarte |
| `.vs-button`, `.vs-button--primary` | Buttons und Aktionslinks |
| `.vs-chip`, `.vs-chip--outline` | Kleine Metadaten-/Status-Labels |
| `.vs-form`, `.vs-field`, `.vs-label` | Formularraster und Beschriftungen |
| `.vs-input`, `.vs-select`, `.vs-textarea` | Formulareingaben |
| `.vs-alert`, `.vs-help` | Hinweise und Hilfetexte |
| `.vs-skip-link` | Tastatur-Sprung zum Hauptinhalt |

## JavaScript

`public/assets/js/vs-ui.js` ist eine kleine, selbständige IIFE. Sie steuert ausschließlich den Dark-Mode-Schalter. Jedes weitere UI-Element erhält klar abgegrenzte Funktionen, JSDoc-Kommentare und einen Fallback ohne JavaScript. jQuery 3.6.1 liegt bereit, ist aber im Kernel bewusst nicht geladen.

## Neu ab alpha.2 – Zugänge und Rechte

- `.vs-auth`/`.vs-auth__panel`: zweispaltiges Login ab 720px, einspaltig auf Mobilgeräten.
- `.vs-input` und `.vs-select`: vereinheitlichte native Eingabefelder und Auswahlmenüs.
- `.vs-alert` und `.vs-alert--error`: verständliche Status- und Fehlermeldungen mit `role="alert"`.
- `.vs-nav`: scrollbar-fähige, auf kleinen Displays vollständig bedienbare Navigation.
- `.vs-role-list` und `.vs-role-entry`: Rollenverwaltung als Karten statt zu breiter Tabelle.
- `.vs-table-wrap`: horizontale Berechtigungsmatrix mit eigener zugänglicher Scrollregion.
- `data-vs-password-toggle`: progressive Option zum Anzeigen eines Passworts ohne JavaScript-Pflicht.

Views erhalten keine Inline-Events; Interaktionen stehen dokumentiert unter `public/assets/js/vs-ui.js`.

## Neu ab alpha.3 – Mitglieder und Dashboard

Mobile Mitgliederkarten (`.vs-member-card`), responsives Suchformular
(`.vs-searchbar`), persönliche Datenblätter (`.vs-details`), gefelderte
Mitgliederformulare (`.vs-fieldset`) und statistikbasierte Dashboardkarten.
Alle Komponenten nutzen die zentralen `--vs-*`-Tokens.

## alpha.4 – Abteilungs- und Adresskarten

`.vs-directory-grid` stellt Einträge standardmäßig einspaltig dar und ab 760px zweispaltig; `.vs-directory-card` ist responsiv und verwendet bestehende UI-Farb- und Abstandsvariablen. Formulare nutzen `.vs-field-grid`, `.vs-input`, `.vs-select`, `.vs-textarea` und `.vs-button` ohne zusätzliche UI-Bibliothek.

## alpha.5 – Aufgabenplanung

Mehrfachauswahl über echte, beschriftete Checkboxen. Ein JavaScript-Filter verbirgt nur nicht passende Namen; ohne JavaScript bleibt die gesamte Auswahl verfügbar. Für Smartphones bleiben Karten, Terminangaben und Buttons touch-freundlich.

## alpha.6 – Nachrichten

`.vs-news-grid` verwendet die vorhandenen Karten- und Rasterkomponenten.
`.vs-news-preview` begrenzt nur die **Vorschau** visuell. Die vollständige
Nachricht wird auf der Detailseite ohne HTML-Ausführung über
`.vs-news-body` mit erhaltenen Zeilenumbrüchen dargestellt.
Der Editor ist ein zugängliches Textarea-Formular mit nativer Eingabeprüfung.

## Alpha.7: Mobile Beitragsübersicht

Die Klassen `vs-finance-stats`, `vs-finance-grid`, `vs-finance-entry` und `vs-finance-inline` bilden ein responsives ein-/zweispaltiges Layout und vier Kennzahlenkarten (sehr schmale Displays: eine Spalte). Alle Eingaben haben sichtbare Labels und ausreichend große Touch-Ziele; unbekannte Texte und Datenbankwerte werden vor HTML-Ausgabe escaped.

## alpha.8 – Mobile Kassenoberfläche

`.vs-cash-grid` stellt Konto- und Journal-Karten zunächst einspaltig dar. Ab 720 Pixeln werden maximal zwei Spalten verwendet. Formulare nutzen bestehende `.vs-field`, `.vs-input`, `.vs-select` und `.vs-button`-Bausteine. Es gibt keine externen CDN-, jQuery-Mobile- oder Bootstrap-Abhängigkeiten. Die gesamte Bedienung bleibt ohne JavaScript möglich.
