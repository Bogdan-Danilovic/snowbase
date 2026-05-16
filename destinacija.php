<?php
require_once 'db.php';

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

/* ======================================================================
   Podaci koji dolaze iz baze ili API-ja (placeholder za demonstraciju)
   U produkciji: povuci iz tabela weather_cache, ski_pas_cene, recenzije
   ====================================================================== */

$vremeData = [
    'temp'           => -3,
    'temp_osecaj'    => -8,
    'sneg_dno_cm'    => 45,
    'sneg_vrh_cm'    => 185,
    'uslovi'         => 'Sunčano',
    'vidljivost'     => 'Odlična (>10 km)',
    'prognoza' => [
        ['dan' => 'PON', 'temp_min' => -8,  'temp_max' => -4, 'stanje' => 'Oblačno',  'ikona' => '☁️'],
        ['dan' => 'UTO', 'temp_min' => -12, 'temp_max' => -7, 'stanje' => 'Sneg',     'ikona' => '❄️'],
        ['dan' => 'SRE', 'temp_min' => -5,  'temp_max' => -1, 'stanje' => 'Sunčano',  'ikona' => '🌤️'],
    ],
];

$staze_status = [
    'plave'           => ['otvorene' => ($dest['plave_otvorene']   ?? 12), 'ukupno' => 15],
    'crvene'          => ['otvorene' => ($dest['crvene_otvorene']  ?? 8),  'ukupno' => 10],
    'crne'            => ['otvorene' => ($dest['crne_otvorene']    ?? 2),  'ukupno' => 4],
    'zicara_aktivnih' => ($dest['zicara_aktivnih'] ?? 8),
    'zicara_ukupno'   => ($dest['broj_zicara']     ?? 10),
];

// U produkciji: SELECT * FROM ski_pas_cene WHERE destinacija_id = ?
$ski_pas_cene = [
    ['kategorija' => 'Odrasli',  'dan1' => 42,  'dan3' => 115, 'dan6' => 195],
    ['kategorija' => 'Studenti', 'dan1' => 35,  'dan3' => 95,  'dan6' => 162],
    ['kategorija' => 'Deca',     'dan1' => 25,  'dan3' => 68,  'dan6' => 112],
    ['kategorija' => 'Senior',   'dan1' => 32,  'dan3' => 88,  'dan6' => 148],
];

// U produkciji: SELECT * FROM recenzije WHERE destinacija_id = ? ORDER BY datum DESC LIMIT 3
$recenzije = [
    [
        'ime'    => 'Marija T.',
        'ocena'  => 5,
        'datum'  => 'Januar 2025.',
        'tekst'  => 'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg je bio prašinast celu nedelju. Organizacija Peak & Palm bila je besprekorna od prvog do poslednjeg dana.',
        'tagovi' => ['Staze ★★★★★', 'Organizacija ★★★★★'],
    ],
    [
        'ime'    => 'Stefan K.',
        'ocena'  => 5,
        'datum'  => 'Februar 2025.',
        'tekst'  => 'Treće godišnje putovanje sa ovom agencijom. Smeštaj tačno prema opisu, transfer sa aerodroma bio brz i bez čekanja. Preporučujem svakome.',
        'tagovi' => ['Smeštaj ★★★★☆', 'Transfer ★★★★★'],
    ],
    [
        'ime'    => 'Ana & Bojan',
        'ocena'  => 4,
        'datum'  => 'Decembar 2024.',
        'tekst'  => 'Odlično za porodice s decom. Ski škola za početnike bila je strpljiva i profesionalna. Noćni život iznad svih očekivanja — pravo iznenađenje!',
        'tagovi' => ['Porodično ★★★★★', 'Noćni život ★★★★☆'],
    ],
];

