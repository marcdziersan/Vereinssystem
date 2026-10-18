-- Vereinssystem 2.1.0: Finanzbuchhaltung. InnoDB, Beträge in ganzzahligen Cent.
CREATE TABLE IF NOT EXISTS `vs_forderungen` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `mitglied_id` INT NOT NULL,
 `periode` CHAR(7) NOT NULL,
 `art` VARCHAR(20) NOT NULL,
 `abteilung_id` INT NOT NULL DEFAULT 0,
 `beschreibung` VARCHAR(190) NOT NULL,
 `betrag_cent` INT NOT NULL,
 `faellig_am` DATE NOT NULL,
 `erstellt_von` INT NOT NULL,
 `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `storniert_am` DATETIME NULL,
 `storniert_von` INT NULL,
 `storno_grund` VARCHAR(250) NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_sollstellung` (`mitglied_id`,`periode`,`art`,`abteilung_id`),
 INDEX `idx_mitglied_periode` (`mitglied_id`,`periode`),
 INDEX `idx_faellig` (`faellig_am`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_zahlungen` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `mitglied_id` INT NOT NULL,
 `betrag_cent` INT NOT NULL,
 `gezahlt_am` DATE NOT NULL,
 `zahlungsweg` VARCHAR(24) NOT NULL,
 `zahler_name` VARCHAR(190) NOT NULL DEFAULT '',
 `referenz` VARCHAR(190) NOT NULL DEFAULT '',
 `bemerkung` VARCHAR(500) NOT NULL DEFAULT '',
 `erstellt_von` INT NOT NULL,
 `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `storniert_am` DATETIME NULL,
 `storniert_von` INT NULL,
 `storno_grund` VARCHAR(250) NULL,
 PRIMARY KEY (`id`),
 INDEX `idx_member_date` (`mitglied_id`,`gezahlt_am`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_zuordnungen` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `zahlung_id` BIGINT UNSIGNED NOT NULL,
 `forderung_id` BIGINT UNSIGNED NOT NULL,
 `betrag_cent` INT NOT NULL,
 `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_zuordnung` (`zahlung_id`,`forderung_id`),
 KEY `idx_forderung` (`forderung_id`),
 CONSTRAINT `fk_vs_zuord_zahlung` FOREIGN KEY (`zahlung_id`) REFERENCES `vs_zahlungen` (`id`) ON DELETE RESTRICT,
 CONSTRAINT `fk_vs_zuord_forderung` FOREIGN KEY (`forderung_id`) REFERENCES `vs_forderungen` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;