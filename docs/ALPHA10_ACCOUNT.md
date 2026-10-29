# MVC Alpha 10.1 – Kontopflege

Dies ist das **erste Fachpaket aus dem Alpha-10-Plan**. Die übrigen geplanten Pakete 10.2–10.6 (Systemeinstellungen, Bank-/CSV-Import, Zeitaufgaben, Backup/Recovery, Jahresabschluss) sind **nicht umgesetzt**.

## Umsetzung

- `/account/settings`: Eigenen Benutzernamen, E-Mail und Mobilnummer ansehen und ändern.
- `POST /account/settings/profile`: Änderungen nur für die eigene, angemeldete Identität. Aktuelles Passwort ist Pflicht.
- `POST /account/settings/password`: Altes Passwort, neues Passwort und Wiederholung; mindestens 12 Zeichen. Nach Erfolg Session-ID und CSRF-Token erneuern.
- Alle Routen sind durch `Authenticated`, `Permission` und bei POST durch `VerifyCsrf` geschützt.
- Eigenständiges `Accounts/AccountRepository`, PDO-Implementierung, `AccountService`, `SelfServiceController`, mobile Ansicht.
- Bereits vorhandene `mitglieder`-Spalten werden verwendet: keine SQL-Migration; keine Änderung der Mitgliedsnummer, Mitgliedschaft oder Rechte.
- Alle PDO-Änderungen mit Parameterbindung; Passwortwechsel nur nach erfolgreichem Vergleich mit dem vorhandenen Hash. Legacy-MD5 wird beim erfolgreichen Wechsel zu `password_hash` migriert.

## Abgrenzungen

- E-Mail-Adresse ist Kontaktinformation, kein neu eingeführter Login-Kanal; Login über Benutzernamen/Mitgliedsnummer bleibt unverändert.
- Andere aktive Sitzungen, deren Passwort-Fingerprint bereits gespeichert wurde, verlieren nach Passwortwechsel bei der nächsten Anfrage den Zugriff. Bereits vor Einführung der Fingerprint-Prüfung bestehende, noch nicht überprüfte Sitzungen können damit nicht rückwirkend sicher widerrufen werden; dafür wäre ein zusätzliches serverseitiges Sitzungs-/Passwortversionsmodell nötig.
- Keine E-Mail-Verifikation, Zwei-Faktor-Authentifizierung oder automatische Benachrichtigung in diesem Paket.
- Für produktive Abnahme sind weiterhin die Stabilisierungsschritte 9.1–9.8 aus `docs/CONSISTENCY_AND_PACKAGE_PLAN.md` und reale WAMP-/MySQL-Tests erforderlich.

## Prüfung

`php tests/account_smoke.php` prüft die fachlichen Regeln mit simuliertem Repository. Diese Prüfung ersetzt keine echte MySQL- und Browserabnahme.