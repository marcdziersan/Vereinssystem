# Änderungsverlauf – MVC-Entwicklung

## 3.0.0-alpha.3 – Dashboard und Mitgliederverwaltung (18.10.2026)

### Neu
- Authentifiziertes MVC-Dashboard mit personalisierten Links und rollenabhängigen Mitgliederstatistiken.
- Mitglieder-Modul als strikt getrennte OOP-Komponenten: `Member`, `MemberRepository`, `PdoMemberRepository`, `MemberService` und `MemberController`.
- Mitgliederliste mit vorbereiteten PDO-Suchabfragen, sicheren LIKE-Wildcards, Filterung und serverseitig begrenzter Pagination.
- Mitgliedsprofile mit an Berechtigungen gebundenen internen Notizen, vollständige Neuanlage und bearbeitbare Stammdaten.
- Soft-Deaktivierung und Reaktivierung für Administratoren ohne physischen Datenverlust; Admin-Konten geschützt.
- Mobile Views für Dashboard, Suche, Profil und mehrteilige Formulare sowie neue UI-Komponenten für diese Ansichten.
- Fachmodultests mit in-memory Repositories, ohne Altcode-PHP-Endpunkte.

### Geändert
- Nach erfolgreicher Anmeldung zum Dashboard statt ausschließlich zur Zugangsseite weiterleiten.
- Rollenmatrix um Member-Lesen, Member-Bearbeiten, Erstellen und Statusverwaltung ergänzt.
- Projektdokumentation auf 3.0.0-alpha.3 angehoben.
- Keine Änderung am 2013er Datenbankschema; neue Mitglieder werden mit allen erforderlichen Nicht-NULL-Feldern geschrieben.

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
