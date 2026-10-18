# Änderungsverlauf – MVC-Entwicklung

## 3.0.0-alpha.2 – Login, Logout und Rechtesystem

### Neu
- Neues OOP-Domänenobjekt `User`, zentrale Rollenregeln `Roles` und `AuthService`.
- `UserRepository`-Interface sowie PDO-Implementierung für das vorhandene `mitglieder`-Schema.
- HTTP-Routen `/login`, `/logout`, `/account`, `/rights` und `/admin/roles` mit eigenständigen MVC-Views.
- GET-/POST-Trennung, CSRF-geschützte Änderungen und Rechteprüfung auf dem Server.
- Rechtestufen 0–5, Zugangsverwaltung für Level 5; Selbstsperre eines Administrators ausgeschlossen.
- Passwortprüfung mit `password_verify()`; alte MD5-Passwörter werden erst bei erfolgreicher Anmeldung ersetzt.
- Dateibasierter Login-Schutz mit Sperrfrist nach wiederholten Fehlversuchen.
- Mobile-First-Komponenten für Zugangsformulare, Navigationsleisten und Rollenlisten.
- CLI-Bootstrap einer kompatiblen Benutzertabelle und eines ersten Admins für neue, leere Datenbanken.
- Isolierte OOP-Tests für Login, Logout, CSRF, Rollen, gesperrte Bereiche und Althash-Umstellung.

### Geändert
- Das MVC-Root führt nun zum Login beziehungsweise zum eigenen Zugang.
- Session-Lebenszyklus und Redirect-Antworten erweitert; PDO-Verbindung bleibt lazy.
- UI-Kit und Dokumentation auf alpha.2 ergänzt.

### Nicht Teil dieses Meilensteins
- Dashboard, Mitglieder-Stammdatenverwaltung und weitere ursprüngliche Vereinsmodule.

## 3.0.0-alpha.1 – Kernel
- Eigenständiger MVC-Kern ohne Legacy-Routing, neues UI-Kit, Router, Views, Session- und PDO-Basis.
