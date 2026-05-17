-- ============================================================================
-- Snowbase — Premium Alpine Travel Catalog
-- Single source of truth: kompletna schema + seed za 8 destinacija.
-- Pokreni u phpMyAdmin nad bazom `peak_palm`.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- DROP all (clean slate)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 1) GRANICNI PRELAZI (globalna)
-- ----------------------------------------------------------------------------
CREATE TABLE `granicni_prelazi` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `naziv`               VARCHAR(80) NOT NULL,
    `iz_drzave`           VARCHAR(40) NOT NULL DEFAULT 'Srbija',
    `u_drzavu`            VARCHAR(40) NOT NULL,
    `tipicno_cekanje_min` SMALLINT    NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2) DESTINACIJE
-- ----------------------------------------------------------------------------
CREATE TABLE `destinacije` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
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

-- ----------------------------------------------------------------------------
-- 3) SKI INFO (1:1 sa destinacijom)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 4) SMESTAJ (1:N — hoteli po destinaciji)
-- ----------------------------------------------------------------------------
CREATE TABLE `smestaj` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`    INT NOT NULL,
    `naziv`             VARCHAR(120) NOT NULL,
    `zvezdice`          TINYINT      NOT NULL DEFAULT 3,
    `kapacitet_osoba`   SMALLINT     NOT NULL DEFAULT 2,
    `cena_po_noci_eur`  DECIMAL(7,2) NOT NULL DEFAULT 0,
    `slika_url`         VARCHAR(255) DEFAULT NULL,
    `redosled`          SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_smestaj_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5) VREME TRENUTNO (1:1) i VREME PROGNOZA (1:N)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 6) STAZE STATUS (live: otvorene/ukupno po tipu)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 7) SKI PAS CENE (1:N)
-- ----------------------------------------------------------------------------
CREATE TABLE `ski_pas_cene` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `kategorija`      VARCHAR(40)  NOT NULL,
    `cena_1dan`       DECIMAL(7,2) NOT NULL,
    `cena_3dana`      DECIMAL(7,2) NOT NULL,
    `cena_6dana`      DECIMAL(7,2) NOT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_pas_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8) DESTINACIJE SLIKE (1:N)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 9) STAZE PUTANJE (SVG za hero mapu)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 10) TRANSPORT OPCIJE (1:N, JSON stavke)
-- ----------------------------------------------------------------------------
CREATE TABLE `transport_opcije` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT NOT NULL,
    `tip`             VARCHAR(40)  NOT NULL DEFAULT 'bus',
    `naziv`           VARCHAR(100) NOT NULL,
    `podnaslov`       VARCHAR(120) DEFAULT NULL,
    `stavke_json`     JSON         DEFAULT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_transport_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11) OPREMA PAKETI (1:N)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 12) SKOLA PAKETI (1:N)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 13) RECENZIJE (1:N po destinaciji, NULL = homepage karusel)
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- 14) FAQ (NULL = globalno)
-- ----------------------------------------------------------------------------
CREATE TABLE `faq` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id`  INT          DEFAULT NULL,
    `pitanje`         VARCHAR(255) NOT NULL,
    `odgovor`         TEXT         NOT NULL,
    `redosled`        SMALLINT     NOT NULL DEFAULT 0,
    `aktivan`         TINYINT(1)   NOT NULL DEFAULT 1,
    KEY `idx_dest_redosled` (`destinacija_id`, `redosled`),
    CONSTRAINT `fk_faq_dest` FOREIGN KEY (`destinacija_id`)
        REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 15) TICKER ITEMS (globalna live obavestenja)
