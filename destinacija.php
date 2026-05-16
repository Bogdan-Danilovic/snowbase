<?php
require_once 'db.php';

/* ---------------------------------------------------------------
   1. UCITAVANJE DESTINACIJE IZ BAZE
   --------------------------------------------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
    $stmt = $pdo->prepare("
        SELECT d.*,
               s.ukupno_staza_km, s.plave_staze_km, s.crvene_staze_km, s.crne_staze_km,
               s.broj_zicara
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $dest = $stmt->fetch();

    if (!$dest) { die("Destinacija nije pronadjena."); }

    $stmtSmestaj = $pdo->prepare("SELECT * FROM smestaj WHERE destinacija_id = ?");
    $stmtSmestaj->execute([$id]);
    $hoteli = $stmtSmestaj->fetchAll();

} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/* ---------------------------------------------------------------
   2. PODACI O DESTINACIJI IZ BAZE
   Svaki upit je u zasebnom try/catch bloku tako da, ako jos
   nisi pokrenuo migracija.sql, stranica nece pasti — odredjena
   sekcija ce samo biti prazna dok ne pokrenes migraciju.
   --------------------------------------------------------------- */

/* --- 2.1 Vremenska prognoza --- */
$vremeData = [
    'temp'        => 0,
    'temp_osecaj' => 0,
    'sneg_dno_cm' => 0,
    'sneg_vrh_cm' => 0,
    'uslovi'      => '—',
    'ikona'       => '☀️',
    'vidljivost'  => '—',
    'prognoza'    => [],
];

