-- ============================================================================
-- Snowbase — SCHEMA + SEED (jedinstveni setup fajl)
-- Pokrenuti pri prvoj instalaciji ili kad zelis da resetujes bazu.
-- Brise sve postojece tabele i ubacuje pocetne podatke.
-- Staze (SVG putanje) su hardkodovane u destinacija.php — ne cuvaju se vise u bazi.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. DROP — brise sve tabele
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `recenzije`;
DROP TABLE IF EXISTS `ticker_items`;
DROP TABLE IF EXISTS `skola_paketi`;
DROP TABLE IF EXISTS `oprema_paketi`;
DROP TABLE IF EXISTS `transport_opcije`;
DROP TABLE IF EXISTS `destinacije_slike`;
DROP TABLE IF EXISTS `vreme_prognoza`;
DROP TABLE IF EXISTS `vreme_trenutno`;
DROP TABLE IF EXISTS `staze_status`;
DROP TABLE IF EXISTS `ski_pas_cene`;
DROP TABLE IF EXISTS `smestaj`;
DROP TABLE IF EXISTS `ski_info`;
DROP TABLE IF EXISTS `destinacije`;
DROP TABLE IF EXISTS `granicni_prelazi`;

-- ----------------------------------------------------------------------------
-- 2. CREATE — sve tabele
-- ----------------------------------------------------------------------------

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

-- ============================================================================
-- 3. SEED — pocetni podaci
-- ============================================================================

-- GRANICNI PRELAZI
INSERT INTO `granicni_prelazi` (`id`, `naziv`, `u_drzavu`, `tipicno_cekanje_min`) VALUES
(1, 'Horgoš',     'Mađarska',      30),
(2, 'Batrovci',   'Hrvatska',      25),
(3, 'Šid',        'Hrvatska',      15),
(4, 'Vrška Čuka', 'Bugarska',      20),
(5, 'Gradina',    'Bugarska',      20),
(6, 'Preševo',    'S. Makedonija', 15),
(7, '—',          'Srbija',         0);

-- DESTINACIJE (6 ski centara — bez Zermatt i Cortina)
INSERT INTO `destinacije` (`id`, `slug`, `naziv`, `opis`, `zemlja`, `region`, `lat`, `lng`, `distanca_od_bg_km`, `prosecna_putarina_eur`, `granicni_prelaz_id`) VALUES
(1, 'les-orres', 'Les Orres',
   'Skrivena perla Francuskih Alpa — staze pod stalnim suncem, kompaktno selo i savršena dnevna preglednost. Idealno za porodice i one koji traže privatnost daleko od gužve.',
   'Francuska', 'Francuske Alpe', 44.4553, 6.5372, 1580, 110.00, 1),
(2, 'chamonix', 'Chamonix-Mont-Blanc',
   'Legendarno utočište alpinista. U podnožju najvišeg evropskog vrha, sa 4 odvojena ski područja i autentičnom francuskom čaršijom. Mecca za napredne skijaše.',
   'Francuska', 'Francuske Alpe', 45.9237, 6.8694, 1530, 115.00, 1),
(3, 'val-thorens', 'Val Thorens',
   'Najviše skijalište Evrope na 2300m — Les 3 Vallées, 600 km povezanih staza, garantovani sneg do maja. Pravi raj za pisteur-e koji ne posustaju.',
   'Francuska', 'Francuske Alpe', 45.2980, 6.5800, 1620, 120.00, 1),
(6, 'st-anton', 'St. Anton am Arlberg',
   'Kolevka alpskog skijanja — gde je rođen moderni skijaški sport 1901. Apres-ski legenda, Schindlerov Kar, 305 km povezanih staza Arlberg sistema.',
   'Austrija', 'Tirol', 46.7497, 10.2702, 1025, 65.00, 1),
(7, 'kopaonik', 'Kopaonik',
   'Krov Srbije — najveći domaći ski centar, 60 km staza, 25 žičara. Komforna alternativa Alpima bez graničnih prelaza, sa srpskom hranom i razumnim cenama.',
   'Srbija', 'Centralna Srbija', 43.2860, 20.8164, 280, 0.00, 7),