-- ----------------------------------------------------------------------------
CREATE TABLE `ticker_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `tekst`      VARCHAR(255) NOT NULL,
    `aktivan`    TINYINT(1)   NOT NULL DEFAULT 1,
    `redosled`   SMALLINT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED PODACI
-- ============================================================================

-- Granicni prelazi
INSERT INTO `granicni_prelazi` (`id`, `naziv`, `u_drzavu`, `tipicno_cekanje_min`) VALUES
(1, 'Horgoš',     'Mađarska', 30),
(2, 'Batrovci',   'Hrvatska', 25),
(3, 'Šid',        'Hrvatska', 15),
(4, 'Vrška Čuka', 'Bugarska', 20),
(5, 'Gradina',    'Bugarska', 20),
(6, 'Preševo',    'S. Makedonija', 15),
(7, '—',          'Srbija',   0);

-- ============================================================================
-- DESTINACIJE (8 ukupno)
-- ============================================================================
INSERT INTO `destinacije` (`id`, `naziv`, `opis`, `zemlja`, `region`, `lat`, `lng`, `distanca_od_bg_km`, `prosecna_putarina_eur`, `granicni_prelaz_id`) VALUES
(1, 'Les Orres',
   'Skrivena perla Francuskih Alpa — staze pod stalnim suncem, kompaktno selo i savršena dnevna preglednost. Idealno za porodice i one koji traže privatnost daleko od gužve.',
   'Francuska', 'Francuske Alpe', 44.4553, 6.5372, 1580, 110.00, 1),
(2, 'Chamonix-Mont-Blanc',
   'Legendarno utočište alpinista. U podnožju najvišeg evropskog vrha, sa 4 odvojena ski područja i autentičnom francuskom čaršijom. Mecca za napredne skijaše.',
   'Francuska', 'Francuske Alpe', 45.9237, 6.8694, 1530, 115.00, 1),
(3, 'Val Thorens',
   'Najviše skijalište Evrope na 2300m — Les 3 Vallées, 600 km povezanih staza, garantovani sneg do maja. Pravi raj za pisteur-e koji ne posustaju.',
   'Francuska', 'Francuske Alpe', 45.2980, 6.5800, 1620, 120.00, 1),
(4, 'Zermatt',
   'Pod Matterhornom — staklena selo bez automobila, pet hiljada metara nadmorske visine i ski pas povezan sa Cervinia-om u Italiji. Definicija švajcarske eleganciji.',
   'Švajcarska', 'Valais', 46.0207, 7.7491, 1350, 95.00, 1),
(5, 'Cortina d''Ampezzo',
   'Kraljica Dolomita — domaćin Olimpijade 2026. Bleda krečnjačka zubaca, žute fasade i 1200 km povezanih staza u Dolomiti Superski sistemu.',
   'Italija', 'Dolomiti', 46.5396, 12.1357, 1190, 75.00, 1),
(6, 'St. Anton am Arlberg',
   'Kolevka alpskog skijanja — gde je rođen moderni skijaški sport 1901. Apres-ski legenda, Schindlerov Kar, 305 km povezanih staza Arlberg sistema.',
   'Austrija', 'Tirol', 46.7497, 10.2702, 1025, 65.00, 1),
(7, 'Kopaonik',
   'Krov Srbije — najveći domaći ski centar, 60 km staza, 25 žičara. Komforna alternativa Alpima bez graničnih prelaza, sa srpskom hranom i razumnim cenama.',
   'Srbija', 'Centralna Srbija', 43.2860, 20.8164, 280, 0.00, 7),
(8, 'Bansko',
   'Najpristupačnije pravo skijalište regiona — 75 km staza, gondola direktno iz centra grada, balkanska gostoljubivost i cene 30% niže od Alpa.',
   'Bugarska', 'Pirin', 41.8378, 23.4884, 530, 18.00, 5);

-- ============================================================================
-- SKI INFO (broj_zicara, ukupno km, plave/crvene/crne km)
-- ============================================================================
INSERT INTO `ski_info` (`destinacija_id`, `ukupno_staza_km`, `plave_staze_km`, `crvene_staze_km`, `crne_staze_km`, `broj_zicara`) VALUES
(1, 100, 35, 50, 15, 17),   -- Les Orres
(2, 170, 50, 90, 30, 49),   -- Chamonix
(3, 600, 250, 280, 70, 31), -- Val Thorens
(4, 360, 110, 195, 55, 53), -- Zermatt
(5, 140, 60, 65, 15, 34),   -- Cortina
(6, 305, 130, 145, 30, 87), -- St. Anton
(7, 60,  29, 21,  10, 25),  -- Kopaonik
(8, 75,  35, 30,  10, 14);  -- Bansko

-- ============================================================================
-- SMESTAJ (2-3 hotela po destinaciji)
-- ============================================================================
INSERT INTO `smestaj` (`destinacija_id`, `naziv`, `zvezdice`, `kapacitet_osoba`, `cena_po_noci_eur`, `slika_url`, `redosled`) VALUES
-- Les Orres
(1, 'Hôtel Le Mélèze',       4, 2,  95, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop', 10),
(1, 'Résidence Les Crêtes',  3, 4,  68, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop', 20),
(1, 'Chalet du Sommet',      5, 6, 180, 'https://images.unsplash.com/photo-1518684079-3c830dcef090?q=80&w=800&auto=format&fit=crop', 30),
-- Chamonix
(2, 'Hôtel Mont-Blanc',       5, 2, 240, 'https://images.unsplash.com/photo-1455587734955-081b22074882?q=80&w=800&auto=format&fit=crop', 10),
(2, 'Auberge du Bois Prin',   4, 2, 140, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=800&auto=format&fit=crop', 20),
(2, 'Chamonix Lodge',         3, 4,  85, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop', 30),
-- Val Thorens
(3, 'Altapura Hotel',         5, 2, 320, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop', 10),
(3, 'Résidence Le Cheval Blanc', 4, 4, 155, 'https://images.unsplash.com/photo-1518684079-3c830dcef090?q=80&w=800&auto=format&fit=crop', 20),
-- Zermatt
(4, 'Hotel Zermatterhof',      5, 2, 380, 'https://images.unsplash.com/photo-1455587734955-081b22074882?q=80&w=800&auto=format&fit=crop', 10),
(4, 'The Omnia Mountain Lodge', 5, 2, 420, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=800&auto=format&fit=crop', 20),
(4, 'Hotel Bahnhof',           3, 4,  90, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop', 30),
-- Cortina
(5, 'Cristallo Hotel Spa',     5, 2, 285, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop', 10),
(5, 'Hotel de la Poste',       4, 2, 165, 'https://images.unsplash.com/photo-1518684079-3c830dcef090?q=80&w=800&auto=format&fit=crop', 20),
(5, 'Camina Suite Spa',        4, 4, 145, 'https://images.unsplash.com/photo-1455587734955-081b22074882?q=80&w=800&auto=format&fit=crop', 30),
-- St. Anton
(6, 'Hotel Schwarzer Adler',   4, 2, 180, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=800&auto=format&fit=crop', 10),
(6, 'Hotel Karl Schranz',      4, 2, 165, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop', 20),
(6, 'Pension Daniela',         3, 3,  75, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop', 30),
-- Kopaonik
(7, 'Grand Hotel & Spa',       4, 2,  95, 'https://images.unsplash.com/photo-1518684079-3c830dcef090?q=80&w=800&auto=format&fit=crop', 10),
(7, 'MK Mountain Resort',      4, 2,  85, 'https://images.unsplash.com/photo-1455587734955-081b22074882?q=80&w=800&auto=format&fit=crop', 20),
(7, 'Apartmani Konaci',        3, 4,  55, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=800&auto=format&fit=crop', 30),
-- Bansko
(8, 'Kempinski Grand Arena',   5, 2, 145, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop', 10),
(8, 'Lucky Bansko Aparthotel', 4, 4,  72, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop', 20),
(8, 'Hotel Strazhite',         3, 2,  48, 'https://images.unsplash.com/photo-1518684079-3c830dcef090?q=80&w=800&auto=format&fit=crop', 30);

-- ============================================================================
-- VREME TRENUTNO
-- ============================================================================
INSERT INTO `vreme_trenutno` (`destinacija_id`, `temp_c`, `temp_osecaj_c`, `sneg_dno_cm`, `sneg_vrh_cm`, `uslovi`, `vidljivost`) VALUES
(1,  -3,  -8,  45, 185, 'Sunčano',  'Odlična (>10 km)'),
(2,  -6, -12,  85, 240, 'Pretežno sunčano', 'Odlična (>10 km)'),
(3,  -9, -15, 120, 310, 'Sneg', 'Slaba (1-3 km)'),
(4,  -7, -13,  95, 280, 'Sunčano', 'Odlična (>10 km)'),
(5,  -2,  -6,  60, 165, 'Oblačno', 'Dobra (5-10 km)'),
(6,  -4,  -9,  70, 195, 'Pretežno oblačno', 'Dobra (5-10 km)'),
(7,  -1,  -5,  35, 110, 'Sunčano', 'Odlična (>10 km)'),
(8,  -3,  -7,  50, 140, 'Sunčano', 'Odlična (>10 km)');

-- ============================================================================
-- VREME PROGNOZA (3 dana po destinaciji)
-- ============================================================================
INSERT INTO `vreme_prognoza` (`destinacija_id`, `dan_skraceno`, `temp_min`, `temp_max`, `stanje`, `redosled`) VALUES
(1, 'PON', -8,  -4, 'Oblačno', 1), (1, 'UTO', -12, -7, 'Sneg', 2), (1, 'SRE', -5,  -1, 'Sunčano', 3),
(2, 'PON',-10,  -6, 'Sneg',    1), (2, 'UTO', -8,  -5, 'Oblačno', 2), (2, 'SRE', -6,  -3, 'Sunčano', 3),
(3, 'PON',-15, -10, 'Sneg',    1), (3, 'UTO',-12,  -8, 'Sneg',    2), (3, 'SRE',-10,  -5, 'Oblačno', 3),
(4, 'PON',-11,  -7, 'Sunčano', 1), (4, 'UTO', -9,  -5, 'Sunčano', 2), (4, 'SRE', -7,  -3, 'Pretežno sunčano', 3),
(5, 'PON', -6,  -2, 'Oblačno', 1), (5, 'UTO', -4,  -1, 'Sunčano', 2), (5, 'SRE', -3,   1, 'Sunčano', 3),
(6, 'PON', -8,  -4, 'Sneg',    1), (6, 'UTO', -6,  -3, 'Oblačno', 2), (6, 'SRE', -5,  -1, 'Pretežno sunčano', 3),
(7, 'PON', -5,  -1, 'Sunčano', 1), (7, 'UTO', -3,   1, 'Pretežno sunčano', 2), (7, 'SRE', -2,   2, 'Sunčano', 3),
(8, 'PON', -7,  -3, 'Sunčano', 1), (8, 'UTO', -5,  -2, 'Pretežno sunčano', 2), (8, 'SRE', -4,  -1, 'Oblačno', 3);

-- ============================================================================
-- STAZE STATUS (otvorene/ukupno)
-- ============================================================================
INSERT INTO `staze_status` (`destinacija_id`, `plave_otvorene`, `plave_ukupno`, `crvene_otvorene`, `crvene_ukupno`, `crne_otvorene`, `crne_ukupno`, `zicara_aktivnih`, `zicara_ukupno`) VALUES
(1, 12, 15,  8, 10,  2,  4, 14, 17),
(2, 18, 22, 24, 30,  9, 12, 42, 49),
(3, 32, 38, 35, 42, 11, 14, 28, 31),
(4, 16, 20, 26, 32, 12, 15, 48, 53),
(5, 14, 18, 14, 18,  3,  6, 28, 34),
(6, 20, 24, 22, 28,  6,  9, 75, 87),
(7, 10, 13,  6,  9,  2,  3, 22, 25),
(8, 12, 15,  8, 11,  3,  4, 12, 14);

-- ============================================================================
-- SKI PAS CENE (4 kategorije po destinaciji)
-- ============================================================================
INSERT INTO `ski_pas_cene` (`destinacija_id`, `kategorija`, `cena_1dan`, `cena_3dana`, `cena_6dana`, `redosled`) VALUES
-- Les Orres
(1,'Odrasli',42,115,195,10),(1,'Studenti',35,95,162,20),(1,'Deca',25,68,112,30),(1,'Senior',32,88,148,40),
-- Chamonix
(2,'Odrasli',64,177,310,10),(2,'Studenti',54,150,265,20),(2,'Deca',38,108,188,30),(2,'Senior',54,150,265,40),
-- Val Thorens
(3,'Odrasli',62,179,326,10),(3,'Studenti',52,152,278,20),(3,'Deca',37,108,195,30),(3,'Senior',56,160,294,40),
-- Zermatt
(4,'Odrasli',79,222,398,10),(4,'Studenti',67,189,338,20),(4,'Deca',40,111,199,30),(4,'Senior',71,200,358,40),
-- Cortina
(5,'Odrasli',61,158,256,10),(5,'Studenti',54,141,232,20),(5,'Deca',43,111,179,30),(5,'Senior',54,141,232,40),
-- St. Anton
(6,'Odrasli',64,180,308,10),(6,'Studenti',57,162,277,20),(6,'Deca',32,90,154,30),(6,'Senior',57,162,277,40),
-- Kopaonik
(7,'Odrasli',38,102,182,10),(7,'Studenti',32,86,154,20),(7,'Deca',22,60,110,30),(7,'Senior',30,80,144,40),
-- Bansko
(8,'Odrasli',43,118,205,10),(8,'Studenti',36,99,172,20),(8,'Deca',24,66,114,30),(8,'Senior',36,99,172,40);

-- ============================================================================
-- DESTINACIJE SLIKE (hero + mapa_staza po destinaciji)
-- ============================================================================
INSERT INTO `destinacije_slike` (`destinacija_id`, `tip`, `url`, `alt`, `redosled`) VALUES
(1,'mapa_staza','Slike/les_orres_mapa.jpg','Mapa staza Les Orres',1),
(1,'gallery','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Les Orres staza',1),
(1,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Les Orres panorama',2),
(2,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Chamonix',1),
(2,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Chamonix Mont Blanc',1),
(3,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Val Thorens',1),
(3,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Val Thorens vrh',1),
(4,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Zermatt',1),
(4,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Matterhorn',1),
(5,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Cortina',1),
(5,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Dolomiti',1),
(6,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza St. Anton',1),
(6,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Arlberg',1),
(7,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Kopaonik',1),
(7,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Kopaonik vrh',1),
(8,'mapa_staza','https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=1200&auto=format&fit=crop','Mapa staza Bansko',1),
(8,'gallery','https://images.unsplash.com/photo-1517825738774-7de9363ef735?q=80&w=1200&auto=format&fit=crop','Pirin planina',1);

-- ============================================================================
-- STAZE PUTANJE (3 staze po destinaciji — placeholder SVG curves)
-- ============================================================================
INSERT INTO `staze_putanje` (`destinacija_id`, `tip_klasa`, `naziv`, `svg_d_putanja`, `duzina_km`, `redosled`) VALUES
-- Les Orres
(1,'plava','La Cascade','M 200 150 Q 300 220 400 300', 8.5, 1),
(1,'crvena','Rouge Mélèzes','M306.5 123.5C295.3 123.9 292.167 118.667 292 116C295.5 110.5 294.5 111 289.5 103.5C297.1 100.7 303.5 97 305.5 90.5C330.3 92.5 330 90.5 330 84C340.5 74.5 341 74.5 333.5 68.5C335.9 60.1 327.833 58 324 58C319.5 58 317.403 63.6563 312 66.5C302.5 71.5 299.5 74 290.5 76C289.5 79 281.6 84.2 276 85C274 86.6667 268 92.5 268.5 101C265.5 104.5 262 104.7 256 107.5', 12.3, 2),
(1,'crna','Pylône','M 320 80 L 310 180 L 290 290', 4.7, 3),
-- Chamonix
(2,'plava','La Verte','M 120 100 Q 250 180 380 280', 14.0, 1),
(2,'crvena','Charamillon','M 180 80 Q 300 140 420 220', 18.5, 2),
(2,'crna','Vallée Blanche','M 220 60 Q 310 200 400 320', 22.0, 3),
-- Val Thorens
(3,'plava','Plein Sud','M 100 120 Q 280 180 460 240', 25.5, 1),
(3,'crvena','Cime Caron','M 140 90 Q 280 200 480 250', 32.0, 2),
(3,'crna','Combe de Rosaël','M 180 70 Q 310 180 470 290', 18.5, 3),
-- Zermatt
(4,'plava','Sunnegga','M 160 130 Q 290 200 420 270', 16.0, 1),
(4,'crvena','Klein Matterhorn','M 200 90 Q 300 200 410 310', 28.5, 2),
(4,'crna','Tiefbach','M 240 70 Q 330 200 420 290', 12.0, 3),
-- Cortina
(5,'plava','Pocol','M 140 110 Q 270 200 400 290', 9.5, 1),
(5,'crvena','Olympia','M 180 90 Q 290 200 420 280', 11.0, 2),
(5,'crna','Forcella Rossa','M 230 70 Q 320 200 410 310', 5.5, 3),
-- St. Anton
(6,'plava','Galzig','M 120 120 Q 270 200 420 260', 18.0, 1),
(6,'crvena','Kapall','M 170 90 Q 290 200 430 280', 22.5, 2),
(6,'crna','Schindler Kar','M 220 70 Q 310 200 410 320', 7.5, 3),
-- Kopaonik
(7,'plava','Sunčana dolina','M 160 140 Q 280 200 400 260', 12.0, 1),
(7,'crvena','Karaman greben','M 200 100 Q 300 200 410 280', 8.5, 2),
(7,'crna','Pančićev vrh','M 250 80 Q 320 200 400 300', 3.5, 3),
-- Bansko
(8,'plava','Plato','M 150 130 Q 280 200 410 270', 14.0, 1),
(8,'crvena','Bunderitsa','M 190 100 Q 300 200 420 280', 12.5, 2),
(8,'crna','Tomba','M 240 80 Q 320 200 410 300', 4.5, 3);

-- ============================================================================
-- TRANSPORT OPCIJE (bus + avion + auto po destinaciji)
-- Stavke se generišu kroz formule (distanca, putarina) za realnost.
-- ============================================================================
INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `stavke_json`, `redosled`)
SELECT id, 'bus', 'Agencijski Autobus', 'Direktna linija',
    JSON_ARRAY(
        JSON_OBJECT('label','Polazak',     'vrednost','Sava Centar, 22:00h'),
        JSON_OBJECT('label','Trajanje',    'vrednost', CONCAT('~', ROUND(distanca_od_bg_km/80), 'h vožnje')),
        JSON_OBJECT('label','Povratak',    'vrednost','Nedeljom, 14:00h'),
        JSON_OBJECT('label','Prtljag',     'vrednost','Kofer + ski torba'),
        JSON_OBJECT('label','Cena prevoza','vrednost', CONCAT(ROUND(distanca_od_bg_km*0.055), ' EUR / osobi'))
    ), 10
FROM `destinacije`;

INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `stavke_json`, `redosled`)
SELECT id, 'avion', 'Avion + Transfer', 'Najbrža opcija',
    JSON_ARRAY(
        JSON_OBJECT('label','Aerodrom',          'vrednost','BEG - Najbliži'),
        JSON_OBJECT('label','Let',               'vrednost','~2h'),
        JSON_OBJECT('label','Transfer',          'vrednost','Aerodrom - Hotel'),
        JSON_OBJECT('label','Trajanje transfera','vrednost','~1.5h'),
        JSON_OBJECT('label','Šatl cena',         'vrednost','35 EUR / osobi')
    ), 20
FROM `destinacije`;

INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `stavke_json`, `redosled`)
SELECT d.id, 'auto', 'Sopstveni Auto', CONCAT(d.distanca_od_bg_km,' km od Beograda'),
    JSON_ARRAY(
        JSON_OBJECT('label','Putarina',       'vrednost', CONCAT(d.prosecna_putarina_eur*2, ' EUR povratno')),
        JSON_OBJECT('label','Zimska oprema',  'vrednost','Obavezna'),
        JSON_OBJECT('label','Granični prelaz','vrednost', COALESCE(gp.naziv,'—'))
    ), 30
FROM `destinacije` d LEFT JOIN `granicni_prelazi` gp ON gp.id = d.granicni_prelaz_id;

-- ============================================================================
-- OPREMA PAKETI (Starter + Premium za sve)
-- ============================================================================
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

-- ============================================================================
-- SKOLA PAKETI (4 paketa po destinaciji)
-- ============================================================================
INSERT INTO `skola_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `jedinica`, `redosled`)
SELECT id, 'Grupni čas (do 6 osoba)', '2h · Svi nivoi · Srpski / Engleski', 18, 'osobi', 10 FROM `destinacije`
UNION ALL SELECT id, 'Individualni čas',        '2h · Personalizovani program',        65, 'čas',   20 FROM `destinacije`
UNION ALL SELECT id, '5-dnevni grupni kurs',    '2h dnevno · Sve uzraste · Sertifikat', 72, 'osobi', 30 FROM `destinacije`
UNION ALL SELECT id, 'Snowboard starter',       '3h · Početnici · Oprema uključena',    48, 'osobi', 40 FROM `destinacije`;