try {
    $stmt = $pdo->prepare("SELECT * FROM vreme_trenutno WHERE destinacija_id = ?");
    $stmt->execute([$id]);
    $vt = $stmt->fetch();

    if ($vt) {
        $vremeData['temp']        = (int)$vt['temp_c'];
        $vremeData['temp_osecaj'] = (int)$vt['temp_osecaj_c'];
        $vremeData['sneg_dno_cm'] = (int)$vt['sneg_dno_cm'];
        $vremeData['sneg_vrh_cm'] = (int)$vt['sneg_vrh_cm'];
        $vremeData['uslovi']      = $vt['uslovi'];
        $vremeData['ikona']       = $vt['ikona'];
        $vremeData['vidljivost']  = $vt['vidljivost'];
    }

    $stmt = $pdo->prepare("
        SELECT dan_skraceno AS dan, temp_min, temp_max, stanje, ikona
        FROM vreme_prognoza
        WHERE destinacija_id = ?
        ORDER BY redosled, id
        LIMIT 7
    ");
    $stmt->execute([$id]);
    $vremeData['prognoza'] = $stmt->fetchAll();
} catch (PDOException $e) {
    /* tabele vreme_* jos ne postoje */
}

/* --- 2.2 Staze status (realtime) --- */
$staze_status = [
    'plave'           => ['otvorene' => 0, 'ukupno' => 0],
    'crvene'          => ['otvorene' => 0, 'ukupno' => 0],
    'crne'            => ['otvorene' => 0, 'ukupno' => 0],
    'zicara_aktivnih' => 0,
    'zicara_ukupno'   => (int)($dest['broj_zicara'] ?? 0),
];

try {
    $stmt = $pdo->prepare("SELECT * FROM staze_status WHERE destinacija_id = ?");
    $stmt->execute([$id]);
    $ss = $stmt->fetch();
    if ($ss) {
        $staze_status = [
            'plave'           => ['otvorene' => (int)$ss['plave_otvorene'],  'ukupno' => (int)$ss['plave_ukupno']],
            'crvene'          => ['otvorene' => (int)$ss['crvene_otvorene'], 'ukupno' => (int)$ss['crvene_ukupno']],
            'crne'            => ['otvorene' => (int)$ss['crne_otvorene'],   'ukupno' => (int)$ss['crne_ukupno']],
            'zicara_aktivnih' => (int)$ss['zicara_aktivnih'],
            'zicara_ukupno'   => (int)$ss['zicara_ukupno'],
        ];
    }
} catch (PDOException $e) {
    /* staze_status tabela jos ne postoji */
}

/* --- 2.3 Cene ski pasa --- */
$ski_pas_cene = [];
try {
    $stmt = $pdo->prepare("
        SELECT kategorija,
               cena_1dan  AS dan1,
               cena_3dana AS dan3,
               cena_6dana AS dan6
        FROM ski_pas_cene
        WHERE destinacija_id = ?
        ORDER BY redosled, id
    ");
    $stmt->execute([$id]);
    $ski_pas_cene = $stmt->fetchAll();
} catch (PDOException $e) {
    /* ski_pas_cene tabela jos ne postoji */
}
/* Fallback samo da JS kalkulator ne pukne ako nema ni jedne kategorije */
if (empty($ski_pas_cene)) {
    $ski_pas_cene = [
        ['kategorija' => 'Odrasli', 'dan1' => 0, 'dan3' => 0, 'dan6' => 0],
    ];
}

/* --- 2.4 Recenzije po destinaciji --- */
$recenzije = [];
try {
    $stmt = $pdo->prepare("
        SELECT ime, ocena, datum_prikaza AS datum, tekst, tagovi
        FROM recenzije
        WHERE destinacija_id = ?
        ORDER BY redosled, id
        LIMIT 6
    ");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $row) {
        /* tagovi su sacuvani kao JSON niz u bazi */
        $row['tagovi'] = $row['tagovi'] ? (json_decode($row['tagovi'], true) ?: []) : [];
        $recenzije[]   = $row;
    }
} catch (PDOException $e) {
    /* recenzije tabela jos ne postoji */
}

/* --- 2.5 FAQ (globalni + per-destinacija) --- */
$faq = [];
try {
    $stmt = $pdo->prepare("
        SELECT pitanje, odgovor
        FROM faq
        WHERE aktivan = 1
          AND (destinacija_id = ? OR destinacija_id IS NULL)
        ORDER BY destinacija_id IS NULL, redosled, id
    ");
    $stmt->execute([$id]);
    $faq = $stmt->fetchAll();
} catch (PDOException $e) {
    /* faq tabela jos ne postoji */
}

/* ---------------------------------------------------------------
   3. KONFIGURACIJA STRANICE
   --------------------------------------------------------------- */
$page_title = htmlspecialchars($dest['naziv']) . ' | Peak and Palm';
$nav_links  = [
    ['href' => 'index.php',   'label' => '← Katalog'],
    ['href' => '#ski-info',   'label' => 'Ski Info'],
    ['href' => '#smestaj',    'label' => 'Smeštaj'],
    ['href' => '#logistika',  'label' => 'Logistika'],
    ['href' => '#ski-pas',    'label' => 'Ski Pas'],
];

include 'partials/head.php';
?>
<body>

<!-- Custom cursor -->
<div class="cursor-dot" id="cursorDot"></div>
<div class="cursor-ring" id="cursorRing"></div>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<div class="main-content">

    <!-- ================================================================
         HERO — Fixed, fades out on scroll
         ================================================================ -->
    <section class="hero-section" id="hero">

        <div class="prava-mapa-container">
            <svg viewBox="0 0 640 346" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg">
                <image href="Slike/les_orres_mapa.jpg" width="640" height="346" />
                <g>
                    <path id="path-blue"  class="staza plava"  d="M 200 150 Q 300 220 400 300" />
                    <path id="path-red"   class="staza crvena" d="M306.5 123.5C295.3 123.9 292.167 118.667 292 116C295.5 110.5 294.5 111 289.5 103.5C297.1 100.7 303.5 97 305.5 90.5C330.3 92.5 330 90.5 330 84C340.5 74.5 341 74.5 333.5 68.5C335.9 60.1 327.833 58 324 58C319.5 58 317.403 63.6563 312 66.5C302.5 71.5 299.5 74 290.5 76C289.5 79 281.6 84.2 276 85C274 86.6667 268 92.5 268.5 101C265.5 104.5 262 104.7 256 107.5" />
                    <path id="path-black" class="staza crna"   d="M 320 80 L 310 180 L 290 290" />
                </g>
            </svg>
        </div>

        <div class="info-side">
            <span class="hero-label">Katalog Destinacija</span>
            <h1 class="hero-title"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($dest['opis']); ?></p>

            <div class="interactive-list">
                <div class="slope-item" data-path="path-blue">
                    <div><span class="slope-name">Plave staze</span></div>
                    <strong class="slope-km blue"><?php echo (float)($dest['plave_staze_km'] ?? 0); ?> km</strong>
                </div>
                <div class="slope-item" data-path="path-red">
                    <div><span class="slope-name">Crvene staze</span></div>
                    <strong class="slope-km red"><?php echo (float)($dest['crvene_staze_km'] ?? 0); ?> km</strong>
                </div>
                <div class="slope-item" data-path="path-black">
                    <div><span class="slope-name">Crne staze</span></div>
                    <strong class="slope-km black"><?php echo (float)($dest['crne_staze_km'] ?? 0); ?> km</strong>
                </div>
            </div>
        </div>

    </section><!-- /hero -->

    <!-- ================================================================
         SCROLL CONTENT
         ================================================================ -->
    <section class="scroll-content">
        <div class="container-wide">

            <!-- ============================================================
                 I. SKI & INFO CENTAR
                 ============================================================ -->
            <div class="reveal section-block" id="ski-info">
                <span class="section-eyebrow">Realtime</span>
                <h2 class="section-title">Ski <span>&amp; Info</span> Centar</h2>
                <div class="section-divider"></div>

                <div class="ski-info-grid">

                    <!-- Weather widget -->
                    <div class="weather-panel reveal">
                        <div class="weather-top">
                            <div class="weather-temp-display">
                                <span class="weather-temp-num"><?php echo (int)$vremeData['temp']; ?></span>
                                <span class="weather-temp-unit">°C</span>
                            </div>
                            <div class="weather-conditions">
                                <span class="weather-cond-label">Stanje</span>
                                <span class="weather-cond-val"><?php echo htmlspecialchars($vremeData['uslovi']); ?></span>
                                <span class="weather-cond-icon"><?php echo $vremeData['ikona']; ?></span>
                            </div>
                        </div>

                        <div class="snow-levels">
                            <div class="snow-level-item">
                                <label>Sneg — dno</label>
                                <div>
                                    <span class="snow-level-val"><?php echo (int)$vremeData['sneg_dno_cm']; ?></span>
                                    <span class="snow-level-unit">cm</span>
                                </div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vremeData['sneg_dno_cm'] / 2); ?>%"></div>
                                </div>
                            </div>
                            <div class="snow-level-item">
                                <label>Sneg — vrh</label>
                                <div>
                                    <span class="snow-level-val"><?php echo (int)$vremeData['sneg_vrh_cm']; ?></span>
                                    <span class="snow-level-unit">cm</span>
                                </div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vremeData['sneg_vrh_cm'] / 3); ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="forecast-strip">
                            <?php foreach ($vremeData['prognoza'] as $dan): ?>
                            <div class="forecast-day">
                                <span class="forecast-day-name"><?php echo htmlspecialchars($dan['dan']); ?></span>
                                <span class="forecast-icon"><?php echo $dan['ikona']; ?></span>
                                <span class="forecast-temp">
                                    <?php echo (int)$dan['temp_max']; ?>°
                                    <span>/ <?php echo (int)$dan['temp_min']; ?>°</span>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Live Staze Status -->
                    <div class="live-status-panel reveal">
                        <span class="live-badge">Live Status</span>

                        <?php
                        $staze_config = [
                            'plave'  => ['label' => 'Plave staze',  'color' => '#38bdf8'],
                            'crvene' => ['label' => 'Crvene staze', 'color' => '#ef4444'],
                            'crne'   => ['label' => 'Crne staze',   'color' => '#b8c8da'],
                        ];
                        foreach ($staze_config as $key => $cfg):
                            $ot  = (int)$staze_status[$key]['otvorene'];
                            $uk  = (int)$staze_status[$key]['ukupno'];
                            $pct = $uk > 0 ? round(($ot / $uk) * 100) : 0;
                        ?>
                        <div class="staze-bar-row">
                            <div class="staze-bar-header">
                                <span class="staze-type-label">
                                    <span class="staze-dot" style="background:<?php echo $cfg['color']; ?>; box-shadow:0 0 6px <?php echo $cfg['color']; ?>;"></span>
                                    <?php echo $cfg['label']; ?>
                                </span>
                                <span class="staze-count">
                                    <strong><?php echo $ot; ?></strong> / <?php echo $uk; ?> otvoreno
                                </span>
                            </div>
                            <div class="staze-progress-track">
                                <div class="staze-progress-fill"
                                     style="width:<?php echo $pct; ?>%; background: linear-gradient(90deg, <?php echo $cfg['color']; ?>66, <?php echo $cfg['color']; ?>);">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="zicara-row">
                            <span class="zicara-label">🚡 Žičare i gondole</span>
                            <span class="zicara-status">
                                <?php echo (int)$staze_status['zicara_aktivnih']; ?> / <?php echo (int)$staze_status['zicara_ukupno']; ?> aktivnih
                            </span>
                        </div>

                        <div class="vidljivost-box">
                            <p class="vidljivost-label">Vidljivost</p>
                            <span class="vidljivost-val"><?php echo htmlspecialchars($vremeData['vidljivost']); ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 II. SMEŠTAJ
                 ============================================================ -->
            <div class="reveal section-block" id="smestaj">
                <span class="section-eyebrow">Partnerski objekti</span>
                <h2 class="section-title">Raspoloživ <span>Smeštaj</span></h2>
                <div class="section-divider"></div>

                <div class="hotel-grid">
                    <?php foreach ($hoteli as $h): ?>
                    <div class="hotel-card reveal">
                        <div class="hotel-img-wrap">
                            <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop"
                                 class="hotel-img"
                                 alt="<?php echo htmlspecialchars($h['naziv']); ?>"
                                 width="800" height="500"
                                 loading="lazy" decoding="async">
                        </div>
                        <div class="hotel-body">
                            <div class="hotel-stars">
                                <?php for ($s = 0; $s < (int)$h['zvezdice']; $s++): ?>
                                    <span>★</span>
                                <?php endfor; ?>
                            </div>
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p class="hotel-meta">
                                Kategorija <?php echo (int)$h['zvezdice']; ?>★ · Kapacitet <?php echo (int)$h['kapacitet_osoba']; ?> osoba
                            </p>
                            <div class="hotel-price">
                                €<?php echo number_format($h['cena_po_noci_eur'], 0); ?>
                                <small>/ noć / osoba</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ============================================================
                 III. OPCIJE PREVOZA
                 ============================================================ -->
            <div class="reveal section-block" id="prevoz">
                <span class="section-eyebrow">Iz Beograda do staze</span>
                <h2 class="section-title">Opcije <span>Prevoza</span></h2>
                <div class="section-divider"></div>

                <div class="transport-grid">

                    <div class="transport-card bus reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <div class="transport-icon-wrap">🚌</div>
                            <h3 class="transport-title">Agencijski Autobus</h3>
                            <p class="transport-subtitle">Direktna linija</p>
                            <ul class="transport-info-list">
                                <li><span>Polazak</span> <strong>Sava Centar, 22:00h</strong></li>
                                <li><span>Trajanje</span> <strong>~<?php echo (int)round($dest['distanca_od_bg_km'] / 80); ?>h vožnje</strong></li>
                                <li><span>Povratak</span> <strong>Nedeljom, 14:00h</strong></li>
                                <li><span>Prtljag</span> <strong>Kofer + ski torba</strong></li>
                                <li><span>Cena prevoza</span> <strong>€<?php echo (int)round($dest['distanca_od_bg_km'] * 0.06); ?> / osobi</strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="transport-card avion reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <div class="transport-icon-wrap">✈️</div>
                            <h3 class="transport-title">Avion + Transfer</h3>
                            <p class="transport-subtitle">Najbrža opcija</p>
                            <ul class="transport-info-list">
                                <li><span>Aerodrom</span> <strong>BEG → Najbliži</strong></li>
                                <li><span>Let</span> <strong>~1h 45min</strong></li>
                                <li><span>Transfer</span> <strong>Aerodrom → Hotel</strong></li>
                                <li><span>Trajanje transfera</span> <strong>~45 min</strong></li>
                                <li><span>Šatl cena</span> <strong>€28 / osobi</strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="transport-card auto reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <div class="transport-icon-wrap">🚗</div>
                            <h3 class="transport-title">Sopstveni Auto</h3>
                            <p class="transport-subtitle"><?php echo (int)$dest['distanca_od_bg_km']; ?> km od BG</p>
                            <ul class="transport-info-list">
                                <li><span>Putarina</span> <strong>€<?php echo (float)$dest['prosecna_putarina_eur']; ?> povratno</strong></li>
                                <li><span>Zimska oprema</span> <strong>Obavezna</strong></li>
                                <li><span>Granični prelaz</span> <strong>Horgos / Batrovci</strong></li>
                            </ul>
                            <div class="route-waypoints">
                                <div class="wp"><span class="wp-dot"></span><span>📍 Beograd (A1 autoput)</span></div>
                                <div class="wp"><span class="wp-dot"></span><span>🛂 Granični prelaz</span></div>
                                <div class="wp"><span class="wp-dot"></span><span>🛣️ Preporučena ruta uz planinu</span></div>
                                <div class="wp">
                                    <span class="wp-dot final"></span>
                                    <strong class="wp-final">🏔️ <?php echo htmlspecialchars($dest['naziv']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 IV. KALKULATOR LOGISTIKE
                 ============================================================ -->
            <div class="reveal logistika-panel" id="logistika">
                <div>
                    <span class="section-eyebrow">Sopstveni prevoz</span>
                    <h2 class="section-title section-title-sm">Kalkulator <span>Logistike</span></h2>
                    <p class="logistika-intro">
                        Unesite parametre vašeg vozila za proračun troškova <br>povratnog puta iz Beograda.
                    </p>
                    <div class="input-group">
                        <label for="in-putnici">Broj putnika</label>
                        <input type="number" id="in-putnici" value="4" min="1" max="9">
                    </div>
                    <div class="input-group">
                        <label for="in-potrosnja">Potrošnja goriva (L/100km)</label>
                        <input type="number" id="in-potrosnja" value="7.0" step="0.1">
                    </div>
                    <div class="input-group">
                        <label for="in-cena-goriva">Cena goriva (€/L)</label>
                        <input type="number" id="in-cena-goriva" value="1.65" step="0.05">
                    </div>
                    <button class="btn-calc" id="btn-trosak">↗ Izračunaj trošak</button>
                </div>

                <div>
                    <div class="stat-item">
                        <p>Udaljenost od Beograda</p>
                        <strong><?php echo (int)$dest['distanca_od_bg_km']; ?> km</strong>
                    </div>
                    <div class="stat-item">
                        <p>Prosečna putarina (tur-retur)</p>
                        <strong>€<?php echo (float)$dest['prosecna_putarina_eur'] * 2; ?></strong>
                    </div>
                    <div class="stat-item">
                        <p>Ukupna distanca (povratak)</p>
                        <strong><?php echo (int)$dest['distanca_od_bg_km'] * 2; ?> km</strong>
                    </div>
                    <div class="result-box logistika-result">
                        <p>Cena povratnog puta po osobi:</p>
                        <strong id="rezultat-cena">€--.--</strong>
                        <p id="rezultat-gorivo" class="rezultat-gorivo"></p>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 V. SKI PAS
                 ============================================================ -->
            <div class="reveal section-block section-block-top" id="ski-pas">
                <span class="section-eyebrow">Transparentne cene</span>
                <h2 class="section-title">Cene <span>Ski Pasa</span></h2>
                <div class="section-divider"></div>

                <div class="ski-pas-layout">

                    <div class="pas-table-wrap reveal">
                        <table class="pas-table">
                            <thead>
                                <tr>
                                    <th>Kategorija</th>
                                    <th>1 dan</th>
                                    <th class="best">3 dana</th>
                                    <th>6 dana</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ski_pas_cene as $red): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($red['kategorija']); ?></td>
                                    <td>€<?php echo (int)$red['dan1']; ?></td>
                                    <td class="best-col">€<?php echo (int)$red['dan3']; ?></td>
                                    <td>€<?php echo (int)$red['dan6']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pas-calc reveal">
                        <h3 class="pas-calc-title">Izračunaj <span>ukupno</span></h3>

                        <p class="pas-control-label">Kategorija</p>
                        <div class="segment-control" id="kategorijaControl">
                            <?php foreach ($ski_pas_cene as $i => $red): ?>
                            <button class="segment-btn<?php echo $i === 0 ? ' active' : ''; ?>"
                                    data-kategorija="<?php echo htmlspecialchars($red['kategorija']); ?>"
                                    data-dan1="<?php echo (int)$red['dan1']; ?>"
                                    data-dan3="<?php echo (int)$red['dan3']; ?>"
                                    data-dan6="<?php echo (int)$red['dan6']; ?>">
                                <?php echo htmlspecialchars($red['kategorija']); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <p class="pas-control-label">Broj dana</p>
                        <div class="days-selector" id="daysControl">
                            <button class="day-btn active" data-dani="1">1 dan</button>
                            <button class="day-btn"        data-dani="3">3 dana</button>
                            <button class="day-btn"        data-dani="6">6 dana</button>
                        </div>

                        <div class="input-group pas-osobe-group">
                            <label for="pas-osobe">Broj osoba</label>
                            <input type="number" id="pas-osobe" value="1" min="1" max="20">
                        </div>

                        <div class="pas-result-box">
                            <span class="pas-result-label">Ukupno za ski pas</span>
                            <div class="pas-result-price" id="pasRezultat">€<?php echo (int)$ski_pas_cene[0]['dan1']; ?></div>
                            <p class="pas-result-sub" id="pasSub">1 odrasli · 1 dan</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 VI. RENTIRANJE OPREME & SKI ŠKOLA
                 ============================================================ -->
            <div class="reveal section-block" id="oprema">
                <span class="section-eyebrow">Agencijski paketi</span>
                <h2 class="section-title">Oprema <span>&amp; Škola</span></h2>
                <div class="section-divider"></div>

                <div class="equipment-section-grid">

                    <div>
                        <p class="equipment-intro">
                            Rentiranje kompletne opreme direktno kroz agenciju — bez čekanja u redu na skijalištima.
                        </p>
                        <div class="equipment-cards">

                            <div class="equipment-card ekonomicni reveal">
                                <span class="equipment-badge">Ekonomični</span>
                                <h3 class="equipment-name">Starter Komplet</h3>
                                <p class="equipment-desc">Idealno za početnike i rekreativce. Proverena oprema renomirane klase.</p>
                                <ul class="equipment-includes">
                                    <li>Skije (all-mountain, početni nivo)</li>
                                    <li>Pancerice (toplinski podstavljene)</li>
                                    <li>Štapovi + kaiš za zapešće</li>
                                    <li>Kaciga (EN 1077 certifikat)</li>
                                </ul>
                                <div class="equipment-price-row">
                                    <div>
                                        <div class="equipment-price">€22</div>
                                        <div class="equipment-period">po danu / po osobi</div>
                                    </div>
                                    <div class="equipment-note">Min. 2 dana</div>
                                </div>
                            </div>

                            <div class="equipment-card premium reveal">
                                <span class="equipment-badge">Premium</span>
                                <h3 class="equipment-name">Expert Performance</h3>
                                <p class="equipment-desc">Napredni modeli skija za iskusne skijaše koji traže preciznost i kontrolu na svakom terenu.</p>
                                <ul class="equipment-includes">
                                    <li>Race/Freeride skije (napredni modeli)</li>
                                    <li>Pancerice (race-fit, carbon vložak)</li>
                                    <li>Štapovi od karbona</li>
                                    <li>Kaciga + zaštitne naočare</li>
                                    <li>Zaštitni šorts i back protektor</li>
                                </ul>
                                <div class="equipment-price-row">
                                    <div>
                                        <div class="equipment-price">€38</div>
                                        <div class="equipment-period">po danu / po osobi</div>
                                    </div>
                                    <div class="equipment-note recommend">Preporučujemo</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="ski-school-panel reveal">
                        <span class="section-eyebrow eyebrow-tight">Škola skijanja</span>
                        <h3 class="ski-school-title">Čas sa <span>instruktorom</span></h3>
                        <p class="ski-school-intro">
                            Licencirani instruktori skijanja i snowboard-a. Rezervacija min. 48h unapred.
                        </p>

                        <div class="school-packages">
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Grupni čas (do 6 osoba)</div>
                                    <div class="school-package-desc">2h · Svi nivoi · Srpski / Engleski</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€18</strong><span>/ osobi</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Individualni čas</div>
                                    <div class="school-package-desc">2h · Personalizovani program</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€65</strong><span>/ čas</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">5-dnevni grupni kurs</div>
                                    <div class="school-package-desc">2h dnevno · Sve uzraste · Sertifikat</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€72</strong><span>/ osobi</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Snowboard starter</div>
                                    <div class="school-package-desc">3h · Početnici · Oprema uključena</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€48</strong><span>/ osobi</span>
                                </div>
                            </div>
                        </div>

                        <div class="school-note">
                            🎯 Za grupe 6+ osoba odobravam 15% popusta na sve pakete opreme i školu skijanja.
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 VII. RECENZIJE
                 ============================================================ -->
            <div class="reveal section-block" id="utisci">
                <span class="section-eyebrow">Verifikovani putnici</span>
                <h2 class="section-title">Iskustva <span>Putnika</span></h2>
                <div class="section-divider"></div>

                <div class="reviews-grid">
                    <?php foreach ($recenzije as $rec): ?>
                    <div class="review-card reveal">
                        <div class="review-stars">
                            <?php for ($s = 0; $s < (int)$rec['ocena']; $s++): ?>
                                <span class="star">★</span>
                            <?php endfor; ?>
                            <?php for ($s = (int)$rec['ocena']; $s < 5; $s++): ?>
                                <span class="star dim">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-tags">
                            <?php foreach ($rec['tagovi'] as $tag): ?>
                            <span class="review-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($rec['tekst']); ?></p>
                        <div class="review-footer">
                            <div>
                                <div class="review-author"><?php echo htmlspecialchars($rec['ime']); ?></div>
                                <div class="review-date"><?php echo htmlspecialchars($rec['datum']); ?></div>
                            </div>
                            <div class="review-check">✓</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ============================================================
                 VIII. FAQ
                 ============================================================ -->
            <div class="reveal section-block" id="faq">
                <span class="section-eyebrow">Sve što trebate znati</span>
                <h2 class="section-title">Česta <span>Pitanja</span></h2>
                <div class="section-divider"></div>

                <div class="faq-list">
                    <?php foreach ($faq as $i => $q): ?>
                    <div class="faq-item" id="faq-<?php echo (int)$i; ?>">
                        <button class="faq-question" type="button" data-faq-index="<?php echo (int)$i; ?>">
                            <span><?php echo htmlspecialchars($q['pitanje']); ?></span>
                            <span class="faq-chevron">
                                <svg viewBox="0 0 12 12" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="2,4 6,8 10,4"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                <?php echo htmlspecialchars($q['odgovor']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>

</div>

<!-- ====================================================================
     JAVASCRIPT
     ==================================================================== -->
<script>
/* ---- Custom Cursor ---- */
const dot  = document.getElementById('cursorDot');
const ring = document.getElementById('cursorRing');
let ringX = 0, ringY = 0, dotX = 0, dotY = 0;
let raf = null;

document.addEventListener('mousemove', e => {
    dotX = e.clientX;
    dotY = e.clientY;
    dot.style.left = dotX + 'px';
    dot.style.top  = dotY + 'px';
    if (!raf) raf = requestAnimationFrame(animateRing);
});

function animateRing() {
    ringX += (dotX - ringX) * 0.14;
    ringY += (dotY - ringY) * 0.14;
    ring.style.left = ringX + 'px';
    ring.style.top  = ringY + 'px';
    raf = requestAnimationFrame(animateRing);
}

const hoverSelector = 'a, button, .slope-item, .faq-question, .hotel-card, .review-card, .transport-card, .day-btn, .segment-btn';
document.querySelectorAll(hoverSelector).forEach(el => {
    el.addEventListener('mouseenter', () => ring.classList.add('hovering'));
    el.addEventListener('mouseleave', () => ring.classList.remove('hovering'));
});

/* ---- Nav mouse glow ---- */
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('mousemove', e => {
        const r = link.getBoundingClientRect();
        link.style.setProperty('--mouse-x', ((e.clientX - r.left) / r.width  * 100) + '%');
        link.style.setProperty('--mouse-y', ((e.clientY - r.top)  / r.height * 100) + '%');
    });
});

