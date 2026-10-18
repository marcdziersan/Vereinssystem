# Quellcode- und Kommentierregeln

Diese Regeln gelten für **alle neu angelegten** PHP-, HTML-, CSS-, SQL- und JavaScript-Dateien im MVC-Branch.

1. **PHP:** `declare(strict_types=1)` in jeder PHP-Datei, Namespaces unter `Vereinssystem\\`, vier Leerzeichen pro Einrückung, konsistente Typisierung, Docblocks auf Klassen/Funktionen mit Zweck und Eingabe-/Ausgabevertrag.
2. **HTML/Views:** semantische Elemente, aussagekräftige ARIA-Labels, Kommentare für Layoutentscheidungen und Sicherheitsgrenzen. Variable Inhalte immer über `View::escape()`; Controller/SQL nicht in Views.
3. **CSS:** `vs-`-Namensraum, dokumentierte Abschnitte (Tokens, Layout, Komponenten, Breakpoints), Designentscheidungen begründen, Mobile First.
4. **JavaScript:** Vanilla JS, `const`/`let`, JSDoc für Funktionen und Nebenwirkungen, keine impliziten globalen Variablen oder Inline-Eventhandler.
5. **SQL (später):** jede Migration bekommt Zweck, Schema-Version, Up-/Down-Strategie und Indizes; alle Laufzeitabfragen verwenden native PDO-Parameter.
6. **Dateiorganisation:** eine Klasse pro Datei, Dateiname gleich Klassenname. Keine dynamischen Includes aus URL-/POST-Daten, keine Geschäftslogik in Templates.
7. **Historische Rechte:** Herkunftshinweise für unverändert übernommene Grafiken und Drittanbieterbibliotheken beibehalten.
8. **Dokumentation:** README zeigt Funktionen und Bedienung; ARCHITECTURE den Code-Aufbau; CHANGELOG alle fachlichen Änderungen. Entwicklungsschritte dürfen noch unvollständige Fachmodule haben, ohne sie als fertig auszugeben.

**Kommentierprinzip:** Nicht jeden einzelnen Operator mit redundanten Kommentaren versehen, sondern **Klassen, Verträge, Zuständigkeiten, Sicherheitsentscheidungen und nicht offensichtliche Abläufe** vollständig erklären. So bleibt der Code trotz ausführlicher Dokumentation lesbar.