-- ============================================================================
-- RECENZIJE (homepage karusel + per-destinacija)
-- ============================================================================
-- Homepage karusel (destinacija_id = NULL, na_homepage=1)
INSERT INTO `recenzije` (`destinacija_id`,`ime`,`avatar`,`lokacija`,`tekst`,`ocena`,`datum_prikaza`,`na_homepage`,`redosled`) VALUES
(NULL,'Marija T.','MT','Les Orres, Francuska',
 'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg prašinast celu nedelju. Organizacija Snowbase bila je besprekorna od prvog do poslednjeg dana.',
 5,'Januar 2026.',1,10),
(NULL,'Stefan K.','SK','St. Anton, Austrija',
 'Treće godišnje putovanje. Smeštaj tačno prema opisu, transfer brz, granični prelaz bez čekanja. Jednom kad probate Snowbase, ne idete drugde.',
 5,'Februar 2026.',1,20),
(NULL,'Ana & Bojan','AB','Cortina d''Ampezzo, Italija',
 'Odlično za porodice. Ski škola za početnike strpljiva i profesionalna. Cortina je čarobno selo — preporučujemo svima.',
 5,'Decembar 2025.',1,30),
(NULL,'Nikola P.','NP','Zermatt, Švajcarska',
 'Sve savršeno isplanirano — od polaska iz Beograda do povratka, nula stresa. Kalkulator je bio tačan do poslednjeg evra.',
 5,'Januar 2026.',1,40);

