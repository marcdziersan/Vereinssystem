-- Alpha.6: additive migration of the historical VereinsSystem news table.
-- Does NOT delete posts or overwrite permissions, authors or timestamps.
CREATE TABLE IF NOT EXISTS `news` (
  `news_id` INT NOT NULL AUTO_INCREMENT,
  `title` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `minright` INT NOT NULL DEFAULT 1,
  `author` INT NOT NULL,
  `created` INT NOT NULL,
  `lastchange` INT NOT NULL,
  `status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`news_id`),
  KEY `idx_news_listing` (`status`,`minright`,`created`,`news_id`),
  KEY `idx_news_author` (`author`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