(8, 'bansko', 'Bansko',
   'Najpristupačnije pravo skijalište regiona — 75 km staza, gondola direktno iz centra grada, balkanska gostoljubivost i cene 30% niže od Alpa.',
   'Bugarska', 'Pirin', 41.8378, 23.4884, 530, 18.00, 5);

-- SKI INFO
INSERT INTO `ski_info` (`destinacija_id`, `ukupno_staza_km`, `plave_staze_km`, `crvene_staze_km`, `crne_staze_km`, `broj_zicara`) VALUES
(1, 100, 35, 50, 15, 17),
(2, 170, 50, 90, 30, 49),
(3, 600, 250, 280, 70, 31),
(6, 305, 130, 145, 30, 87),
(7, 60,  29, 21,  10, 25),
(8, 75,  35, 30,  10, 14);

-- SMESTAJ
INSERT INTO `smestaj` (`destinacija_id`, `naziv`, `tip_smestaja`, `zvezdice`, `kapacitet_osoba`, `cena_po_noci_eur`, `slika_url`, `redosled`) VALUES
-- Les Orres
(1, 'Hôtel Le Mélèze',          'hotel',    4, 2,  98, 'Slike/les-orres/h1.jpg', 10),
(1, 'Résidence Les Crêtes',     'apartman', 3, 4,  72, 'Slike/les-orres/h2.jpg', 20),
(1, 'Chalet du Sommet',         'chalet',   5, 6, 185, 'Slike/les-orres/h3.jpg', 30),
-- Chamonix
(2, 'Hôtel Mont-Blanc',         'hotel',    5, 2, 245, 'Slike/chamonix/h1.jpg', 10),
(2, 'Auberge du Bois Prin',     'hotel',    4, 2, 145, 'Slike/chamonix/h2.jpg', 20),
(2, 'Chamonix Lodge',           'pansion',  3, 4,  88, 'Slike/chamonix/h3.jpg', 30),
-- Val Thorens
(3, 'Altapura Hotel',           'hotel',    5, 2, 325, 'Slike/val-thorens/h1.jpg', 10),
(3, 'Résidence Le Cheval Blanc','apartman', 4, 4, 160, 'Slike/val-thorens/h2.jpg', 20),
-- St. Anton
(6, 'Hotel Schwarzer Adler',    'hotel',    4, 2, 185, 'Slike/st-anton/h1.jpg', 10),
(6, 'Hotel Karl Schranz',       'hotel',    4, 2, 168, 'Slike/st-anton/h2.jpg', 20),
(6, 'Pension Daniela',          'pansion',  3, 3,  78, 'Slike/st-anton/h3.jpg', 30),
-- Kopaonik
(7, 'Grand Hotel & Spa',        'hotel',    4, 2,  98, 'Slike/kopaonik/h1.jpg', 10),
(7, 'MK Mountain Resort',       'hotel',    4, 2,  88, 'Slike/kopaonik/h2.jpg', 20),
(7, 'Apartmani Konaci',         'apartman', 3, 4,  56, 'Slike/kopaonik/h3.jpg', 30),
-- Bansko
(8, 'Kempinski Grand Arena',    'hotel',    5, 2, 148, 'Slike/bansko/h1.jpg', 10),
(8, 'Lucky Bansko Aparthotel',  'apartman', 4, 4,  74, 'Slike/bansko/h2.jpg', 20),
(8, 'Hotel Strazhite',          'hotel',    3, 2,  50, 'Slike/bansko/h3.jpg', 30);

-- VREME TRENUTNO
INSERT INTO `vreme_trenutno` (`destinacija_id`, `temp_c`, `temp_osecaj_c`, `sneg_dno_cm`, `sneg_vrh_cm`, `uslovi`, `vidljivost`) VALUES
(1, -3,  -8,  45, 185, 'Sunčano',           'Odlična (>10 km)'),
(2, -6, -12,  85, 240, 'Pretežno sunčano',  'Odlična (>10 km)'),
(3, -9, -15, 120, 310, 'Sneg',              'Slaba (1-3 km)'),
(6, -4,  -9,  70, 195, 'Pretežno oblačno',  'Dobra (5-10 km)'),
(7, -1,  -5,  35, 110, 'Sunčano',           'Odlična (>10 km)'),
(8, -3,  -7,  50, 140, 'Sunčano',           'Odlična (>10 km)');