// U produkciji: SELECT * FROM faq WHERE destinacija_id = ? OR globalni = 1
$faq = [
    [
        'pitanje' => 'Da li je ski pas uključen u cenu aranžmana?',
        'odgovor' => 'Ski pas nije automatski uključen u smeštajni aranžman — to nam omogućava da svaki paket prilagodimo vašim potrebama. Možete ga dokupiti kroz naš kalkulator na ovoj stranici, ili nas kontaktirati za paket deal (smeštaj + pas) koji je često povoljniji od pojedinačne kupovine.',
    ],
    [
        'pitanje' => 'Kakvo zdravstveno osiguranje je potrebno za ski destinacije?',
        'odgovor' => 'Strogo preporučujemo putno osiguranje koje eksplicitno pokriva "zimske sportove i aktivnosti na snegu". Standardne turistički polise često ne pokrivaju skijaške povrede. Imamo dogovor sa partnerskim osiguravačem koji nudi specijalnu skijašku polisu od samo €8/dan po osobi — pitajte naše agente za detalje.',
    ],
    [
        'pitanje' => 'Šta se dešava sa ski pasom ako se planina zatvori zbog nevremena?',
        'odgovor' => 'Svaki skijaški centar iz našeg kataloga ima jasnu kompenzacionu politiku: za zatvaranje duže od 4 uzastopna sata vrši se proporcionalna nadoknada — ili produžetak pasa bez naknade, ili bon za narednu sezonu. Peak & Palm aktivno zastupa vaše interese u takvim situacijama.',
    ],
    [
        'pitanje' => 'Kako rezervisati ski školu ili rentiranje opreme?',
        'odgovor' => 'Rezervacija se vrši minimum 48h pre željenog termina. Popunite kontakt obrazac na kraju stranice ili nas direktno kontaktirajte. Za grupe od 6 i više osoba odobravamo 15% popusta na kompletan paket opreme.',
    ],
];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dest['naziv']); ?> | Peak and Palm</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style 3.0.css">

    <style>
        /* ---- Page-specific overrides ---- */
        .main-content { margin-top: 80px; }

        .hero-section {
            position: fixed; top: 80px; left: 0;
            width: 100%; height: calc(100vh - 80px);
            display: grid; grid-template-columns: 1.2fr 0.8fr;
            gap: 40px; padding: 40px 60px; align-items: center;
            z-index: 10;
            transition: opacity 0.45s cubic-bezier(0.16,1,0.3,1),
                        transform 0.45s cubic-bezier(0.16,1,0.3,1);
            will-change: transform, opacity;
        }

        .hero-section.scrolled {
            opacity: 0;
            transform: translateY(-36px) scale(0.97);
            pointer-events: none;
        }

        /* Staze interaktivna lista */
        .staza { fill: none; stroke-width: 2; stroke-linecap: round; opacity: 0.55; transition: all 0.32s; cursor: pointer; }
        .staza.active, .staza:hover { opacity: 1; stroke-width: 3.2; filter: drop-shadow(0 0 6px currentColor); }
        .staza.plava  { stroke: #38bdf8; color: #38bdf8; }
        .staza.crvena { stroke: #ef4444; color: #ef4444; }
        .staza.crna   { stroke: #b8c8da; color: #b8c8da; }

        /* Scroll content starts below viewport */
        .scroll-content {
            position: relative; margin-top: 100vh;
            background: var(--void); border-top: 1px solid var(--border-subtle);
            padding: 72px 60px 110px; z-index: 20;
            box-shadow: 0 -28px 60px rgba(0,0,0,0.92);
        }
    </style>
</head>
<body>

<!-- Custom cursor -->
<div class="cursor-dot" id="cursorDot"></div>
<div class="cursor-ring" id="cursorRing"></div>

<div class="fixed-bg"></div>

<nav>
    <a href="index.php">Peak<span>&</span>Palm</a>
    <div class="nav-links">
        <a href="index.php">← Katalog</a>
        <a href="#ski-info">Ski Info</a>
        <a href="#smestaj">Smeštaj</a>
        <a href="#logistika">Logistika</a>
        <a href="#ski-pas">Ski Pas</a>
    </div>
</nav>

<div class="main-content">

    <!-- ================================================================
         HERO — Fixed, fades out on scroll
         ================================================================ -->
    <section class="hero-section" id="hero">

        <!-- Left: SVG Mapa -->
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

        <!-- Right: Destination info + slope list -->
        <div class="info-side">
            <span class="hero-label">Katalog Destinacija</span>
            <h1 class="hero-title"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($dest['opis']); ?></p>

            <div class="interactive-list">
                <div class="slope-item" onmouseenter="toggleMap('path-blue',true)"  onmouseleave="toggleMap('path-blue',false)">
                    <div>
                        <span class="slope-name">Plave staze</span>
                    </div>
                    <strong style="color:#38bdf8;"><?php echo $dest['plave_staze_km'] ?? '0'; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-red',true)"   onmouseleave="toggleMap('path-red',false)">
                    <div>
                        <span class="slope-name">Crvene staze</span>
                    </div>
                    <strong style="color:#ef4444;"><?php echo $dest['crvene_staze_km'] ?? '0'; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-black',true)" onmouseleave="toggleMap('path-black',false)">
                    <div>
                        <span class="slope-name">Crne staze</span>
                    </div>
                    <strong style="color:#b8c8da;"><?php echo $dest['crne_staze_km'] ?? '0'; ?> km</strong>
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
            <div class="reveal" id="ski-info" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Realtime</span>
                <h2 class="section-title">Ski <span>&amp; Info</span> Centar</h2>
                <div class="section-divider"></div>

                <div class="ski-info-grid">

                    <!-- Weather widget -->
                    <div class="weather-panel reveal">
                        <div class="weather-top">
                            <div class="weather-temp-display">
                                <span class="weather-temp-num"><?php echo $vremeData['temp']; ?></span>
                                <span class="weather-temp-unit">°C</span>
                            </div>
                            <div class="weather-conditions">
                                <span class="weather-cond-label">Stanje</span>
                                <span class="weather-cond-val"><?php echo $vremeData['uslovi']; ?></span>
                                <span class="weather-cond-icon">☀️</span>
                            </div>
                        </div>

                        <!-- Snow levels -->
                        <div class="snow-levels">
                            <div class="snow-level-item">
                                <label>Sneg — dno</label>
                                <div>
                                    <span class="snow-level-val"><?php echo $vremeData['sneg_dno_cm']; ?></span>
                                    <span class="snow-level-unit">cm</span>
                                </div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vremeData['sneg_dno_cm'] / 2); ?>%"></div>
                                </div>
                            </div>
                            <div class="snow-level-item">
                                <label>Sneg — vrh</label>
                                <div>
                                    <span class="snow-level-val"><?php echo $vremeData['sneg_vrh_cm']; ?></span>
                                    <span class="snow-level-unit">cm</span>
                                </div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vremeData['sneg_vrh_cm'] / 3); ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 3-day forecast -->
                        <div class="forecast-strip">
                            <?php foreach ($vremeData['prognoza'] as $dan): ?>
                            <div class="forecast-day">
                                <span class="forecast-day-name"><?php echo $dan['dan']; ?></span>
                                <span class="forecast-icon"><?php echo $dan['ikona']; ?></span>
                                <span class="forecast-temp">
                                    <?php echo $dan['temp_max']; ?>° 
                                    <span>/ <?php echo $dan['temp_min']; ?>°</span>
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
                            'plave'  => ['label' => 'Plave staze',  'color' => '#38bdf8', 'var' => '--slope-blue'],
                            'crvene' => ['label' => 'Crvene staze', 'color' => '#ef4444', 'var' => '--slope-red'],
                            'crne'   => ['label' => 'Crne staze',   'color' => '#b8c8da', 'var' => '--slope-black'],
                        ];
                        foreach ($staze_config as $key => $cfg):
                            $ot  = $staze_status[$key]['otvorene'];
                            $uk  = $staze_status[$key]['ukupno'];
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
                            <span style="font-size:0.82rem; color:var(--text-secondary);">🚡 Žičare i gondole</span>
                            <span class="zicara-status">
                                <?php echo $staze_status['zicara_aktivnih']; ?> / <?php echo $staze_status['zicara_ukupno']; ?> aktivnih
                            </span>
                        </div>

                        <div style="margin-top:14px; padding:12px 14px; background:rgba(0,0,0,0.2); border-radius:var(--r-sm); border:1px solid var(--border-subtle);">
                            <p style="font-size:0.72rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:2px; margin-bottom:4px;">Vidljivost</p>
                            <span style="font-size:0.88rem; color:var(--text-secondary);"><?php echo $vremeData['vidljivost']; ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 II. SMEŠTAJ
                 ============================================================ -->
            <div class="reveal" id="smestaj" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Partnerski objekti</span>
                <h2 class="section-title">Raspoloživ <span>Smeštaj</span></h2>
                <div class="section-divider"></div>

                <div class="hotel-grid">
                    <?php foreach ($hoteli as $h): ?>
                    <div class="hotel-card reveal">
                        <div style="overflow:hidden;height:185px;">
                            <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop"
                                 class="hotel-img" alt="<?php echo htmlspecialchars($h['naziv']); ?>">
                        </div>
                        <div class="hotel-body">
                            <div class="hotel-stars">
                                <?php for ($s = 0; $s < (int)$h['zvezdice']; $s++): ?>
                                    <span>★</span>
                                <?php endfor; ?>
                            </div>
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p class="hotel-meta">
                                Kategorija <?php echo $h['zvezdice']; ?>★ · Kapacitet <?php echo $h['kapacitet_osoba']; ?> osoba
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
            <div class="reveal" id="prevoz" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Iz Beograda do staze</span>
                <h2 class="section-title">Opcije <span>Prevoza</span></h2>
                <div class="section-divider"></div>

                <div class="transport-grid">

                    <!-- Autobus -->
                    <div class="transport-card bus reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <div class="transport-icon-wrap">🚌</div>
                            <h3 class="transport-title">Agencijski Autobus</h3>
                            <p class="transport-subtitle">Direktna linija</p>
                            <ul class="transport-info-list">
                                <li><span>Polazak</span> <strong>Sava Centar, 22:00h</strong></li>
                                <li><span>Trajanje</span> <strong>~<?php echo round($dest['distanca_od_bg_km'] / 80); ?>h vožnje</strong></li>
                                <li><span>Povratak</span> <strong>Nedeljom, 14:00h</strong></li>
                                <li><span>Prtljag</span> <strong>Kofer + ski torba</strong></li>
                                <li><span>Cena prevoza</span> <strong>€<?php echo round($dest['distanca_od_bg_km'] * 0.06); ?> / osobi</strong></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Avion + Transfer -->
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

                    <!-- Sopstveni auto -->
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
                                <div class="wp"><span class="wp-dot" style="border-color:var(--ice);background:var(--ice-soft);"></span>
                                    <strong style="color:var(--ice);">🏔️ <?php echo htmlspecialchars($dest['naziv']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================
                 IV. KALKULATOR LOGISTIKE (Auto)
                 ============================================================ -->
            <div class="reveal logistika-panel" id="logistika">
                <div>
                    <span class="section-eyebrow">Sopstveni prevoz</span>
                    <h2 class="section-title" style="font-size:2rem; margin-bottom:8px;">Kalkulator <span>Logistike</span></h2>
                    <p style="color:var(--text-secondary); margin-bottom:24px; font-size:0.88rem; line-height:1.6;">
                        Unesite parametre vašeg vozila za proračun troškova <br>povratnog puta iz Beograda.
                    </p>
                    <div class="input-group">
                        <label>Broj putnika</label>
                        <input type="number" id="in-putnici" value="4" min="1" max="9">
                    </div>
                    <div class="input-group">
                        <label>Potrošnja goriva (L/100km)</label>
                        <input type="number" id="in-potrosnja" value="7.0" step="0.1">
                    </div>
                    <div class="input-group">
                        <label>Cena goriva (€/L)</label>
                        <input type="number" id="in-cena-goriva" value="1.65" step="0.05">
                    </div>
                    <button class="btn-calc" onclick="izracunajTrosak()">↗ Izračunaj trošak</button>
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
                    <div class="result-box" style="margin-top:18px;">
                        <p>Cena povratnog puta po osobi:</p>
                        <strong id="rezultat-cena">€--.--</strong>
                        <p id="rezultat-gorivo" style="font-size:0.74rem; margin-top:6px; opacity:0; transition:opacity 0.4s;"></p>
                    </div>
                </div>
            </div><!-- /logistika -->

            <!-- ============================================================
                 V. SKI PAS — Cenovnik + Kalkulator
                 ============================================================ -->
            <div class="reveal" id="ski-pas" style="margin-top: var(--section-gap); margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Transparentne cene</span>
                <h2 class="section-title">Cene <span>Ski Pasa</span></h2>
                <div class="section-divider"></div>

                <div class="ski-pas-layout">

                    <!-- Price table -->
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
                                    <td><?php echo $red['kategorija']; ?></td>
                                    <td>€<?php echo $red['dan1']; ?></td>
                                    <td class="best-col">€<?php echo $red['dan3']; ?></td>
                                    <td>€<?php echo $red['dan6']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- JS Calculator -->
                    <div class="pas-calc reveal">
                        <h3 class="pas-calc-title">Izračunaj <span>ukupno</span></h3>

                        <!-- Category selector -->
                        <p style="font-size:0.72rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:2px; margin-bottom:8px;">Kategorija</p>
                        <div class="segment-control" id="kategorijaControl">
                            <?php
                            foreach ($ski_pas_cene as $i => $red):
                                $active = $i === 0 ? ' active' : '';
                            ?>
                            <button class="segment-btn<?php echo $active; ?>"
                                    onclick="setKategorija(this, '<?php echo $red['kategorija']; ?>', <?php echo $red['dan1']; ?>, <?php echo $red['dan3']; ?>, <?php echo $red['dan6']; ?>)">
                                <?php echo $red['kategorija']; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Days selector -->
                        <p style="font-size:0.72rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:2px; margin-bottom:8px; margin-top:16px;">Broj dana</p>
                        <div class="days-selector" id="daysControl">
                            <button class="day-btn active" onclick="setDan(this, 1)">1 dan</button>
                            <button class="day-btn"        onclick="setDan(this, 3)">3 dana</button>
                            <button class="day-btn"        onclick="setDan(this, 6)">6 dana</button>
                        </div>

                        <!-- Person count -->
                        <div class="input-group" style="margin-bottom:18px;">
                            <label>Broj osoba</label>
                            <input type="number" id="pas-osobe" value="1" min="1" max="20"
                                   oninput="izracunajPas()" style="margin-top:6px;">
                        </div>

                        <div class="pas-result-box">
                            <span class="pas-result-label">Ukupno za ski pas</span>
                            <div class="pas-result-price" id="pasRezultat">€42</div>
                            <p class="pas-result-sub" id="pasSub">1 odrasli · 1 dan</p>
                        </div>
                    </div>

                </div>
            </div><!-- /ski-pas -->

            <!-- ============================================================
                 VI. RENTIRANJE OPREME & SKI ŠKOLA
                 ============================================================ -->
            <div class="reveal" id="oprema" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Agencijski paketi</span>
                <h2 class="section-title">Oprema <span>&amp; Škola</span></h2>
                <div class="section-divider"></div>

                <div class="equipment-section-grid">

                    <!-- Equipment packages -->
                    <div>
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:22px; line-height:1.6;">
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
                                    <div style="font-size:0.75rem; color:var(--text-dim);">Min. 2 dana</div>
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
                                    <div style="font-size:0.75rem; color:rgba(232,184,75,0.5);">Preporučujemo</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Ski school -->
                    <div class="ski-school-panel reveal">
                        <span class="section-eyebrow" style="margin-bottom:8px; display:block;">Škola skijanja</span>
                        <h3 style="font-family:'Cormorant Garamond',serif; font-size:1.6rem; font-weight:700; margin-bottom:4px;">
                            Čas sa <span style="color:var(--ice);">instruktorom</span>
                        </h3>
                        <p style="color:var(--text-secondary); font-size:0.82rem; line-height:1.6; margin-bottom:6px;">
                            Licencirani instruktori skijanja i snowboard-a. Rezervacija min. 48h unapred.
                        </p>

                        <div class="school-packages">
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Grupni čas (do 6 osoba)</div>
                                    <div class="school-package-desc">2h · Svi nivoi · Srpski / Engleski</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€18</strong>
                                    <span>/ osobi</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Individualni čas</div>
                                    <div class="school-package-desc">2h · Personalizovani program</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€65</strong>
                                    <span>/ čas</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">5-dnevni grupni kurs</div>
                                    <div class="school-package-desc">2h dnevno · Sve uzraste · Sertifikat</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€72</strong>
                                    <span>/ osobi</span>
                                </div>
                            </div>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name">Snowboard starter</div>
                                    <div class="school-package-desc">3h · Početnici · Oprema uključena</div>
                                </div>
                                <div class="school-package-price">
                                    <strong>€48</strong>
                                    <span>/ osobi</span>
                                </div>
                            </div>
                        </div>

                        <div class="school-note">
                            🎯 Za grupe 6+ osoba odobravam 15% popusta na sve pakete opreme i školu skijanja.
                        </div>
                    </div>

                </div>
            </div><!-- /oprema -->

            <!-- ============================================================
                 VII. RECENZIJE
                 ============================================================ -->
            <div class="reveal" id="utisci" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Verifikovani putnici</span>
                <h2 class="section-title">Iskustva <span>Putnika</span></h2>
                <div class="section-divider"></div>

                <div class="reviews-grid">
                    <?php foreach ($recenzije as $rec): ?>
                    <div class="review-card reveal">
                        <div class="review-stars">
                            <?php for ($s = 0; $s < $rec['ocena']; $s++): ?>
                                <span class="star">★</span>
                            <?php endfor; ?>
                            <?php for ($s = $rec['ocena']; $s < 5; $s++): ?>
                                <span class="star" style="opacity:0.2;">★</span>
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
                                <div class="review-date"><?php echo $rec['datum']; ?></div>
                            </div>
                            <div style="font-size:1.4rem; opacity:0.35;">✓</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div><!-- /utisci -->

            <!-- ============================================================
                 VIII. FAQ ACCORDION
                 ============================================================ -->
            <div class="reveal" id="faq" style="margin-bottom: var(--section-gap);">
                <span class="section-eyebrow">Sve što trebate znati</span>
                <h2 class="section-title">Česta <span>Pitanja</span></h2>
                <div class="section-divider"></div>

                <div class="faq-list">
                    <?php foreach ($faq as $i => $q): ?>
                    <div class="faq-item" id="faq-<?php echo $i; ?>">
                        <button class="faq-question" onclick="toggleFaq(<?php echo $i; ?>)">
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
            </div><!-- /faq -->

        </div><!-- /container-wide -->
    </section><!-- /scroll-content -->

</div><!-- /main-content -->

<!-- ====================================================================
     JAVASCRIPT
     ==================================================================== -->
<script>
/* ---- Custom Cursor ---- */
const dot  = document.getElementById('cursorDot');
const ring = document.getElementById('cursorRing');
let ringX = 0, ringY = 0, dotX = 0, dotY = 0;
let raf;

document.addEventListener('mousemove', e => {
    dotX = e.clientX;  dotY = e.clientY;
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

document.querySelectorAll('a, button, .slope-item, .faq-question, .hotel-card, .review-card, .transport-card, .day-btn, .segment-btn').forEach(el => {
    el.addEventListener('mouseenter', () => ring.classList.add('hovering'));
    el.addEventListener('mouseleave', () => ring.classList.remove('hovering'));
});

/* ---- Nav mouse glow ---- */
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('mousemove', e => {
        const r = link.getBoundingClientRect();
        link.style.setProperty('--mouse-x', ((e.clientX - r.left) / r.width * 100) + '%');
        link.style.setProperty('--mouse-y', ((e.clientY - r.top)  / r.height * 100) + '%');
    });
});

/* ---- SVG Mapa toggle ---- */
function toggleMap(id, isActive) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('active', isActive);
}

