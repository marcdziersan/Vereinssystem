# Mitglieder – MVC-Modul 3.0.0-alpha.3

## Grundprinzip

Verzeichnis, Profil, Formular, Speichern und Statusverwaltung sind eigenständige
MVC-Routen. Es gibt keine direkte PHP-Dateiroute zum Speichern und keinen Mischbetrieb
mit dem ursprünglichen 2.4.0-Code.

| GET/POST | Route | Zugriff |
| --- | --- | --- |
| GET | `/dashboard` | aktive Benutzer |
| GET | `/members` | Vorstand und Administration |
| GET | `/members/view?id=…` | eigenes Profil oder Vorstand/Administration |
| GET | `/members/create` | Administration |
| POST | `/members/store` | Administration + CSRF |
| GET | `/members/edit?id=…` | Vorstand/Administration; Adminprofil nur Admin |
| POST | `/members/update` | Vorstand/Administration + CSRF; Adminprofil nur Admin |
| POST | `/members/status` | Administration + CSRF; fremde Nichtadministratoren |

## Datenfelder

Mitgliedsnummer, Name, Geburtstag, Eintritt, Geschlecht, E-Mail, Mobiltelefon,
Mitgliedschaftsart, Abrechnungsart, Anschrifts-ID, Elternreferenzen und interne
Notizen. Neuanlage ergänzt einen Benutzernamen und ein Initialpasswort. Das Passwort
wird ausschließlich gehasht gespeichert. Die Abrechnungsauswahl ist derzeit auf
Bankeinzug/Überweisung begrenzt und betrifft noch **keine automatische Buchhaltung**.

Bestehende Bankdaten bleiben im Originalschema erhalten, werden hier aber **nicht**
angezeigt oder bearbeitet. Eingebaute Rollenverwaltung und Authentifizierungsmodul
bleiben die einzigen zuständigen Stellen für Rechte beziehungsweise Passwortlogik.

## Sicherheit / Datenqualität

- Formularwerte werden im Service gegen Typ, Länge, Datumsformat und Wertebereich geprüft.
- PDO nutzt native gebundene Parameter. Suchwildcards (`%`, `_`) werden zusätzlich
  neutralisiert, damit Suche nach Literalen möglich bleibt.
- Gespeichert werden nur ausdrücklich erlaubte Felder. Die Form kann keine
  `rights`, `passwort`, `username`-Änderung oder Bankdaten-Änderung injizieren.
- Inaktiver Status ist ein Soft-Status, kein physisches DELETE; Wiederherstellung
  ist durch die Administration möglich.
- Datenbankzugriffe und Berechtigungen werden serverseitig geprüft. Die View
  gibt alle dynamischen Text- und Attributinhalte HTML-kodiert aus.
- Eingaben enthalten nur numerische Adress- und Elternreferenzen, solange die
  eigenständigen Adress- und Familienmodule noch nicht neu implementiert sind.

## Nächste Modulgrenze

Orte/Adressen und Abteilungsmitgliedschaften gehören zu eigenständigen Domänen.
Sie dürfen später auf den Mitgliederservice aufsetzen, sollten aber nicht durch
neue gemischte SQL-Abfragen in die Mitglieder-Views eingepflegt werden.
