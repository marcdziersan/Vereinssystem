# Vereinsnachrichten – alpha.6

## Ziel und Abgrenzung

Eine vereinsinterne **Aushang-/Nachrichtenverwaltung** mit Leserechten, Autorenschutz und Archiv.
Das Modul ersetzt die historische `news`-Maske. **Keine privaten Direktnachrichten, keine Push-Benachrichtigungen und kein Messenger** in diesem Meilenstein.

## Bedienung

1. Als aktives Mitglied unter **Nachrichten** veröffentlichte Meldungen lesen und durchsuchen.
2. Vorstand (Level 4) oder Administration (Level 5): **Neue Nachricht** anlegen, Titel, Klartext und minimale Lesestufe angeben. Neue Beiträge werden direkt veröffentlicht.
3. Eigene aktive Meldungen bearbeiten; Administratoren dürfen auch fremde aktive Meldungen bearbeiten.
4. **Archivieren** per geschütztem POST-Formular; Meldungen werden nicht gelöscht.
5. Administration: **Archiv** anzeigen und bei Bedarf per POST wiederherstellen.

## Datenfelder und Altdaten

Die schon 2013 vorhandene Tabelle `news` bleibt kompatibel: `news_id`, `title`, `content`, `minright`, `author`, `created`, `lastchange`, `status`.
`status=1` ist veröffentlicht, `status=2` archiviert. Zeitpunktfelder verwenden kompatible Unix-Sekunden; Anzeige erfolgt in lokaler Serverzeit (Europe/Berlin). Die Migration erstellt die Tabelle **nur wenn sie fehlt**.

**Sicherheitsentscheidung für Altinhalte:** Historische HTML-Texte werden nicht als HTML ausgeführt, sondern ausnahmslos HTML-escaped angezeigt. Die Formatierung historischer HTML-Nachrichten kann deshalb als Markup-Text sichtbar sein. Dadurch werden Skripte und Inline-Eventhandler weder im Dashboard noch in der Detailansicht ausgeführt.

## Architektur

```text
routes/web.php → Authenticated → Permission → [VerifyCsrf POST]
              → NewsController → NewsService → NewsPost
                                           → NewsRepository → PdoNewsRepository
resources/views/news/{index,show,form,archived}.php
```

Für Freigaben und Archivierung gelten dieselben Regeln **beim Datenbankzugriff und bei der Anzeige**. Das Repository erstellt vorbereitete PDO-Statements und filtert Listen/Seitenzahlen; das Domänenmodell enthält keine HTTP- oder Datenbanklogik.

## Neue Datenbank

Der Installer fügt `004_news.sql` als achte Tabelle hinzu. Ein bestehendes alpha.5-System nutzt unter WAMP/CLI:

```sh
php bin/migrate-news.php
```

Die Datenbankzugangsdaten verbleiben in `config/database.local.php`. Es werden keine vorhandenen Nachrichten gelöscht oder überschrieben.

## Praktische Kontrolle

- Veröffentlichung mit Level 1, Freigabe Level 4 und Lesen mit einem einfachen Mitglied.
- Bearbeitung einer Nachricht durch Autor, anderen Vorstandsaccount und Administrator.
- Archivieren, Verbergen, Zurückholen und anschließendes erneutes Lesen.
- Nachricht mit `<script>` als Text speichern und deren sichere Darstellung im Browser prüfen.
- Suche, Seitenwechsel, Smartphone-Darstellung und WAMP-Root-Weiterleitung.

Die automatisierten Fachverträge stehen getrennt in `tests/news_smoke.php`.
