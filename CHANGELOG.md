# Changelog – MVC-Neuentwicklung

## 3.0.0-alpha.1 – Kernel-Grundlage (18.10.2026)

### Neu
- Eigenständige OOP-/MVC-Projektstruktur ohne historische PHP-Endpunkte.
- PSR-4-artiger Autoloader ohne Composer; keine Frameworkpflicht.
- Front Controller, feste HTTP-Routen, Request/Response und isolierte PHP-Templates.
- Einheitliche Fehlerausgabe, HTML-Escaping, MIME-Header und JSON-Statusroute.
- Session-/CSRF-Grundlagen und optionale PDO-Verbindungsfabrik ohne Alt-SQL-Modus.
- Selbst entwickeltes Mobile-First-UI-Kit mit Formularklassen, Layout und Dark Mode.
- Lokale jQuery-Kopie für spätere Module; nicht zum Start des Kernels erforderlich.
- Dokumentation und eigenständige Kernel-Smoke-Checks.

### Bewusste Abgrenzung
- Kein Login, Dashboard, Mitglieder, Rechte-, Beitrags- oder Kassenmodul.
- Keine Legacy-Links, keine übernommenen PHP-Formulare und keine SQL-Migration.
- `master` bleibt unverändert; diese Neuentwicklung findet allein auf `refactor/mvc-architecture` statt.
