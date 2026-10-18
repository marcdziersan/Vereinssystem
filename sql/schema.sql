-- phpMyAdmin SQL Dump
-- version 4.0.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 08. Dez 2013 um 21:08
-- Server Version: 5.5.33
-- PHP-Version: 5.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `vereinssystem`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `abteilungen`
--

CREATE TABLE IF NOT EXISTS `abteilungen` (
  `abteilungs_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `beschreibung` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `homepage` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `abteilungsleiter` int(11) NOT NULL COMMENT 'Mitglieds-ID des Abteilungsleiters',
  `aktivenumlage` int(11) NOT NULL COMMENT 'monatlicher Betrag in Cent',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1=Aktiv;2=Papierkorb',
  PRIMARY KEY (`abteilungs_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abteilungen des Vereins' AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `abteilungszugehoerigkeit`
--

CREATE TABLE IF NOT EXISTS `abteilungszugehoerigkeit` (
  `mitglied` int(11) NOT NULL COMMENT 'Mitglieds-ID des Mitgleids',
  `abteilung` int(11) NOT NULL COMMENT 'Abteilungs-ID der Abteilung',
  `aktiv` int(11) NOT NULL DEFAULT '1' COMMENT '0=Inaktiv;1=Aktiv (wichtig für Aktivenumlage)',
  `beitrittdate` date NOT NULL COMMENT 'Datum, wann der Abteilung beigetreten wurde',
  PRIMARY KEY (`mitglied`,`abteilung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Zugehörigkeit der Vereinsmitglieder zu den Abteilungen';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `dienste`
--

CREATE TABLE IF NOT EXISTS `dienste` (
  `dienst_id` int(11) NOT NULL AUTO_INCREMENT,
  `event` int(11) NOT NULL COMMENT 'Veranstaltungs-ID des Events',
  `dienstart` int(11) NOT NULL COMMENT '0= Standdienst; Alternativ Anzahl der Kuchen',
  `person` int(11) NOT NULL COMMENT 'Mitglieder-ID der ausführenden/diensthabenden Person',
  `startzeit` int(11) NOT NULL COMMENT 'Unix-Timestamp, wann der Dienst startet',
  `endzeit` int(11) NOT NULL COMMENT 'Unix-Timestamp, wann der Dienst endet',
  `stand` int(11) NOT NULL COMMENT 'Bei mehreren gleichzeitig anwesenden Person die jeweilige nummer',
  `erstellt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`dienst_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Diensteinteilungen' AUTO_INCREMENT=75 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `einstellungen`
--

CREATE TABLE IF NOT EXISTS `einstellungen` (
  `name` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Einstellungen';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mitglieder`
--

CREATE TABLE IF NOT EXISTS `mitglieder` (
  `mitglieder_id` int(11) NOT NULL AUTO_INCREMENT,
  `mitgliedsnummer` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nachname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `vorname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `geschlecht` int(11) NOT NULL COMMENT '0=nicht gesetzt;1=männlich;2=weiblich',
  `geburtstag` date NOT NULL COMMENT 'Geburtstag als MySQL Date-Element',
  `beitritt` date NOT NULL COMMENT 'Beitrittstag als MySQL-Date Element',
  `anschrift` int(11) NOT NULL COMMENT 'Orts-ID des Wohnortes',
  `handy` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mitgliedschaft` int(11) NOT NULL COMMENT '0=Normal; 1=Unterstützende; 2=Ruhende',
  `abrechnung` int(11) NOT NULL COMMENT '0=Bankeinzug;1=Überweisung;etc...',
  `kontoinhaber` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontonummer` int(11) NOT NULL,
  `bankname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `blz` int(11) NOT NULL,
  `dienste_lastyears` int(11) NOT NULL COMMENT 'Enthält die Anzahl der Dienste, die in den letzten Jahren nicht getätigt wurden',
  `parent1` int(11) NOT NULL COMMENT 'Mutter',
  `parent2` int(11) NOT NULL COMMENT 'Vater',
  `notizen` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Neben der E-Mail alternativer Name zum Einloggen',
  `passwort` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash (Altbestand MD5 beim Login migrierbar)',
  `rights` int(11) NOT NULL DEFAULT '1' COMMENT 'Rechtelevel 0=gesperrt 1=normal 5=admin',
  `lastbirthdaymail` int(11) NOT NULL COMMENT 'Unix-Timestamp des letzten Sendens der Geburtstagsmail',
  `lastjubileemail` int(11) NOT NULL COMMENT 'Unix-Timestamp des letzten Sendens der Jubiläumsmail',
  `lastlogintime` int(11) NOT NULL COMMENT 'Unix-Timestamp des (vor-)letzten Logins',
  `lastloginip` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP-Adresse des (vor-)letzten Logins',
  `currentlogintime` int(11) NOT NULL COMMENT 'Unix-Timestamp des aktuellen/letzten Logins',
  `currentloginip` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP-Adresse des aktuellen/letzten Logins',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1=Aktiv;2=Papierkorb(+Gesperrt)',
  PRIMARY KEY (`mitglieder_id`),
  UNIQUE KEY `mitgliedsnummer` (`mitgliedsnummer`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vereinsmitglieder' AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `news_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `minright` int(11) NOT NULL COMMENT 'Mindesterechtelevel, um den Eintrag zu sehen',
  `author` int(11) NOT NULL COMMENT 'Mitglieder-ID des Erstellers',
  `created` int(11) NOT NULL,
  `lastchange` int(11) NOT NULL,
  `status` int(11) NOT NULL COMMENT '1=Aktiv;2=Papierkorb',
  PRIMARY KEY (`news_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nachrichtenbeiträge' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orte`
--

CREATE TABLE IF NOT EXISTS `orte` (
  `orts_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `strasse` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hausnummer` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plz` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ort` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefon` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telefonnummer (Format: XXX/XXXX)',
  `typ` int(11) NOT NULL COMMENT '0 = Nicht gesetzt, 1 = Wohnort, 2 = Veranstaltungsort',
  `createdby` int(11) NOT NULL COMMENT 'Nutzer-ID des Erstellers',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1=Aktiv;2=Papierkorb',
  PRIMARY KEY (`orts_id`),
  UNIQUE KEY `strasse` (`strasse`,`hausnummer`,`plz`,`ort`,`telefon`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Wohn, Veranstaltungsorte, etc.' AUTO_INCREMENT=48 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `veranstaltungen`
--

CREATE TABLE IF NOT EXISTS `veranstaltungen` (
  `veranstaltungs_id` int(11) NOT NULL AUTO_INCREMENT,
  `veranstaltungsname` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beschreibung` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ort` int(11) NOT NULL COMMENT 'Orts-ID des Veranstaltungsortes',
  `ansprechpartner` int(11) NOT NULL COMMENT 'Mitglieder-ID des Hauptverantwortlichen',
  `startzeit` int(11) NOT NULL COMMENT 'Unix-Timestamp des Veranstaltungsbegins',
  `endzeit` int(11) NOT NULL COMMENT 'Unix-Timestamp des Veranstaltungsendes',
  `mindienste` int(11) NOT NULL DEFAULT '0',
  `minkuchen` int(11) NOT NULL DEFAULT '0',
  `dienstbeschreibung` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`veranstaltungs_id`),
  UNIQUE KEY `name` (`veranstaltungsname`,`ort`,`startzeit`,`endzeit`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Events, Veranstaltungen' AUTO_INCREMENT=4 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


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

-- Vereinssystem 2.3.0 – Nur additive, wiederholbare Schemaänderungen.
-- Beträge werden ganzzahlig in Cent und Daten als DATE gespeichert.
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
CREATE TABLE IF NOT EXISTS `vs_berechtigungen` (
 `mitglied_id` INT NOT NULL,
 `recht` VARCHAR(50) NOT NULL,
 `vergeben_von` INT NOT NULL,
 `vergeben_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`mitglied_id`,`recht`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_audit` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `akteur` INT NOT NULL,
 `aktion` VARCHAR(80) NOT NULL,
 `objekt` VARCHAR(50) NOT NULL,
 `objekt_id` BIGINT NOT NULL DEFAULT 0,
 `details` TEXT NOT NULL,
 PRIMARY KEY (`id`), KEY `idx_audit_date` (`erstellt_am`), KEY `idx_audit_objekt` (`objekt`,`objekt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_tarife` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `art` VARCHAR(20) NOT NULL,
 `abteilung_id` INT NOT NULL DEFAULT 0,
 `gueltig_ab` DATE NOT NULL,
 `betrag_cent` INT NOT NULL,
 `erstellt_von` INT NOT NULL,
 `erstellt_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`), UNIQUE KEY `uq_tarif_datum` (`art`,`abteilung_id`,`gueltig_ab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `vs_schema_version` (
 `version` VARCHAR(15) NOT NULL PRIMARY KEY,
 `installiert_am` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;