-- VREME PROGNOZA
INSERT INTO `vreme_prognoza` (`destinacija_id`, `dan_skraceno`, `temp_min`, `temp_max`, `stanje`, `redosled`) VALUES
(1, 'PON', -8,  -4, 'Oblačno', 1), (1, 'UTO', -12, -7, 'Sneg', 2), (1, 'SRE', -5,  -1, 'Sunčano', 3),
(2, 'PON',-10,  -6, 'Sneg',    1), (2, 'UTO', -8,  -5, 'Oblačno', 2), (2, 'SRE', -6,  -3, 'Sunčano', 3),
(3, 'PON',-15, -10, 'Sneg',    1), (3, 'UTO',-12,  -8, 'Sneg',    2), (3, 'SRE',-10,  -5, 'Oblačno', 3),
(6, 'PON', -8,  -4, 'Sneg',    1), (6, 'UTO', -6,  -3, 'Oblačno', 2), (6, 'SRE', -5,  -1, 'Pretežno sunčano', 3),
(7, 'PON', -5,  -1, 'Sunčano', 1), (7, 'UTO', -3,   1, 'Pretežno sunčano', 2), (7, 'SRE', -2,   2, 'Sunčano', 3),
(8, 'PON', -7,  -3, 'Sunčano', 1), (8, 'UTO', -5,  -2, 'Pretežno sunčano', 2), (8, 'SRE', -4,  -1, 'Oblačno', 3);

-- STAZE STATUS
INSERT INTO `staze_status` (`destinacija_id`, `plave_otvorene`, `plave_ukupno`, `crvene_otvorene`, `crvene_ukupno`, `crne_otvorene`, `crne_ukupno`, `zicara_aktivnih`, `zicara_ukupno`) VALUES
(1, 12, 15,  8, 10,  2,  4, 14, 17),
(2, 18, 22, 24, 30,  9, 12, 42, 49),
(3, 32, 38, 35, 42, 11, 14, 28, 31),
(6, 20, 24, 22, 28,  6,  9, 75, 87),
(7, 10, 13,  6,  9,  2,  3, 22, 25),
(8, 12, 15,  8, 11,  3,  4, 12, 14);

-- SKI PAS CENE
INSERT INTO `ski_pas_cene` (`destinacija_id`, `kategorija`, `cena_1dan`, `cena_2dana`, `cena_3dana`, `cena_5dana`, `cena_6dana`, `cena_7dana`, `redosled`) VALUES
-- Les Orres
(1,'Odrasli',  44,  82, 118, 175, 198, 222, 10),
(1,'Studenti', 37,  68, 100, 148, 168, 188, 20),
(1,'Deca',     26,  48,  72, 105, 118, 132, 30),
(1,'Senior',   34,  64,  92, 138, 156, 175, 40),
-- Chamonix
(2,'Odrasli',  68, 128, 184, 270, 312, 340, 10),
(2,'Studenti', 58, 108, 156, 230, 266, 290, 20),
(2,'Deca',     40,  74, 112, 165, 190, 210, 30),
(2,'Senior',   58, 108, 156, 230, 266, 290, 40),
-- Val Thorens
(3,'Odrasli',  72, 138, 198, 295, 348, 378, 10),
(3,'Studenti', 60, 116, 168, 250, 296, 322, 20),
(3,'Deca',     42,  80, 118, 175, 208, 226, 30),
(3,'Senior',   64, 122, 175, 262, 310, 336, 40),
-- St. Anton
(6,'Odrasli',  67, 128, 186, 274, 318, 345, 10),
(6,'Studenti', 60, 114, 168, 248, 286, 310, 20),
(6,'Deca',     34,  64,  94, 138, 160, 174, 30),
(6,'Senior',   60, 114, 168, 248, 286, 310, 40),
-- Kopaonik
(7,'Odrasli',  40,  74, 106, 158, 185, 205, 10),
(7,'Studenti', 33,  62,  90, 134, 158, 175, 20),
(7,'Deca',     23,  44,  62,  94, 110, 122, 30),
(7,'Senior',   31,  58,  84, 124, 148, 164, 40),
-- Bansko
(8,'Odrasli',  45,  84, 122, 178, 208, 228, 10),
(8,'Studenti', 38,  72, 104, 152, 176, 196, 20),
(8,'Deca',     25,  47,  68,  98, 116, 128, 30),
(8,'Senior',   38,  72, 104, 152, 176, 196, 40);

