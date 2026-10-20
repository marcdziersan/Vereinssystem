-- Additive alpha.9 receipt storage metadata, same schema as the 2.4.0 master branch.
-- Receipt bytes remain outside public/ and are served only after authentication.
CREATE TABLE IF NOT EXISTS `vs_belege` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `buchung_id` BIGINT UNSIGNED NOT NULL,
 `dateiname` VARCHAR(180) NOT NULL,
 `speichername` CHAR(64) NOT NULL,
 `mime` VARCHAR(60) NOT NULL,
 `groesse` INT UNSIGNED NOT NULL,
 `sha256` CHAR(64) NOT NULL,
 `hochgeladen_von` INT NOT NULL,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`), UNIQUE KEY `uq_beleg_speichername` (`speichername`),
 KEY `idx_beleg_buchung` (`buchung_id`),
 CONSTRAINT `fk_beleg_buchung` FOREIGN KEY (`buchung_id`) REFERENCES `vs_kassenbuch` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
