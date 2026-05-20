-- ============================================================================
-- Snowbase — SCHEMA
-- Kreira sve tabele. Pokrenuti samo pri prvoj instalaciji ili kad se menjaju
-- tabele. Brise sve podatke ukljucujuci rucno nacrtane staze!
-- Posle ovoga pokreni `seed.sql` za pocetne podatke.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `recenzije`;
DROP TABLE IF EXISTS `faq`;
DROP TABLE IF EXISTS `ticker_items`;
DROP TABLE IF EXISTS `skola_paketi`;
DROP TABLE IF EXISTS `oprema_paketi`;
DROP TABLE IF EXISTS `transport_opcije`;
DROP TABLE IF EXISTS `staze_putanje`;
DROP TABLE IF EXISTS `destinacije_slike`;
DROP TABLE IF EXISTS `vreme_prognoza`;
DROP TABLE IF EXISTS `vreme_trenutno`;
DROP TABLE IF EXISTS `staze_status`;
DROP TABLE IF EXISTS `ski_pas_cene`;
DROP TABLE IF EXISTS `smestaj`;
DROP TABLE IF EXISTS `ski_info`;
DROP TABLE IF EXISTS `destinacije`;
DROP TABLE IF EXISTS `granicni_prelazi`;

-- GRANICNI PRELAZI
CREATE TABLE `granicni_prelazi` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `naziv`               VARCHAR(80) NOT NULL,
    `iz_drzave`           VARCHAR(40) NOT NULL DEFAULT 'Srbija',
    `u_drzavu`            VARCHAR(40) NOT NULL,
    `tipicno_cekanje_min` SMALLINT    NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DESTINACIJE
CREATE TABLE `destinacije` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `slug`                  VARCHAR(60)  NOT NULL UNIQUE,
    `naziv`                 VARCHAR(120) NOT NULL,
    `opis`                  TEXT         DEFAULT NULL,
    `zemlja`                VARCHAR(60)  DEFAULT NULL,
    `region`                VARCHAR(80)  DEFAULT NULL,
    `lat`                   DECIMAL(10,6) DEFAULT NULL,
    `lng`                   DECIMAL(10,6) DEFAULT NULL,
    `distanca_od_bg_km`     INT          NOT NULL DEFAULT 0,
    `prosecna_putarina_eur` DECIMAL(7,2) NOT NULL DEFAULT 0,
    `granicni_prelaz_id`    INT          DEFAULT NULL,
    CONSTRAINT `fk_dest_prelaz` FOREIGN KEY (`granicni_prelaz_id`)
        REFERENCES `granicni_prelazi`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SKI INFO
