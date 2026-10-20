-- Alpha.9: private file metadata. Actual bytes live under storage/receipts,
-- which is inaccessible to HTTP clients. Append-only: no existing cash data removed.
CREATE TABLE IF NOT EXISTS `vs_kassenbelege` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `buchung_id` BIGINT UNSIGNED NOT NULL,
 `originalname` VARCHAR(180) NOT NULL,
 `mime` VARCHAR(50) NOT NULL,
 `size_bytes` INT UNSIGNED NOT NULL,
 `sha256` CHAR(64) NOT NULL,
 `dateiname` CHAR(36) NOT NULL,
 `erstellt_von` INT NOT NULL,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_beleg_datei` (`dateiname`),
 KEY `idx_beleg_buchung` (`buchung_id`),
 CONSTRAINT `fk_beleg_buchung` FOREIGN KEY (`buchung_id`) REFERENCES `vs_kassenbuch` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
