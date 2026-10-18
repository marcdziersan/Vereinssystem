# UX/UI 2.4.0 – Technischer Leitfaden

## Ziel

Mobile-first für das gesamte Vereinssystem: keine zweite Mobilanwendung, keine Framework-Abhängigkeit und keine mehrfach gepflegten Masken. Das Design verwendet die historischen Logos unverändert und modernisiert die gemeinsame Bedienoberfläche.

## Gemeinsame Komponenten

| Datei | Inhalt |
|---|---|
| `inc/ui.php` | HTML-Escaping, feste SVG-Icons, aktiver Navigationsbereich und Seitenzuordnung |
| `inc/head.inc.php` | Ein gemeinsamer HTML-Head, Topbar, Sidebar, Mobile-Tabs und öffentlicher Header |
| `inc/footer.inc.php` | Responsiver Footer und Lizenz-/Herkunftsangabe |
| `css/app.css` | Design-Tokens, Layout, Navigation, Formulare, Tabellen, Login, Dashboard, Dark Mode |
| `assets/js/app.js` | Menü-Interaktionen, Fokusführung, Tabellenaufbereitung, Passwortsichtbarkeit, Dark Mode |
| `css/setup.css` | Eigenständiges Setup-Design für Installer und Upgrade ohne Login-Session |

## Navigation und Breakpoints

- Bis **679px**: mobile Tab-Navigation unten, Touchflächen mindestens 44px, einfache Datentabellen als Karten, einspaltige Formulare.
- Ab **680px**: vier Finanz-KPIs nebeneinander, flexibel nebeneinander platzierte Formularfelder; Desktop-Tabellen bleiben lesbar.
- Ab **960px**: klassische Zwei-Spalten-Verwaltungsmasken als responsive Grid.
- Ab **1100px**: feste Sidebar, Desktop-Kopfleiste und volle Übersicht.
- Bei aktivem Mobile-Drawer: Fokus bleibt darin, Escape schließt es, nach dem Schließen kehrt der Fokus zurück. Ohne JavaScript werden die Links als normale Navigation angezeigt.

## Datenlisten

Das Skript ergänzt **nur** bei klarer Kopfzeile und einfachen Tabellenzellen `data-vs-label`. Formulare innerhalb von Tabellen, zusammengefasste Zellen und verschachtelte Strukturen bleiben ausdrücklich scrollbar. Tabellen werden nicht inhaltlich neu erzeugt und keine Eingabefelder versetzt; Buchungen und Datensätze bleiben servergesteuert.

## Formulare und historische Interaktionen

- Name-/ID-Attribute, `action`, GET-/POST-Methoden, CSRF-Tokens und vorhandene Zugriffsrechte bleiben erhalten.
- Bestehendes `$appendjs` (jQuery-Code einzelner 2013er Formulare) wird in einem DOMContentLoaded-Handler aktiviert. jQuery bleibt lokal eingebunden.
- Neue Gestaltung setzt responsive Breiten statt historischer `.medium`/`.xlarge`-Fixmaße und vermeidet Smartphone-Zoom durch 16px Schrift in Eingabefeldern.
- Login: `vs_login_token`, `after`, CAPTCHA, Meldungen und Passwort-POST bleiben erhalten. Passwort einblenden ist nur Client-UI.

## Barrierearmut und Ausbauregeln

Sichtbarer Fokus, Skip-Link, `aria-current` für aktive Navigation, `aria-expanded` und `aria-controls` am Drawer, Fokusführung, ausreichende Touchflächen und `prefers-reduced-motion` werden unterstützt. Komplexe Bestandsmasken können zusätzliche feldbezogene Labels benötigen; neue Formulare sollten `for`/`id` durchgehend verwenden.

Neue UI-Elemente zuerst in `css/app.css`/`inc/ui.php` implementieren, bestehende CSS-Klassen wiederverwenden und keine zusätzlichen CDN-/NPM-Abhängigkeiten einführen. Animationen müssen bei reduzierter Bewegung automatisch ausfallen.

## Manuelle UX-Abnahme

Auf 360px, 390px, 768px und mindestens 1280px prüfen: Anmeldung, Abmeldung, Menü öffnen/schließen, aktiven Navigationspunkt, Tabellenkarten, Kasse (Einnahmen, Ausgaben, Umbuchungen), Mitgliedsanlage, Dienste, Nachrichten, Einstellungen und Rollen. Buchungen weiterhin durch den Server prüfen; JavaScript ändert keine Kontostände.