/* ---- Scroll: hero fade + reveal ---- */
window.addEventListener('scroll', onScroll, { passive: true });

function onScroll() {
    const scrolled = window.scrollY;
    const hero = document.getElementById('hero');
    const nav  = document.querySelector('nav');

    nav?.classList.toggle('scrolled', scrolled > 40);

    if (hero) hero.classList.toggle('scrolled', scrolled > 80);

    document.querySelectorAll('.reveal').forEach(el => {
        if (el.getBoundingClientRect().top < window.innerHeight - 90) {
            el.classList.add('visible');
        }
    });
}

// Trigger on load too
window.addEventListener('DOMContentLoaded', () => {
    onScroll();
    // Make initially visible reveals fire
    setTimeout(onScroll, 120);
});

/* ---- Kalkulator Logistike ---- */
const DISTANCA = <?php echo (int)$dest['distanca_od_bg_km']; ?>;
const PUTARINA = <?php echo (float)$dest['prosecna_putarina_eur']; ?>;

function izracunajTrosak() {
    const putnici      = Math.max(1, parseInt(document.getElementById('in-putnici').value) || 1);
    const potrosnja    = parseFloat(document.getElementById('in-potrosnja').value) || 7.0;
    const cenaGoriva   = parseFloat(document.getElementById('in-cena-goriva').value) || 1.65;

    const ukupnoKm     = DISTANCA * 2;
    const litara       = (ukupnoKm / 100) * potrosnja;
    const gorivo       = litara * cenaGoriva;
    const putarinaOba  = PUTARINA * 2;
    const ukupno       = gorivo + putarinaOba;
    const poOsobi      = ukupno / putnici;

    const el = document.getElementById('rezultat-cena');
    el.style.transform = 'scale(0.88)';
    el.style.opacity   = '0.4';
    setTimeout(() => {
        el.textContent = '€' + poOsobi.toFixed(2);
        el.style.transform = '';
        el.style.opacity   = '';
    }, 250);

    const sub = document.getElementById('rezultat-gorivo');
    sub.textContent = `Gorivo: €${gorivo.toFixed(2)} · Putarina: €${putarinaOba.toFixed(2)}`;
    sub.style.opacity = '1';
}

