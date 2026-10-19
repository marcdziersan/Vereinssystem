-- MVC alpha.5: Flexible Aufgaben mit mehreren Mitgliedern je Veranstaltung.
-- Keine Standdienst- oder Kuchenlogik. Bestehende Tabelle `dienste` bleibt unangetastet.
-- Additive Migration: CREATE TABLE IF NOT EXISTS, keine Datenlöschung.
CREATE TABLE IF NOT EXISTS `veranstaltungen` (
    `veranstaltungs_id` INT NOT NULL AUTO_INCREMENT,
    `veranstaltungsname` VARCHAR(250) COLLATE utf8mb4_unicode_ci NOT NULL,
    `beschreibung` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `ort` INT NOT NULL DEFAULT 0,
    `ansprechpartner` INT NOT NULL DEFAULT 0,
    `startzeit` INT NOT NULL,
    `endzeit` INT NOT NULL,
    `mindienste` INT NOT NULL DEFAULT 0,
    `minkuchen` INT NOT NULL DEFAULT 0,
    `dienstbeschreibung` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `status` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`veranstaltungs_id`),
    UNIQUE KEY `name` (`veranstaltungsname`,`ort`,`startzeit`,`endzeit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vs_event_aufgaben` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `titel` VARCHAR(160) COLLATE utf8mb4_unicode_ci NOT NULL,
    `beschreibung` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `ort` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `startzeit` INT NOT NULL,
    `endzeit` INT NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `erstellt_von` INT NOT NULL,
    `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aufgabe_event` (`event_id`,`status`,`startzeit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vs_event_aufgaben_mitglieder` (
    `aufgabe_id` INT NOT NULL,
    `mitglied_id` INT NOT NULL,
    `zugeteilt_von` INT NOT NULL,
    `zugeteilt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`aufgabe_id`,`mitglied_id`),
    KEY `idx_aufgaben_mitglied` (`mitglied_id`,`aufgabe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
