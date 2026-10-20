-- MVC alpha.8: additional, repeatable historical-compatible cash tables.
-- Never drop existing 2.x cash data. All money is integer cents.
CREATE TABLE IF NOT EXISTS `vs_konten` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `name` VARCHAR(120) NOT NULL,
 `typ` VARCHAR(40) NOT NULL,
 `anfangsbestand_cent` BIGINT NOT NULL DEFAULT 0,
 `anfangsdatum` DATE NOT NULL,
 `aktiv` TINYINT(1) NOT NULL DEFAULT 1,
 `erstellt_von` INT NOT NULL,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`), UNIQUE KEY `uq_konto_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_kassenbuch` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `konto_id` INT UNSIGNED NOT NULL,
 `art` VARCHAR(16) NOT NULL,
 `datum` DATE NOT NULL,
 `betrag_cent` BIGINT NOT NULL,
 `kategorie` VARCHAR(100) NOT NULL DEFAULT '',
 `beschreibung` VARCHAR(500) NOT NULL,
 `belegnummer` VARCHAR(100) NOT NULL DEFAULT '',
 `zahlung_id` BIGINT UNSIGNED NULL,
 `umbuchung_key` CHAR(32) NULL,
 `import_key` CHAR(64) NULL,
 `erstellt_von` INT NOT NULL,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `storniert_am` DATETIME NULL,
 `storniert_von` INT NULL,
 `storno_grund` VARCHAR(250) NULL,
 PRIMARY KEY (`id`),
 KEY `idx_kasse_konto_datum` (`konto_id`,`datum`),
 KEY `idx_kasse_umbuchung` (`umbuchung_key`),
 UNIQUE KEY `uq_kasse_zahlung` (`zahlung_id`),
 UNIQUE KEY `uq_kasse_import` (`import_key`),
 CONSTRAINT `fk_kasse_konto` FOREIGN KEY (`konto_id`) REFERENCES `vs_konten` (`id`) ON DELETE RESTRICT,
 CONSTRAINT `fk_kasse_zahlung` FOREIGN KEY (`zahlung_id`) REFERENCES `vs_zahlungen` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_audit` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `akteur` INT NOT NULL,
 `aktion` VARCHAR(80) NOT NULL,
 `objekt` VARCHAR(50) NOT NULL,
 `objekt_id` BIGINT NOT NULL DEFAULT 0,
 `details` TEXT NOT NULL,
 PRIMARY KEY (`id`),
 KEY `idx_audit_date` (`erstellt_am`),
 KEY `idx_audit_objekt` (`objekt`,`objekt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