/* ---- SVG Mapa toggle (event delegation umesto inline onmouseenter) ---- */
document.querySelectorAll('.slope-item').forEach(item => {
    const path = document.getElementById(item.dataset.path);
    if (!path) return;
    item.addEventListener('mouseenter', () => path.classList.add('active'));
    item.addEventListener('mouseleave', () => path.classList.remove('active'));
});

/* ---- Hero fade na scroll (lightweight, koristi rAF throttling) ---- */
const hero    = document.getElementById('hero');
const nav     = document.getElementById('main-nav');
let scrollTicking = false;

window.addEventListener('scroll', () => {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
        const y = window.scrollY;
        nav?.classList.toggle('scrolled',  y > 40);
        hero?.classList.toggle('scrolled', y > 80);
        scrollTicking = false;
    });
}, { passive: true });

/* ---- Reveal animacije (IntersectionObserver, ne scroll handler) ---- */
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

/* ---- Kalkulator Logistike ---- */
const DISTANCA = <?php echo (int)$dest['distanca_od_bg_km']; ?>;
const PUTARINA = <?php echo (float)$dest['prosecna_putarina_eur']; ?>;

function izracunajTrosak() {
    const putnici    = Math.max(1, parseInt(document.getElementById('in-putnici').value, 10) || 1);
    const potrosnja  = parseFloat(document.getElementById('in-potrosnja').value) || 7.0;
    const cenaGoriva = parseFloat(document.getElementById('in-cena-goriva').value) || 1.65;

    const ukupnoKm    = DISTANCA * 2;
    const gorivo      = (ukupnoKm / 100) * potrosnja * cenaGoriva;
    const putarinaOba = PUTARINA * 2;
    const poOsobi     = (gorivo + putarinaOba) / putnici;

    const el = document.getElementById('rezultat-cena');
    el.classList.add('updating');
    setTimeout(() => {
        el.textContent = '€' + poOsobi.toFixed(2);
        el.classList.remove('updating');
    }, 220);

    const sub = document.getElementById('rezultat-gorivo');
    sub.textContent = `Gorivo: €${gorivo.toFixed(2)} · Putarina: €${putarinaOba.toFixed(2)}`;
    sub.classList.add('visible');
}

