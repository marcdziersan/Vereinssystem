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
