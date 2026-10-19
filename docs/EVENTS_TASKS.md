# Veranstaltungen und flexible Aufgaben – alpha.5

## Ziel und Bedienung

Das MVC-Modul kennt **keine festen Diensttypen**, Standdienst-Slots oder Kuchensonderfälle mehr. Zu jeder Veranstaltung lassen sich beliebig viele Aufgaben mit folgenden Feldern anlegen:

- **Aufgabe:** frei formulierter Titel und optionale Beschreibung
- **Datum/Uhrzeit:** Beginn und Ende als deutsche Lokalzeit (`Europe/Berlin`)
- **Ort:** frei formulierter Einsatzort, z. B. „Vereinsheim – Haupteingang“
- **Mitglieder:** beliebige Auswahl aus den aktiven Vereinsmitgliedern; Mehrfachzuordnung ist möglich

In `Veranstaltungen → Termin öffnen → Aufgabe planen` lassen sich Mitglieder über Kontrollkästchen zuordnen. Die Suchfunktion filtert nur die bereits geladenen Anzeigenamen. Bei einer Änderung werden alle aktuellen Zuweisungen der Aufgabe durch die neue Auswahl ersetzt; leere Auswahl bedeutet unbesetzte Aufgabe.

## Technische Schichten

| Schicht | Dateien | Verantwortlichkeit |
| --- | --- | --- |
| Models | `app/Models/Event.php`, `EventTask.php`, `TaskAssignment.php` | Unveränderliche Domänenobjekte und Feldtypen |
| Services | `app/Services/EventService.php`, `TaskService.php` | Plausibilität, Geschäftsregeln, Aufgabenzuteilung |
| Repositories | `app/Events/EventRepository.php`, `PdoEventRepository.php` | SQL, Transaktionen, Zeilensperren, Konflikte |
| Middleware | `app/Http/Middleware/*` | Authentifizierung, Berechtigungen, CSRF vor Controller-Aufruf |
| Controller | `app/Controllers/EventController.php` | HTTP-Eingabe in Service-Aufrufe übersetzen |
| Views | `resources/views/events/*` | Ausschließlich escaped HTML und Formulare |
| Routing | `routes/web.php` | Explizite Endpunkte und Middleware-Stack |

Die Middleware wird derzeit für **alle alpha.5-Veranstaltungsrouten** verwendet. Die älteren Fachmodule behalten vorerst zusätzlich ihre Controller-Prüfungen, bis sie schrittweise auf die gemeinsame Middleware übertragen werden.

## Datenhaltung und bestehende Datensätze

- `veranstaltungen` bleibt als ursprüngliche Stammdatentabelle kompatibel. Die historischen Felder `mindienste`, `minkuchen` und `dienstbeschreibung` werden bei neuen Datensätzen mit Null-/Leerwerten gefüllt, aber in der neuen Oberfläche nicht angezeigt.
- `vs_event_aufgaben` speichert Name, Beschreibung, Ort und Zeitraum.
- `vs_event_aufgaben_mitglieder` speichert eine n:m-Zuordnung von Aufgaben zu Mitgliedern.
- Die ursprüngliche Tabelle `dienste` wird **nicht** verändert oder automatisch migriert. Eine Übernahme historischer Stand-/Kuchendienste bedarf einer späteren fachlichen Entscheidung.

Alle SQL-Anweisungen verwenden PDO. Mitgliedseintragungen werden innerhalb einer Transaktion angelegt bzw. ersetzt. Während der Zuweisung sperrt das Repository die betroffenen Mitgliedszeilen in aufsteigender Reihenfolge und prüft zeitliche Überschneidungen zu **anderen aktiven Aufgaben aktiver Veranstaltungen**, auch veranstaltungsübergreifend. Bereits abgeschlossene oder archivierte Aufgaben werden nicht inaktive Zuweisungen widerspiegeln, sondern bleiben historisch gespeichert. Archivieren setzt `status = 2`; es löscht keine Zuordnungszeilen.

## Installation und Update

**Neuinstallation:** `http://localhost/verein/` unter WAMP öffnen. Der Installer nutzt `001_members_auth.sql`, `002_departments_places.sql` und `003_events_tasks.sql`, insgesamt **7 Tabellen**.

**Update von alpha.4:** Datenbank sichern und `php bin/migrate-events.php` ausführen. Die SQL-Datei verwendet `CREATE TABLE IF NOT EXISTS`, ist daher erneut ausführbar. Bestehende Mitgliedsdaten bleiben erhalten.

## Routen

`GET /events`, `/events/view`, `/events/create`, `/events/edit`, `/events/tasks/create`, `/events/tasks/edit`; `POST /events/store`, `/events/update`, `/events/status`, `/events/tasks/save`, `/events/tasks/archive`.

GET list/view ab Mitgliedslevel 1, Veranstaltung erstellen/bearbeiten ab 4, Status ab 5. Aufgaben erstellen/bearbeiten/archivieren und Mitgliedszuteilungen ab 4. Jeder POST erfordert zusätzlich CSRF.

## UI und Barrierefreiheit

Mobile-First-Karten, Formularfelder mit Labels, Checkboxen mit Namen, Tastaturfokus, lesbare Zeit- und Ortsangaben. Das JavaScript ergänzt nur die Mitgliedersuche; ohne JavaScript bleiben alle Auswahlfelder benutzbar.
