-- alpha.4: Idempotent extension of the original schema. No DROP/DELETE.
-- Original names and integer cents preserve old VereinsSystem compatibility.
CREATE TABLE IF NOT EXISTS `orte` (
  `orts_id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `strasse` VARCHAR(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hausnummer` VARCHAR(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plz` VARCHAR(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ort` VARCHAR(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefon` VARCHAR(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `typ` INT NOT NULL DEFAULT 0,
  `createdby` INT NOT NULL DEFAULT 0,
  `status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`orts_id`),
  UNIQUE KEY `strasse` (`strasse`,`hausnummer`,`plz`,`ort`,`telefon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `abteilungen` (
  `abteilungs_id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `beschreibung` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `homepage` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `abteilungsleiter` INT NOT NULL DEFAULT 0,
  `aktivenumlage` INT NOT NULL DEFAULT 0,
  `status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`abteilungs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `abteilungszugehoerigkeit` (
  `mitglied` INT NOT NULL,
  `abteilung` INT NOT NULL,
  `aktiv` INT NOT NULL DEFAULT 1,
  `beitrittdate` DATE NOT NULL,
  PRIMARY KEY (`mitglied`,`abteilung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