-- DESTINACIJE SLIKE
INSERT INTO `destinacije_slike` (`destinacija_id`, `tip`, `url`, `alt`, `redosled`) VALUES
-- Les Orres
(1,'mapa_staza', 'Slike/les-orres/mapa.jpg',  'Mapa staza Les Orres',     1),
(1,'hero',       'Slike/les-orres/hero.jpg',  'Les Orres panorama',       1),
(1,'gallery',    'Slike/les-orres/g1.jpg',    'Les Orres staze',          1),
(1,'gallery',    'Slike/les-orres/g2.jpg',    'Les Orres selo',           2),
-- Chamonix
(2,'mapa_staza', 'Slike/chamonix/mapa.jpg',   'Mapa staza Chamonix',      1),
(2,'hero',       'Slike/chamonix/hero.jpg',   'Mont Blanc panorama',      1),
(2,'gallery',    'Slike/chamonix/g1.jpg',     'Chamonix čaršija',         1),
(2,'gallery',    'Slike/chamonix/g2.jpg',     'Aiguille du Midi',         2),
-- Val Thorens
(3,'mapa_staza', 'Slike/val-thorens/mapa.jpg','Mapa staza Val Thorens',   1),
(3,'hero',       'Slike/val-thorens/hero.jpg','Val Thorens vrh',          1),
(3,'gallery',    'Slike/val-thorens/g1.jpg',  '3 Vallées panorama',       1),
(3,'gallery',    'Slike/val-thorens/g2.jpg',  'Cime Caron',                2),
-- St. Anton
(6,'mapa_staza', 'Slike/st-anton/mapa.jpg',   'Mapa staza St. Anton',      1),
(6,'hero',       'Slike/st-anton/hero.jpg',   'St. Anton panorama',        1),
(6,'gallery',    'Slike/st-anton/g1.jpg',     'Galzig',                    1),
(6,'gallery',    'Slike/st-anton/g2.jpg',     'Schindler Kar',             2),
-- Kopaonik
(7,'mapa_staza', 'Slike/kopaonik/mapa.jpg',   'Mapa staza Kopaonik',       1),
(7,'hero',       'Slike/kopaonik/hero.jpg',   'Kopaonik panorama',         1),
(7,'gallery',    'Slike/kopaonik/g1.jpg',     'Pančićev vrh',              1),
(7,'gallery',    'Slike/kopaonik/g2.jpg',     'Sunčana dolina',            2),
-- Bansko
(8,'mapa_staza', 'Slike/bansko/mapa.jpg',     'Mapa staza Bansko',         1),
(8,'hero',       'Slike/bansko/hero.jpg',     'Pirin planina',             1),
(8,'gallery',    'Slike/bansko/g1.jpg',       'Bansko gondola',            1),
(8,'gallery',    'Slike/bansko/g2.jpg',       'Plato staze',               2);

