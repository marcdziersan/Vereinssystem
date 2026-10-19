# HTTP-Middleware

- `Middleware` beschreibt die Schnittstelle `handle(Request, $next): Response`.
- `Stack` komponiert Middleware in der registrierten Reihenfolge.
- `Authenticated` leitet unbekannte oder gesperrte Sitzungen zur Anmeldung.
- `Permission` prüft das konkrete Recht gegen die zentrale `Roles`-Matrix.
- `VerifyCsrf` prüft `_csrf` bei schreibenden POST-Anfragen.

Die alpha.5-Eventrouten verwenden die Middleware tatsächlich in `routes/web.php`; es handelt sich nicht nur um Platzhalter für eine künftige Implementierung. Bereits bestehende Fachcontroller werden in zukünftigen Entwicklungsphasen schrittweise umgestellt.
