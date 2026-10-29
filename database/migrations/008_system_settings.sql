-- MVC alpha.10.2: non-secret club configuration and append-only change history.
-- Mail/SMTP credentials are NEVER stored here. No mail is sent by this release.
CREATE TABLE IF NOT EXISTS `vs_system_settings` (
 `id` TINYINT UNSIGNED NOT NULL,
 `values_json` TEXT NOT NULL,
 `revision` INT UNSIGNED NOT NULL,
 `updated_by` INT NOT NULL,
 `updated_at` DATETIME NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vs_system_audit` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `actor_id` INT NOT NULL,
 `revision` INT UNSIGNED NOT NULL,
 `changes_json` TEXT NOT NULL,
 `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 KEY `idx_system_audit_date` (`created_at`),
 KEY `idx_system_audit_actor` (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