-- TRANSPORT OPCIJE — BUS
INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `cena_po_osobi`, `stavke_json`, `redosled`)
SELECT id, 'bus', 'Agencijski Autobus', 'Direktna linija iz Beograda',
    ROUND(distanca_od_bg_km * 0.085, 0),
    JSON_ARRAY(
        JSON_OBJECT('label','Polazak',     'vrednost','Sava Centar, 22:00h'),
        JSON_OBJECT('label','Trajanje',    'vrednost', CONCAT('~', ROUND(distanca_od_bg_km/80), 'h vožnje')),
        JSON_OBJECT('label','Povratak',    'vrednost','Nedeljom, 14:00h'),
        JSON_OBJECT('label','Prtljag',     'vrednost','Kofer + ski torba'),
        JSON_OBJECT('label','Cena karte',  'vrednost', CONCAT(ROUND(distanca_od_bg_km * 0.085, 0), ' EUR / osobi'))
    ), 10
FROM `destinacije`;

-- TRANSPORT OPCIJE — AVION
INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `cena_po_osobi`, `stavke_json`, `redosled`)
SELECT id, 'avion', 'Avion + Transfer', 'Najbrža opcija',
    CASE
        WHEN zemlja = 'Srbija' THEN 0
        WHEN zemlja = 'Bugarska' THEN 180
        ELSE 250
    END,
    JSON_ARRAY(
        JSON_OBJECT('label','Aerodrom',          'vrednost', CASE WHEN zemlja='Srbija' THEN 'Nije primenljivo' ELSE 'BEG - Najbliži' END),
        JSON_OBJECT('label','Let',               'vrednost', CASE WHEN zemlja='Srbija' THEN '—' ELSE '~2h' END),
        JSON_OBJECT('label','Transfer',          'vrednost','Aerodrom - Hotel'),
        JSON_OBJECT('label','Trajanje transfera','vrednost','~1.5h'),
        JSON_OBJECT('label','Paket cena',        'vrednost', CONCAT(
            CASE WHEN zemlja='Srbija' THEN '—' WHEN zemlja='Bugarska' THEN '180' ELSE '250' END, ' EUR / osobi'))
    ), 20
FROM `destinacije`;

-- TRANSPORT OPCIJE — AUTO
INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `cena_po_osobi`, `stavke_json`, `redosled`)
SELECT d.id, 'auto', 'Sopstveni Auto', CONCAT(d.distanca_od_bg_km,' km od Beograda'), 0,
    JSON_ARRAY(
        JSON_OBJECT('label','Putarina',       'vrednost', CONCAT(d.prosecna_putarina_eur*2, ' EUR povratno')),
        JSON_OBJECT('label','Zimska oprema',  'vrednost','Obavezna'),
        JSON_OBJECT('label','Granični prelaz','vrednost', COALESCE(gp.naziv,'—'))
    ), 30
FROM `destinacije` d LEFT JOIN `granicni_prelazi` gp ON gp.id = d.granicni_prelaz_id;

-- OPREMA PAKETI
INSERT INTO `oprema_paketi` (`destinacija_id`, `naziv`, `badge`, `opis`, `cena_eur`, `includes_json`, `napomena`, `preporuceno`, `redosled`)
SELECT id, 'Starter Komplet', 'Ekonomični',
    'Idealno za početnike i rekreativce. Proverena oprema renomirane klase.',
    22,
    JSON_ARRAY('Skije (all-mountain, početni nivo)','Pancerice (toplinski podstavljene)','Štapovi + kaiš za zapešće','Kaciga (EN 1077 certifikat)'),
    'Min. 2 dana', 0, 10
FROM `destinacije`;

INSERT INTO `oprema_paketi` (`destinacija_id`, `naziv`, `badge`, `opis`, `cena_eur`, `includes_json`, `napomena`, `preporuceno`, `redosled`)
SELECT id, 'Expert Performance', 'Premium',
    'Napredni modeli skija za iskusne skijaše koji traže preciznost i kontrolu na svakom terenu.',
    38,
    JSON_ARRAY('Race/Freeride skije','Pancerice (race-fit, carbon vložak)','Štapovi od karbona','Kaciga + zaštitne naočare','Zaštitni šorts i back protektor'),
    'Preporučujemo', 1, 20
FROM `destinacije`;