-- Per-destinacija recenzije (2 po destinaciji)
INSERT INTO `recenzije` (`destinacija_id`,`ime`,`tekst`,`ocena`,`datum_prikaza`,`tagovi`,`redosled`)
SELECT id,'Marko D.',
    'Savršeno organizovan put. Staze prelepe, hotel taman koliko nam treba. Ekipa Snowbase brza i profesionalna.',
    5,'Januar 2026.', JSON_ARRAY('Staze ★★★★★','Organizacija ★★★★★'), 10
FROM `destinacije`;

INSERT INTO `recenzije` (`destinacija_id`,`ime`,`tekst`,`ocena`,`datum_prikaza`,`tagovi`,`redosled`)
SELECT id,'Jelena & Vuk',
    'Treća sezona zaredom. Cena je fer, kvalitet izuzetan. Ski škola savršena za našu decu — preporučujemo svima.',
    5,'Februar 2026.', JSON_ARRAY('Porodično ★★★★★','Smestaj ★★★★☆'), 20
FROM `destinacije`;

-- ============================================================================
-- FAQ (globalna — destinacija_id NULL)
-- ============================================================================
INSERT INTO `faq` (`destinacija_id`,`pitanje`,`odgovor`,`redosled`) VALUES
(NULL,'Da li je ski pas uključen u cenu aranžmana?',
 'Ski pas nije automatski uključen u smeštajni aranžman — to nam omogućava da svaki paket prilagodimo vašim potrebama. Možete ga dokupiti kroz naš kalkulator na ovoj stranici, ili nas kontaktirati za paket deal (smeštaj + pas) koji je često povoljniji od pojedinačne kupovine.',10),
