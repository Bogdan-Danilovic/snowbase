-- ============================================================================
-- Peak & Palm — Migracija hardkodovanih podataka u MySQL bazu
-- ----------------------------------------------------------------------------
-- Pokrenuti u phpMyAdmin nad bazom `peak_palm`.
-- Skripta je IDEMPOTENTNA: DROP IF EXISTS pre svakog CREATE-a, pa moze
-- da se izvrti ponovo bez problema (sve seed-podatke ce ponovo ubaciti).
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TICKER ITEMS — globalna obavestenja (jedina tabela bez veze na destinaciju)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ticker_items`;
CREATE TABLE `ticker_items` (
    `id`         INT NOT NULL AUTO_INCREMENT,
    `tekst`      VARCHAR(255) NOT NULL,
    `aktivan`    TINYINT(1)   NOT NULL DEFAULT 1,
    `redosled`   SMALLINT     NOT NULL DEFAULT 0,
    `kreiran_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aktivan_redosled` (`aktivan`, `redosled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. RECENZIJE — sa nullable destinacija_id
--    destinacija_id NULL + na_homepage=1  -> homepage carousel
--    destinacija_id NOT NULL              -> stranica skijalista
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `recenzije`;
CREATE TABLE `recenzije` (
    `id`             INT NOT NULL AUTO_INCREMENT,
    `destinacija_id` INT DEFAULT NULL,
    `ime`            VARCHAR(80)  NOT NULL,
    `avatar`         VARCHAR(8)   DEFAULT NULL,
    `lokacija`       VARCHAR(120) DEFAULT NULL,
    `tekst`          TEXT         NOT NULL,
    `ocena`          TINYINT      NOT NULL DEFAULT 5,
    `datum_prikaza`  VARCHAR(40)  NOT NULL,
    `tagovi`         JSON         DEFAULT NULL,
    `na_homepage`    TINYINT(1)   NOT NULL DEFAULT 0,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    `kreiran_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dest`     (`destinacija_id`),
    KEY `idx_homepage` (`na_homepage`, `redosled`),
    CONSTRAINT `fk_recenzije_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. FAQ — destinacija_id NULL = globalni (vidi se na svakoj destinaciji)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `faq`;
CREATE TABLE `faq` (
    `id`             INT NOT NULL AUTO_INCREMENT,
    `destinacija_id` INT DEFAULT NULL,
    `pitanje`        VARCHAR(255) NOT NULL,
    `odgovor`        TEXT         NOT NULL,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    `aktivan`        TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_dest_redosled` (`destinacija_id`, `redosled`),
    CONSTRAINT `fk_faq_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. SKI PAS CENE — per destinacija
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ski_pas_cene`;
CREATE TABLE `ski_pas_cene` (
    `id`             INT NOT NULL AUTO_INCREMENT,
    `destinacija_id` INT NOT NULL,
    `kategorija`     VARCHAR(40)  NOT NULL,
    `cena_1dan`      DECIMAL(7,2) NOT NULL,
    `cena_3dana`     DECIMAL(7,2) NOT NULL,
    `cena_6dana`     DECIMAL(7,2) NOT NULL,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_dest_kategorija` (`destinacija_id`, `kategorija`),
    CONSTRAINT `fk_ski_pas_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. VREME TRENUTNO — jedan red po destinaciji
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `vreme_trenutno`;
CREATE TABLE `vreme_trenutno` (
    `destinacija_id` INT NOT NULL,
    `temp_c`         TINYINT      NOT NULL,
    `temp_osecaj_c`  TINYINT      DEFAULT NULL,
    `sneg_dno_cm`    SMALLINT NOT NULL DEFAULT 0,
    `sneg_vrh_cm`    SMALLINT NOT NULL DEFAULT 0,
    `uslovi`         VARCHAR(60)  NOT NULL,
    `ikona`          VARCHAR(10)  NOT NULL DEFAULT '☀️',
    `vidljivost`     VARCHAR(60)  DEFAULT NULL,
    `azurirano_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`destinacija_id`),
    CONSTRAINT `fk_vreme_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. VREME PROGNOZA — 3-7 dana po destinaciji
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `vreme_prognoza`;
CREATE TABLE `vreme_prognoza` (
    `id`             INT NOT NULL AUTO_INCREMENT,
    `destinacija_id` INT NOT NULL,
    `dan_skraceno`   VARCHAR(8)   NOT NULL,
    `temp_min`       TINYINT      NOT NULL,
    `temp_max`       TINYINT      NOT NULL,
    `stanje`         VARCHAR(40)  NOT NULL,
    `ikona`          VARCHAR(10)  NOT NULL DEFAULT '☁️',
    `redosled`       TINYINT      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_dest_redosled` (`destinacija_id`, `redosled`),
    CONSTRAINT `fk_prognoza_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. STAZE STATUS — realtime stanje staza i zicara
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `staze_status`;
CREATE TABLE `staze_status` (
    `destinacija_id`  INT NOT NULL,
    `plave_otvorene`  SMALLINT NOT NULL DEFAULT 0,
    `plave_ukupno`    SMALLINT NOT NULL DEFAULT 0,
    `crvene_otvorene` SMALLINT NOT NULL DEFAULT 0,
    `crvene_ukupno`   SMALLINT NOT NULL DEFAULT 0,
    `crne_otvorene`   SMALLINT NOT NULL DEFAULT 0,
    `crne_ukupno`     SMALLINT NOT NULL DEFAULT 0,
    `zicara_aktivnih` SMALLINT NOT NULL DEFAULT 0,
    `zicara_ukupno`   SMALLINT NOT NULL DEFAULT 0,
    `azurirano_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`destinacija_id`),
    CONSTRAINT `fk_staze_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED PODACI
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Ticker items (globalno)
-- ----------------------------------------------------------------------------
INSERT INTO `ticker_items` (`tekst`, `redosled`) VALUES
('❄️ Les Orres: 120 cm snega na vrhu · Prajder: odličan',                 10),
('🚗 Batrovci (SRB/HRV): Zadržavanje ~30 min',                            20),
('⛷️ Chamonix-Mont-Blanc: Sve žičare u pogonu · Vidljivost odlična',      30),
('⚠️ Simplon prevoj (CH): Obavezni lanci ili zimske gume',                40),
('❄️ Val Thorens: 210 cm snega · Sezona traje do kraja aprila',           50),
('🚗 Horgoš (SRB/HUN): Bez zadržavanja',                                  60),
('🌤️ Innsbruck: −6°C · Sunčano · Sve staze otvorene',                    70),
('⚠️ Brenner autoput (AT): Zimska oprema obavezna iznad 700 m',           80),
('❄️ Cortina d''Ampezzo: 85 cm sveže snežne podloge',                     90),
('🚗 Šid (SRB/HRV): Zadržavanje ~15 min',                                100),
('⛷️ Sella Ronda (IT): 40 km runde · Perfektni uslovi',                  110),
('❄️ Zermatt: 300 cm snega na Matterhornskom platou',                    120);

-- ----------------------------------------------------------------------------
-- 2. Recenzije za HOMEPAGE (destinacija_id NULL, na_homepage = 1)
-- ----------------------------------------------------------------------------
INSERT INTO `recenzije`
    (`destinacija_id`, `ime`, `avatar`, `lokacija`, `tekst`, `ocena`, `datum_prikaza`, `na_homepage`, `redosled`) VALUES
(NULL, 'Marija T.',   'MT', 'Les Orres, Francuska',
 'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg prašinast celu nedelju. Organizacija Peak & Palm bila je besprekorna od prvog do poslednjeg dana.',
 5, 'Januar 2025.', 1, 10),
(NULL, 'Stefan K.',   'SK', 'Innsbruck, Austrija',
 'Treće godišnje putovanje sa ovom agencijom. Smeštaj tačno prema opisu, transfer sa aerodroma brz i bez čekanja. Jednom kad probate, ne idete drugde.',
 5, 'Februar 2025.', 1, 20),
(NULL, 'Ana & Bojan', 'AB', 'Cortina d''Ampezzo, Italija',
 'Odlično za porodice s decom. Ski škola za početnike bila je strpljiva i profesionalna. Noćni život iznad svih očekivanja — pravo iznenađenje!',
 5, 'Decembar 2024.', 1, 30),
(NULL, 'Nikola P.',   'NP', 'Zermatt, Švajcarska',
 'Sve je bilo savršeno isplanirano. Od polaska iz Beograda do povratka — nula stresa. Kalkulator na sajtu je bio tačan do poslednjeg evra. Hvala ekipi!',
 5, 'Januar 2025.', 1, 40);

-- ----------------------------------------------------------------------------
-- 3. Recenzije za SVAKU DESTINACIJU (per-destination, isti seed za sve)
--    Koristi se INSERT ... SELECT FROM destinacije da se automatski kreiraju
--    redovi za sve postojeće destinacije.
-- ----------------------------------------------------------------------------
INSERT INTO `recenzije` (`destinacija_id`, `ime`, `tekst`, `ocena`, `datum_prikaza`, `tagovi`, `redosled`)
SELECT id, 'Marija T.',
       'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg je bio prašinast celu nedelju. Organizacija Peak & Palm bila je besprekorna od prvog do poslednjeg dana.',
       5, 'Januar 2025.',
       JSON_ARRAY('Staze ★★★★★', 'Organizacija ★★★★★'),
       10
FROM `destinacije`;

INSERT INTO `recenzije` (`destinacija_id`, `ime`, `tekst`, `ocena`, `datum_prikaza`, `tagovi`, `redosled`)
SELECT id, 'Stefan K.',
       'Treće godišnje putovanje sa ovom agencijom. Smeštaj tačno prema opisu, transfer sa aerodroma bio brz i bez čekanja. Preporučujem svakome.',
       5, 'Februar 2025.',
       JSON_ARRAY('Smeštaj ★★★★☆', 'Transfer ★★★★★'),
       20
FROM `destinacije`;

INSERT INTO `recenzije` (`destinacija_id`, `ime`, `tekst`, `ocena`, `datum_prikaza`, `tagovi`, `redosled`)
SELECT id, 'Ana & Bojan',
       'Odlično za porodice s decom. Ski škola za početnike bila je strpljiva i profesionalna. Noćni život iznad svih očekivanja — pravo iznenađenje!',
       4, 'Decembar 2024.',
       JSON_ARRAY('Porodično ★★★★★', 'Noćni život ★★★★☆'),
       30
FROM `destinacije`;

-- ----------------------------------------------------------------------------
-- 4. FAQ — globalna pitanja (destinacija_id = NULL)
-- ----------------------------------------------------------------------------
INSERT INTO `faq` (`destinacija_id`, `pitanje`, `odgovor`, `redosled`) VALUES
(NULL,
 'Da li je ski pas uključen u cenu aranžmana?',
 'Ski pas nije automatski uključen u smeštajni aranžman — to nam omogućava da svaki paket prilagodimo vašim potrebama. Možete ga dokupiti kroz naš kalkulator na ovoj stranici, ili nas kontaktirati za paket deal (smeštaj + pas) koji je često povoljniji od pojedinačne kupovine.',
 10),
(NULL,
 'Kakvo zdravstveno osiguranje je potrebno za ski destinacije?',
 'Strogo preporučujemo putno osiguranje koje eksplicitno pokriva "zimske sportove i aktivnosti na snegu". Standardne turistički polise često ne pokrivaju skijaške povrede. Imamo dogovor sa partnerskim osiguravačem koji nudi specijalnu skijašku polisu od samo €8/dan po osobi — pitajte naše agente za detalje.',
 20),
(NULL,
 'Šta se dešava sa ski pasom ako se planina zatvori zbog nevremena?',
 'Svaki skijaški centar iz našeg kataloga ima jasnu kompenzacionu politiku: za zatvaranje duže od 4 uzastopna sata vrši se proporcionalna nadoknada — ili produžetak pasa bez naknade, ili bon za narednu sezonu. Peak & Palm aktivno zastupa vaše interese u takvim situacijama.',
 30),
(NULL,
 'Kako rezervisati ski školu ili rentiranje opreme?',
 'Rezervacija se vrši minimum 48h pre željenog termina. Popunite kontakt obrazac na kraju stranice ili nas direktno kontaktirajte. Za grupe od 6 i više osoba odobravamo 15% popusta na kompletan paket opreme.',
 40);

-- ----------------------------------------------------------------------------
-- 5. Ski pas cene — per destinacija (isti seed za sve)
-- ----------------------------------------------------------------------------
INSERT INTO `ski_pas_cene` (`destinacija_id`, `kategorija`, `cena_1dan`, `cena_3dana`, `cena_6dana`, `redosled`)
SELECT id, 'Odrasli',  42, 115, 195, 10 FROM `destinacije`
UNION ALL
SELECT id, 'Studenti', 35,  95, 162, 20 FROM `destinacije`
UNION ALL
SELECT id, 'Deca',     25,  68, 112, 30 FROM `destinacije`
UNION ALL
SELECT id, 'Senior',   32,  88, 148, 40 FROM `destinacije`;

-- ----------------------------------------------------------------------------
-- 6. Vreme trenutno — per destinacija (isti seed za sve)
-- ----------------------------------------------------------------------------
INSERT INTO `vreme_trenutno`
    (`destinacija_id`, `temp_c`, `temp_osecaj_c`, `sneg_dno_cm`, `sneg_vrh_cm`, `uslovi`, `ikona`, `vidljivost`)
SELECT id, -3, -8, 45, 185, 'Sunčano', '☀️', 'Odlična (>10 km)'
FROM `destinacije`;

-- ----------------------------------------------------------------------------
-- 7. Vreme prognoza — 3 dana per destinacija
-- ----------------------------------------------------------------------------
INSERT INTO `vreme_prognoza` (`destinacija_id`, `dan_skraceno`, `temp_min`, `temp_max`, `stanje`, `ikona`, `redosled`)
SELECT id, 'PON',  -8, -4, 'Oblačno', '☁️', 1 FROM `destinacije`
UNION ALL
SELECT id, 'UTO', -12, -7, 'Sneg',    '❄️', 2 FROM `destinacije`
UNION ALL
SELECT id, 'SRE',  -5, -1, 'Sunčano', '🌤️', 3 FROM `destinacije`;

-- ----------------------------------------------------------------------------
-- 8. Staze status — per destinacija
-- ----------------------------------------------------------------------------
INSERT INTO `staze_status`
    (`destinacija_id`, `plave_otvorene`, `plave_ukupno`, `crvene_otvorene`, `crvene_ukupno`,
     `crne_otvorene`, `crne_ukupno`, `zicara_aktivnih`, `zicara_ukupno`)
SELECT id, 12, 15, 8, 10, 2, 4, 8, 10
FROM `destinacije`;

-- ============================================================================
-- KRAJ MIGRACIJE
-- ============================================================================