CREATE TABLE `ski_info` (
    `destinacija_id`    INT NOT NULL PRIMARY KEY,
    `ukupno_staza_km`   DECIMAL(6,1) NOT NULL DEFAULT 0,
    `plave_staze_km`    DECIMAL(6,1) NOT NULL DEFAULT 0,
    `crvene_staze_km`   DECIMAL(6,1) NOT NULL DEFAULT 0,
    `crne_staze_km`     DECIMAL(6,1) NOT NULL DEFAULT 0,
    `broj_zicara`       SMALLINT     NOT NULL DEFAULT 0,
    CONSTRAINT `fk_ski_info_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMESTAJ
CREATE TABLE `smestaj` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`    INT NOT NULL,
    `naziv`             VARCHAR(120) NOT NULL,
    `tip_smestaja`      ENUM('hotel','apartman','pansion','chalet') NOT NULL DEFAULT 'hotel',
    `zvezdice`          TINYINT      NOT NULL DEFAULT 3,
    `kapacitet_osoba`   SMALLINT     NOT NULL DEFAULT 2,
    `cena_po_noci_eur`  DECIMAL(7,2) NOT NULL DEFAULT 0,
    `slika_url`         VARCHAR(255) DEFAULT NULL,
    `redosled`          SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_smestaj_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- VREME
CREATE TABLE `vreme_trenutno` (
    `destinacija_id`  INT NOT NULL PRIMARY KEY,
    `temp_c`          TINYINT      NOT NULL DEFAULT 0,
    `temp_osecaj_c`   TINYINT      DEFAULT NULL,
    `sneg_dno_cm`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `sneg_vrh_cm`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `uslovi`          VARCHAR(60)  NOT NULL DEFAULT '—',
    `vidljivost`      VARCHAR(60)  DEFAULT NULL,
    CONSTRAINT `fk_vreme_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vreme_prognoza` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `dan_skraceno`    VARCHAR(8)   NOT NULL,
    `temp_min`        TINYINT      NOT NULL,
    `temp_max`        TINYINT      NOT NULL,
    `stanje`          VARCHAR(40)  NOT NULL,
    `redosled`        TINYINT      NOT NULL DEFAULT 0,
    KEY `idx_dest_redosled` (`destinacija_id`, `redosled`),
    CONSTRAINT `fk_prognoza_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STAZE STATUS
CREATE TABLE `staze_status` (
    `destinacija_id`     INT NOT NULL PRIMARY KEY,
    `plave_otvorene`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `plave_ukupno`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `crvene_otvorene`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `crvene_ukupno`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `crne_otvorene`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `crne_ukupno`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `zicara_aktivnih`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `zicara_ukupno`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `fk_staze_status_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SKI PAS CENE
CREATE TABLE `ski_pas_cene` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `kategorija`      VARCHAR(40)  NOT NULL,
    `cena_1dan`       DECIMAL(7,2) NOT NULL,
    `cena_2dana`      DECIMAL(7,2) NOT NULL,
    `cena_3dana`      DECIMAL(7,2) NOT NULL,
    `cena_5dana`      DECIMAL(7,2) NOT NULL,
    `cena_6dana`      DECIMAL(7,2) NOT NULL,
    `cena_7dana`      DECIMAL(7,2) NOT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_pas_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DESTINACIJE SLIKE
CREATE TABLE `destinacije_slike` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `tip`             ENUM('hero','mapa_staza','gallery') NOT NULL DEFAULT 'gallery',
    `url`             VARCHAR(255) NOT NULL,
    `alt`             VARCHAR(200) DEFAULT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest_tip` (`destinacija_id`, `tip`),
    CONSTRAINT `fk_slike_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STAZE PUTANJE (SVG) — popunjava se kroz admin/crtanje-staza.php
CREATE TABLE `staze_putanje` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `tip_klasa`       VARCHAR(40)  NOT NULL,
    `naziv`           VARCHAR(100) DEFAULT NULL,
    `svg_d_putanja`   TEXT         NOT NULL,
    `duzina_km`       DECIMAL(5,1) NOT NULL DEFAULT 0,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest_tip` (`destinacija_id`, `tip_klasa`),
    CONSTRAINT `fk_putanje_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TRANSPORT OPCIJE
CREATE TABLE `transport_opcije` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `tip`             ENUM('bus','avion','auto') NOT NULL DEFAULT 'bus',
    `naziv`           VARCHAR(100) NOT NULL,
    `podnaslov`       VARCHAR(120) DEFAULT NULL,
    `cena_po_osobi`   DECIMAL(7,2) NOT NULL DEFAULT 0,
    `stavke_json`     JSON         DEFAULT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_transport_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OPREMA PAKETI
CREATE TABLE `oprema_paketi` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `naziv`           VARCHAR(100) NOT NULL,
    `badge`           VARCHAR(40)  DEFAULT NULL,
    `opis`            TEXT         DEFAULT NULL,
    `cena_eur`        DECIMAL(7,2) NOT NULL,
    `includes_json`   JSON         DEFAULT NULL,
    `napomena`        VARCHAR(120) DEFAULT NULL,
    `preporuceno`     TINYINT(1)   NOT NULL DEFAULT 0,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_oprema_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SKOLA PAKETI
CREATE TABLE `skola_paketi` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `naziv`           VARCHAR(100) NOT NULL,
    `opis`            VARCHAR(255) DEFAULT NULL,
    `cena_eur`        DECIMAL(7,2) NOT NULL,
    `jedinica`        VARCHAR(20)  NOT NULL DEFAULT 'osobi',
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_skola_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RECENZIJE
CREATE TABLE `recenzije` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT DEFAULT NULL,
    `ime`             VARCHAR(80)  NOT NULL,
    `avatar`          VARCHAR(8)   DEFAULT NULL,
    `lokacija`        VARCHAR(120) DEFAULT NULL,
    `tekst`           TEXT         NOT NULL,
    `ocena`           TINYINT      NOT NULL DEFAULT 5,
    `datum_prikaza`   VARCHAR(40)  NOT NULL,
    `tagovi`          JSON         DEFAULT NULL,
    `na_homepage`     TINYINT(1)   NOT NULL DEFAULT 0,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    KEY `idx_homepage` (`na_homepage`),
    CONSTRAINT `fk_recenzije_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TICKER ITEMS
CREATE TABLE `ticker_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `tekst`      VARCHAR(255) NOT NULL,
    `aktivan`    TINYINT(1)   NOT NULL DEFAULT 1,
    `redosled`   SMALLINT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