-- SKOLA PAKETI
INSERT INTO `skola_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `jedinica`, `redosled`)
SELECT id, 'Grupni čas (do 6 osoba)', '2h · Svi nivoi · Srpski / Engleski', 18, 'osobi', 10 FROM `destinacije`
UNION ALL SELECT id, 'Individualni čas',        '2h · Personalizovani program',        65, 'čas',   20 FROM `destinacije`
UNION ALL SELECT id, '5-dnevni grupni kurs',    '2h dnevno · Sve uzraste · Sertifikat', 72, 'osobi', 30 FROM `destinacije`
UNION ALL SELECT id, 'Snowboard starter',       '3h · Početnici · Oprema uključena',    48, 'osobi', 40 FROM `destinacije`;

-- RECENZIJE — homepage karusel
INSERT INTO `recenzije` (`destinacija_id`,`ime`,`avatar`,`lokacija`,`tekst`,`ocena`,`datum_prikaza`,`na_homepage`,`redosled`) VALUES
(NULL,'Marija T.','MT','Les Orres, Francuska',
 'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg prašinast celu nedelju. Organizacija Snowbase bila je besprekorna od prvog do poslednjeg dana.',
 5,'Januar 2026.',1,10),
(NULL,'Stefan K.','SK','St. Anton, Austrija',
 'Treće godišnje putovanje. Smeštaj tačno prema opisu, transfer brz, granični prelaz bez čekanja. Jednom kad probate Snowbase, ne idete drugde.',
 5,'Februar 2026.',1,20),
(NULL,'Milan & Tara','MT','Val Thorens, Francuska',
 'Najveće skijalište koje smo iskusili — staze beskrajne, sneg garantovan. Snowbase organizacija prvoklasna od polaska do povratka.',
 5,'Decembar 2025.',1,30),
(NULL,'Nikola P.','NP','Chamonix, Francuska',
 'Sve savršeno isplanirano — od polaska iz Beograda do povratka, nula stresa. Kalkulator je bio tačan do poslednjeg evra.',
 5,'Januar 2026.',1,40);

-- RECENZIJE — per-destinacija
INSERT INTO `recenzije` (`destinacija_id`,`ime`,`tekst`,`ocena`,`datum_prikaza`,`tagovi`,`redosled`)
SELECT id,'Marko D.',
    'Savršeno organizovan put. Staze prelepe, hotel taman koliko nam treba. Ekipa Snowbase brza i profesionalna.',
    5,'Januar 2026.', JSON_ARRAY('Staze ★★★★★','Organizacija ★★★★★'), 10
FROM `destinacije`;

INSERT INTO `recenzije` (`destinacija_id`,`ime`,`tekst`,`ocena`,`datum_prikaza`,`tagovi`,`redosled`)
SELECT id,'Jelena & Vuk',
    'Treća sezona zaredom. Cena je fer, kvalitet izuzetan. Ski škola savršena za našu decu — preporučujemo svima.',
    5,'Februar 2026.', JSON_ARRAY('Porodično ★★★★★','Smeštaj ★★★★☆'), 20
FROM `destinacije`;

-- TICKER ITEMS
INSERT INTO `ticker_items` (`tekst`,`redosled`) VALUES
('Les Orres: 185 cm snega na vrhu · Prajder odličan',10),
('Batrovci (SRB/HRV): Zadržavanje ~25 min',20),
('Chamonix-Mont-Blanc: Sve žičare u pogonu · Vidljivost odlična',30),
('Simplon prevoj (CH): Obavezni lanci ili zimske gume',40),
('Val Thorens: 310 cm snega · Sezona traje do kraja aprila',50),
('Horgoš (SRB/HUN): Bez zadržavanja',60),
('Innsbruck / St. Anton: -6°C · Sunčano · Sve staze otvorene',70),
('Brenner autoput (AT): Zimska oprema obavezna iznad 700 m',80),
('Šid (SRB/HRV): Zadržavanje ~15 min',100),
('Kopaonik: -1°C · Sunčano · Domaća sezona u punom jeku',130),
('Bansko: 140 cm snega · Najpristupačnija opcija u regionu',140);