(NULL,'Kakvo zdravstveno osiguranje je potrebno za ski destinacije?',
 'Strogo preporučujemo putno osiguranje koje eksplicitno pokriva "zimske sportove i aktivnosti na snegu". Standardne turističke polise često ne pokrivaju skijaške povrede. Imamo dogovor sa partnerskim osiguravačem koji nudi specijalnu skijašku polisu od samo 8 EUR/dan po osobi.',20),
(NULL,'Šta se dešava sa ski pasom ako se planina zatvori zbog nevremena?',
 'Svaki skijaški centar iz našeg kataloga ima jasnu kompenzacionu politiku: za zatvaranje duže od 4 uzastopna sata vrši se proporcionalna nadoknada — ili produžetak pasa bez naknade, ili bon za narednu sezonu. Snowbase aktivno zastupa vaše interese.',30),
(NULL,'Kako rezervisati ski školu ili rentiranje opreme?',
 'Rezervacija se vrši minimum 48h pre željenog termina. Popunite kontakt obrazac na kraju stranice ili nas direktno kontaktirajte. Za grupe od 6 i više osoba odobravamo 15% popusta na kompletan paket opreme.',40);

-- ============================================================================
-- TICKER ITEMS (globalni)
-- ============================================================================
INSERT INTO `ticker_items` (`tekst`,`redosled`) VALUES
('Les Orres: 185 cm snega na vrhu · Prajder odličan',10),
('Batrovci (SRB/HRV): Zadržavanje ~25 min',20),
('Chamonix-Mont-Blanc: Sve žičare u pogonu · Vidljivost odlična',30),
('Simplon prevoj (CH): Obavezni lanci ili zimske gume',40),
('Val Thorens: 310 cm snega · Sezona traje do kraja aprila',50),
('Horgoš (SRB/HUN): Bez zadržavanja',60),
('Innsbruck / St. Anton: -6 C · Sunčano · Sve staze otvorene',70),
('Brenner autoput (AT): Zimska oprema obavezna iznad 700 m',80),
('Cortina d''Ampezzo: 165 cm sveže snežne podloge',90),
('Šid (SRB/HRV): Zadržavanje ~15 min',100),
('Sella Ronda (IT): 40 km runde · Perfektni uslovi',110),
('Zermatt: 280 cm snega na Matterhornskom platou',120),
('Kopaonik: -1 C · Sunčano · Domaća sezona u punom jeku',130),
('Bansko: 140 cm snega · Najpristupačnija opcija u regionu',140);

-- ============================================================================
-- KRAJ — Snowbase baza spremna
-- ============================================================================