/* ---- Ski Pas Kalkulator ---- */
let pasKategorija = { naziv: 'Odrasli', dan1: <?php echo $ski_pas_cene[0]['dan1']; ?>, dan3: <?php echo $ski_pas_cene[0]['dan3']; ?>, dan6: <?php echo $ski_pas_cene[0]['dan6']; ?> };
let pasDani = 1;

function setKategorija(btn, naziv, d1, d3, d6) {
    document.querySelectorAll('#kategorijaControl .segment-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    pasKategorija = { naziv, dan1: d1, dan3: d3, dan6: d6 };
    izracunajPas();
}

function setDan(btn, dani) {
    document.querySelectorAll('#daysControl .day-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    pasDani = dani;
    izracunajPas();
}

function izracunajPas() {
    const osobe  = Math.max(1, parseInt(document.getElementById('pas-osobe').value) || 1);
    const cena1  = pasDani === 1 ? pasKategorija.dan1 : pasDani === 3 ? pasKategorija.dan3 : pasKategorija.dan6;
    const ukupno = cena1 * osobe;
    const daniLabel = pasDani === 1 ? '1 dan' : pasDani + ' dana';

    const el = document.getElementById('pasRezultat');
    el.classList.add('updating');
    setTimeout(() => {
        el.textContent = '€' + ukupno;
        el.classList.remove('updating');
    }, 220);

    document.getElementById('pasSub').textContent = `${osobe} × ${pasKategorija.naziv.toLowerCase()} · ${daniLabel}`;
}

/* ---- FAQ Accordion ---- */
function toggleFaq(index) {
    const item = document.getElementById('faq-' + index);
    const isOpen = item.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));

    // Open clicked (unless it was already open)
    if (!isOpen) item.classList.add('open');
}
</script>

</body>
</html>