document.getElementById('btn-trosak').addEventListener('click', izracunajTrosak);

/* ---- Ski Pas Kalkulator ---- */
let pasKategorija = {
    naziv: '<?php echo htmlspecialchars($ski_pas_cene[0]['kategorija']); ?>',
    dan1:  <?php echo (int)$ski_pas_cene[0]['dan1']; ?>,
    dan3:  <?php echo (int)$ski_pas_cene[0]['dan3']; ?>,
    dan6:  <?php echo (int)$ski_pas_cene[0]['dan6']; ?>,
};
let pasDani = 1;

document.querySelectorAll('#kategorijaControl .segment-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#kategorijaControl .segment-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        pasKategorija = {
            naziv: btn.dataset.kategorija,
            dan1: parseInt(btn.dataset.dan1, 10),
            dan3: parseInt(btn.dataset.dan3, 10),
            dan6: parseInt(btn.dataset.dan6, 10),
        };
        izracunajPas();
    });
});

document.querySelectorAll('#daysControl .day-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#daysControl .day-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        pasDani = parseInt(btn.dataset.dani, 10);
        izracunajPas();
    });
});

document.getElementById('pas-osobe').addEventListener('input', izracunajPas);

function izracunajPas() {
    const osobe = Math.max(1, parseInt(document.getElementById('pas-osobe').value, 10) || 1);
    const cena  = pasDani === 1 ? pasKategorija.dan1
               : pasDani === 3 ? pasKategorija.dan3
               : pasKategorija.dan6;
    const ukupno    = cena * osobe;
    const daniLabel = pasDani === 1 ? '1 dan' : pasDani + ' dana';

    const el = document.getElementById('pasRezultat');
    el.classList.add('updating');
    setTimeout(() => {
        el.textContent = '€' + ukupno;
        el.classList.remove('updating');
    }, 220);

    document.getElementById('pasSub').textContent =
        `${osobe} × ${pasKategorija.naziv.toLowerCase()} · ${daniLabel}`;
}

/* ---- FAQ Accordion ---- */
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const item   = btn.closest('.faq-item');
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
    });
});
</script>

<?php include 'partials/footer.php'; ?>
