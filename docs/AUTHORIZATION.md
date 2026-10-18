# Authentifizierung und Berechtigungen

## Architektur

`AuthController → AuthService → UserRepository → PdoUserRepository → PDO`

`AccessController → AuthService.currentUser → Roles → View`

Der HTTP-Controller kennt keine SQL-Anweisung. Die Authentifizierung kennt keine HTML-Vorlage. Die Views erhalten nur die notwendigen Daten. Dieses Muster wird für kommende MVC-Module beibehalten.

## Kontodaten

Das neue Login greift auf die bestehende `mitglieder`-Tabelle zu, liest nur die für den Zugriff notwendigen Spalten und verhindert durch einen `LIMIT 2`-Abruf mehrdeutige Benutzer-/Mitgliedsnummern-Zuordnungen. Inaktive Datensätze (`status != 1`) und gesperrte Stufen (`rights = 0`) erhalten keinen Zugang. Das gespeicherte Passwort ist entweder ein aktueller `password_hash` oder ein historischer MD5-Hash, der nach erfolgreicher Anmeldung ersetzt wird.

Das Konto speichert nur die numerische Benutzer-ID in der PHP-Session. Bei jeder geschützten Anfrage liest `AuthService::currentUser()` Status und Rechtestufe erneut aus dem Repository. Eine Änderung ist damit ohne erneutes Einloggen wirksam.

## Aktuell implementierte Fähigkeiten

| Fähigkeit | Benötigte Stufe | Route |
| --- | --- | --- |
| `account.view` | 1–5 | `/account` |
| `rights.view` | 1–5 | `/rights` |
| `rights.assign` | 5 | `/admin/roles` |

0 = gesperrt; 1 = Mitglied; 2 = Moderator; 3 = Abteilungsleitung; 4 = Vorstand; 5 = Administration.

Die Rollenbezeichnungen bleiben kompatibel. **Für künftige Module ist eine Berechtigung nicht automatisch verfügbar**, nur weil die Rolle eine hohe Zahl besitzt: Jede neue Fähigkeit muss im `Roles`-Katalog explizit definiert und im entsprechenden Controller geprüft werden.

## Schutzregeln

- Formularänderungen erfolgen ausschließlich mit POST und CSRF-Token.
- Sessions nutzen HttpOnly und SameSite=Lax; `Secure` wird bei HTTPS gesetzt.
- Die Session-ID wird nach erfolgreichem Login gewechselt und beim Logout verworfen.
- Fehler des Login-Formulars sind absichtlich unspezifisch.
- Nach fünf Fehlversuchen pro IP wird 15 Minuten blockiert; Datei- und Lock-Verwaltung liegt unter `storage/rate_limits/` außerhalb des Webroots.
- Die Administratorrolle kann nicht durch eigenes Rollen-Formular gesperrt werden.
- Rollenzuweisungen gelten nur für aktive Mitglieder; inaktive Konten bleiben gesperrt.
- Das Repository nutzt Prepared Statements und keine dynamischen SQL-Feldnamen aus HTTP-Eingaben.

## Betriebsgrenzen

Dieser Meilenstein enthält noch keine komplexe Rechtehistorie oder ein vollwertiges Audit-Journal. Diese Bausteine werden zusammen mit den Verwaltungsmodulen ergänzt. Die aktuelle Rollenliste zeigt aus Gründen der Übersichtlichkeit maximal 100 Konten; Suche und Paginierung folgen mit der Mitgliederverwaltung.

## Tests

`php tests/kernel_smoke.php` und `php tests/auth_smoke.php`.
