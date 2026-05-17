<?php
/**
 * destinacija.php — Snowbase
 * Bogata DB-driven stranica destinacije: hero mapa, vreme, live status,
 * smestaj, prevoz, logistika kalkulator, ski pas, oprema & skola, recenzije, FAQ.
 *
 * Svi podaci dolaze iz baze. URL: destinacija.php?id=X
 */
require_once 'db.php';

/* ============================================================
   1. OSNOVNI PODACI O DESTINACIJI
   ============================================================ */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
    $stmt = $pdo->prepare("
        SELECT d.*,
               s.ukupno_staza_km, s.plave_staze_km, s.crvene_staze_km, s.crne_staze_km, s.broj_zicara,
               gp.naziv AS prelaz_naziv
        FROM   destinacije d
        LEFT JOIN ski_info         s  ON s.destinacija_id = d.id
        LEFT JOIN granicni_prelazi gp ON gp.id = d.granicni_prelaz_id
        WHERE  d.id = ?
    ");
    $stmt->execute([$id]);
    $dest = $stmt->fetch();
    if (!$dest) { http_response_code(404); die("Destinacija nije pronađena."); }
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/* ============================================================
   2. POMOĆNA: safe fetch (vraca prazan niz ako tabela ne postoji)
   ============================================================ */
function fetchByDest(PDO $pdo, string $sql, int $id): array {
    try {
        $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetchAll();
    } catch (PDOException $e) { return []; }
}
function fetchOneByDest(PDO $pdo, string $sql, int $id) {
    try {
        $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetch();
    } catch (PDOException $e) { return null; }
}

/* ============================================================
   3. SVE POVEZANE TABELE
   ============================================================ */
$hoteli         = fetchByDest($pdo, "SELECT * FROM smestaj WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$vreme          = fetchOneByDest($pdo, "SELECT * FROM vreme_trenutno WHERE destinacija_id = ?", $id);
$prognoza       = fetchByDest($pdo, "SELECT * FROM vreme_prognoza WHERE destinacija_id = ? ORDER BY redosled, id LIMIT 7", $id);
$staze_status   = fetchOneByDest($pdo, "SELECT * FROM staze_status WHERE destinacija_id = ?", $id);
$ski_pas_cene   = fetchByDest($pdo, "SELECT kategorija, cena_1dan AS dan1, cena_3dana AS dan3, cena_6dana AS dan6 FROM ski_pas_cene WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$transport      = fetchByDest($pdo, "SELECT * FROM transport_opcije WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$oprema_paketi  = fetchByDest($pdo, "SELECT * FROM oprema_paketi WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$skola_paketi   = fetchByDest($pdo, "SELECT * FROM skola_paketi WHERE destinacija_id = ? ORDER BY redosled, id", $id);

/* JSON dekodiranje za transport stavke i oprema includes */
foreach ($transport as &$t) {
    $t['stavke'] = $t['stavke_json'] ? (json_decode($t['stavke_json'], true) ?: []) : [];
}
unset($t);
foreach ($oprema_paketi as &$o) {
    $o['includes'] = $o['includes_json'] ? (json_decode($o['includes_json'], true) ?: []) : [];
}
unset($o);

/* Slike — grupisanje */
$sve_slike = fetchByDest($pdo, "SELECT tip, url, alt FROM destinacije_slike WHERE destinacija_id = ? ORDER BY tip, redosled, id", $id);
$slike = ['hero' => [], 'mapa_staza' => [], 'gallery' => []];
foreach ($sve_slike as $s) { $slike[$s['tip']][] = $s; }
$mapa_staza_url = $slike['mapa_staza'][0]['url'] ?? null;

/* SVG putanje */
$staze_putanje = fetchByDest($pdo, "SELECT tip_klasa, naziv, svg_d_putanja, duzina_km FROM staze_putanje WHERE destinacija_id = ? ORDER BY redosled, id", $id);

/* Recenzije po destinaciji */
$recenzije_raw = fetchByDest($pdo, "SELECT ime, ocena, datum_prikaza AS datum, tekst, tagovi FROM recenzije WHERE destinacija_id = ? ORDER BY redosled, id LIMIT 6", $id);
$recenzije = array_map(function($r) {
    $r['tagovi'] = $r['tagovi'] ? (json_decode($r['tagovi'], true) ?: []) : [];
    return $r;
}, $recenzije_raw);

/* FAQ — globalni (NULL) + per-destinacija */
$faq = [];
try {
    $s = $pdo->prepare("SELECT pitanje, odgovor FROM faq WHERE aktivan = 1 AND (destinacija_id = ? OR destinacija_id IS NULL) ORDER BY destinacija_id IS NULL, redosled, id");
    $s->execute([$id]);
    $faq = $s->fetchAll();
} catch (PDOException $e) {}

/* ============================================================
   4. STAZE PUTANJE — label mapa za tipove (lako prosirivo)
   ============================================================ */
$labele_grupa = [
    'plava'  => ['naziv' => 'Plave staze',  'css' => 'plava'],
    'crvena' => ['naziv' => 'Crvene staze', 'css' => 'crvena'],
    'crna'   => ['naziv' => 'Crne staze',   'css' => 'crna'],
];

/* Grupisanje SVG putanja po tipu — zbira duzina */
$staze_po_tipu = [];
foreach ($staze_putanje as $sp) {
    $tip = $sp['tip_klasa'];
    if (!isset($staze_po_tipu[$tip])) {
        $staze_po_tipu[$tip] = ['duzina' => 0.0];
    }
    $staze_po_tipu[$tip]['duzina'] += (float)$sp['duzina_km'];
}

/* ============================================================
   5. PARAMETRI ZA HEAD + NAV
   ============================================================ */
$page_title = htmlspecialchars($dest['naziv']) . ' | Snowbase';

include 'partials/head.php';
?>
<body>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<div class="main-content">

    <!-- ================================================================
         HERO — Fixed, fades on scroll
         ================================================================ -->
    <section class="hero-section" id="hero">
        <div class="prava-mapa-container">
            <svg viewBox="0 0 640 346" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg">
                <?php if ($mapa_staza_url): ?>
                    <image href="<?php echo htmlspecialchars($mapa_staza_url); ?>" width="640" height="346" preserveAspectRatio="xMidYMid slice"/>
                <?php endif; ?>
                <g>
                    <?php foreach ($staze_putanje as $sp): ?>
                        <path class="staza <?php echo htmlspecialchars($sp['tip_klasa']); ?>"
                              d="<?php echo htmlspecialchars($sp['svg_d_putanja']); ?>" />
                    <?php endforeach; ?>
                </g>
            </svg>
        </div>

        <div class="info-side">
            <span class="hero-label">
                <?php
                $parts = array_filter([$dest['zemlja'] ?? null, $dest['region'] ?? null]);
                echo htmlspecialchars($parts ? implode(' · ', $parts) : 'Premium Alpine');
                ?>
            </span>
            <h1 class="hero-title"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($dest['opis'] ?? ''); ?></p>

            <div class="interactive-list">
                <?php
                /* Iz `ski_info` polja: plave_staze_km / crvene_staze_km / crne_staze_km */
                $hero_lista = [
                    'plava'  => ['label' => 'Plave staze',  'km' => $dest['plave_staze_km']  ?? 0],
                    'crvena' => ['label' => 'Crvene staze', 'km' => $dest['crvene_staze_km'] ?? 0],
                    'crna'   => ['label' => 'Crne staze',   'km' => $dest['crne_staze_km']   ?? 0],
                ];
                foreach ($hero_lista as $tip => $row):
                ?>
                <div class="slope-item" data-tip="<?php echo $tip; ?>">
                    <div><span class="slope-name"><?php echo $row['label']; ?></span></div>
                    <strong class="slope-km <?php echo $tip; ?>">
                        <?php echo number_format((float)$row['km'], 1, ',', ''); ?> km
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================================================================
         SCROLL CONTENT
         ================================================================ -->
    <section class="scroll-content">
        <div class="container-wide">

            <!-- I. SKI & INFO CENTAR (vreme + live staze status) -->
            <?php if ($vreme || $staze_status): ?>
            <div class="reveal section-block" id="ski-info">
                <span class="section-eyebrow">Realtime</span>
                <h2 class="section-title">Ski <span>&amp; Info</span> Centar</h2>
                <div class="section-divider"></div>

                <div class="ski-info-grid">

                    <?php if ($vreme): ?>
                    <div class="weather-panel reveal">
                        <div class="weather-top">
                            <div class="weather-temp-display">
                                <span class="weather-temp-num"><?php echo (int)$vreme['temp_c']; ?></span>
                                <span class="weather-temp-unit">°C</span>
                            </div>
                            <div class="weather-conditions">
                                <span class="weather-cond-label">Stanje</span>
                                <span class="weather-cond-val"><?php echo htmlspecialchars($vreme['uslovi']); ?></span>
                            </div>
                        </div>

                        <div class="snow-levels">
                            <div class="snow-level-item">
                                <label>Sneg — dno</label>
                                <div><span class="snow-level-val"><?php echo (int)$vreme['sneg_dno_cm']; ?></span><span class="snow-level-unit">cm</span></div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vreme['sneg_dno_cm'] / 2); ?>%"></div>
                                </div>
                            </div>
                            <div class="snow-level-item">
                                <label>Sneg — vrh</label>
                                <div><span class="snow-level-val"><?php echo (int)$vreme['sneg_vrh_cm']; ?></span><span class="snow-level-unit">cm</span></div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vreme['sneg_vrh_cm'] / 3); ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="forecast-strip">
                            <?php foreach ($prognoza as $dan): ?>
                            <div class="forecast-day">
                                <span class="forecast-day-name"><?php echo htmlspecialchars($dan['dan_skraceno']); ?></span>
                                <span class="forecast-temp">
                                    <?php echo (int)$dan['temp_max']; ?>° <span>/ <?php echo (int)$dan['temp_min']; ?>°</span>
                                </span>
                                <span class="forecast-stanje"><?php echo htmlspecialchars($dan['stanje']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($staze_status): ?>
                    <div class="live-status-panel reveal">
                        <span class="live-badge">Live Status</span>
                        <?php
                        $status_config = [
                            'plave'  => ['label' => 'Plave staze',  'css' => 'plava',  'ot' => $staze_status['plave_otvorene'],  'uk' => $staze_status['plave_ukupno']],
                            'crvene' => ['label' => 'Crvene staze', 'css' => 'crvena', 'ot' => $staze_status['crvene_otvorene'], 'uk' => $staze_status['crvene_ukupno']],
                            'crne'   => ['label' => 'Crne staze',   'css' => 'crna',   'ot' => $staze_status['crne_otvorene'],   'uk' => $staze_status['crne_ukupno']],
                        ];
                        foreach ($status_config as $row):
                            $pct = $row['uk'] > 0 ? round(($row['ot'] / $row['uk']) * 100) : 0;
                        ?>
                        <div class="staze-bar-row">
                            <div class="staze-bar-header">
                                <span class="staze-type-label">
                                    <span class="staze-dot <?php echo $row['css']; ?>"></span>
                                    <?php echo $row['label']; ?>
                                </span>
                                <span class="staze-count">
                                    <strong><?php echo (int)$row['ot']; ?></strong> / <?php echo (int)$row['uk']; ?> otvoreno
                                </span>
                            </div>
                            <div class="staze-progress-track">
                                <div class="staze-progress-fill <?php echo $row['css']; ?>" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="zicara-row">
                            <span class="zicara-label">Žičare i gondole</span>
                            <span class="zicara-status">
                                <strong><?php echo (int)$staze_status['zicara_aktivnih']; ?></strong> / <?php echo (int)$staze_status['zicara_ukupno']; ?> aktivnih
                            </span>
                        </div>

                        <?php if (!empty($vreme['vidljivost'])): ?>
                        <div class="vidljivost-box">
                            <p class="vidljivost-label">Vidljivost</p>
                            <span class="vidljivost-val"><?php echo htmlspecialchars($vreme['vidljivost']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- II. SMEŠTAJ -->
            <?php if (!empty($hoteli)): ?>
            <div class="reveal section-block" id="smestaj">
                <span class="section-eyebrow">Partnerski objekti</span>
                <h2 class="section-title">Raspoloživ <span>Smeštaj</span></h2>
                <div class="section-divider"></div>

                <div class="hotel-grid">
                    <?php foreach ($hoteli as $h): ?>
                    <div class="hotel-card reveal">
                        <div class="hotel-img-wrap">
                            <img src="<?php echo htmlspecialchars($h['slika_url'] ?? 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop'); ?>"
                                 class="hotel-img" alt="<?php echo htmlspecialchars($h['naziv']); ?>"
                                 width="800" height="500" loading="lazy" decoding="async">
                        </div>
                        <div class="hotel-body">
                            <div class="hotel-stars">
                                <?php for ($s = 0; $s < (int)$h['zvezdice']; $s++): ?><span>★</span><?php endfor; ?>
                            </div>
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p class="hotel-meta">
                                Kategorija <?php echo (int)$h['zvezdice']; ?>★ · Kapacitet <?php echo (int)$h['kapacitet_osoba']; ?> osoba
                            </p>
                            <div class="hotel-price">
                                €<?php echo number_format((float)$h['cena_po_noci_eur'], 0); ?>
                                <small>/ noć / osoba</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- III. OPCIJE PREVOZA -->
            <?php if (!empty($transport)):
                $TIP_NASLOV = ['bus' => 'Bus', 'avion' => 'Avion + Transfer', 'auto' => 'Auto'];
            ?>
            <div class="reveal section-block" id="prevoz">
                <span class="section-eyebrow">Iz Beograda do staze</span>
                <h2 class="section-title">Opcije <span>Prevoza</span></h2>
                <div class="section-divider"></div>

                <div class="transport-grid">
                    <?php foreach ($transport as $t):
                        $naslov = $TIP_NASLOV[$t['tip']] ?? $t['naziv'];
                    ?>
                    <div class="transport-card <?php echo htmlspecialchars($t['tip']); ?> reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <h3 class="transport-title"><?php echo htmlspecialchars($naslov); ?></h3>
                            <ul class="transport-info-list">
                                <?php foreach ($t['stavke'] as $stavka): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($stavka['label'] ?? ''); ?></span>
                                    <strong><?php echo htmlspecialchars($stavka['vrednost'] ?? ''); ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- IV. LOGISTIKA KALKULATOR -->
            <div class="reveal logistika-panel" id="logistika">
                <div>
                    <span class="section-eyebrow">Sopstveni prevoz</span>
                    <h2 class="section-title section-title-sm">Kalkulator <span>Logistike</span></h2>
                    <p class="logistika-intro">
                        Unesite parametre vašeg vozila za proračun troškova<br>povratnog puta iz Beograda.
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
                    <button class="btn-calc" id="btn-trosak">Izračunaj trošak</button>
                </div>

                <div>
                    <div class="stat-item">
                        <p>Udaljenost od Beograda</p>
                        <strong><?php echo (int)$dest['distanca_od_bg_km']; ?> km</strong>
                    </div>
                    <div class="stat-item">
                        <p>Prosečna putarina (tur-retur)</p>
                        <strong>€<?php echo number_format((float)$dest['prosecna_putarina_eur'] * 2, 0); ?></strong>
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

            <!-- V. SKI PAS — cenovnik + kalkulator -->
            <?php if (!empty($ski_pas_cene)): ?>
            <div class="reveal section-block section-block-top" id="ski-pas">
                <span class="section-eyebrow">Transparentne cene</span>
                <h2 class="section-title">Cene <span>Ski Pasa</span></h2>
                <div class="section-divider"></div>

                <div class="ski-pas-layout">
                    <div class="pas-table-wrap reveal">
                        <table class="pas-table">
                            <thead><tr><th>Kategorija</th><th>1 dan</th><th class="best">3 dana</th><th>6 dana</th></tr></thead>
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
                            <p class="pas-result-sub" id="pasSub">1 <?php echo htmlspecialchars(strtolower($ski_pas_cene[0]['kategorija'])); ?> · 1 dan</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- VI. OPREMA & ŠKOLA -->
            <?php if (!empty($oprema_paketi) || !empty($skola_paketi)): ?>
            <div class="reveal section-block" id="oprema">
                <span class="section-eyebrow">Agencijski paketi</span>
                <h2 class="section-title">Oprema <span>&amp; Škola</span></h2>
                <div class="section-divider"></div>

                <div class="equipment-section-grid">

                    <?php if (!empty($oprema_paketi)): ?>
                    <div>
                        <p class="equipment-intro">
                            Rentiranje kompletne opreme direktno kroz agenciju — bez čekanja u redu na skijalištima.
                        </p>
                        <div class="equipment-cards">
                            <?php foreach ($oprema_paketi as $op): ?>
                            <div class="equipment-card <?php echo $op['preporuceno'] ? 'premium' : 'ekonomicni'; ?> reveal">
                                <?php if (!empty($op['badge'])): ?>
                                    <span class="equipment-badge"><?php echo htmlspecialchars($op['badge']); ?></span>
                                <?php endif; ?>
                                <h3 class="equipment-name"><?php echo htmlspecialchars($op['naziv']); ?></h3>
                                <?php if (!empty($op['opis'])): ?>
                                    <p class="equipment-desc"><?php echo htmlspecialchars($op['opis']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($op['includes'])): ?>
                                <ul class="equipment-includes">
                                    <?php foreach ($op['includes'] as $line): ?>
                                        <li><?php echo htmlspecialchars($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                <div class="equipment-price-row">
                                    <div>
                                        <div class="equipment-price">€<?php echo (int)$op['cena_eur']; ?></div>
                                        <div class="equipment-period">po danu / po osobi</div>
                                    </div>
                                    <?php if (!empty($op['napomena'])): ?>
                                        <div class="equipment-note<?php echo $op['preporuceno'] ? ' recommend' : ''; ?>">
                                            <?php echo htmlspecialchars($op['napomena']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skola_paketi)): ?>
                    <div class="ski-school-panel reveal">
                        <span class="section-eyebrow eyebrow-tight">Škola skijanja</span>
                        <h3 class="ski-school-title">Čas sa <span>instruktorom</span></h3>
                        <p class="ski-school-intro">Licencirani instruktori skijanja i snowboarda. Rezervacija min. 48h unapred.</p>

                        <div class="school-packages">
                            <?php foreach ($skola_paketi as $sk): ?>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name"><?php echo htmlspecialchars($sk['naziv']); ?></div>
                                    <?php if (!empty($sk['opis'])): ?>
                                        <div class="school-package-desc"><?php echo htmlspecialchars($sk['opis']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="school-package-price">
                                    <strong>€<?php echo (int)$sk['cena_eur']; ?></strong>
                                    <span>/ <?php echo htmlspecialchars($sk['jedinica']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="school-note">
                            Za grupe 6+ osoba odobravamo 15% popusta na sve pakete opreme i školu skijanja.
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- VII. RECENZIJE -->
            <?php if (!empty($recenzije)): ?>
            <div class="reveal section-block" id="utisci">
                <span class="section-eyebrow">Verifikovani putnici</span>
                <h2 class="section-title">Iskustva <span>Putnika</span></h2>
                <div class="section-divider"></div>

                <div class="reviews-grid">
                    <?php foreach ($recenzije as $rec): ?>
                    <div class="review-card reveal">
                        <div class="review-stars">
                            <?php for ($s = 0; $s < (int)$rec['ocena']; $s++): ?><span class="star">★</span><?php endfor; ?>
                            <?php for ($s = (int)$rec['ocena']; $s < 5; $s++): ?><span class="star dim">★</span><?php endfor; ?>
                        </div>
                        <?php if (!empty($rec['tagovi'])): ?>
                        <div class="review-tags">
                            <?php foreach ($rec['tagovi'] as $tag): ?>
                                <span class="review-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
            <?php endif; ?>

            <!-- VIII. FAQ -->
            <?php if (!empty($faq)): ?>
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
                            <div class="faq-answer-inner"><?php echo htmlspecialchars($q['odgovor']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
/* ---- Slope hover → aktivira sve <path> sa tom klasom ---- */
document.querySelectorAll('.slope-item').forEach(item => {
    const klasa = item.dataset.tip;
    const paths = document.querySelectorAll('.staza.' + klasa);
    item.addEventListener('mouseenter', () => paths.forEach(p => p.classList.add('active')));
    item.addEventListener('mouseleave', () => paths.forEach(p => p.classList.remove('active')));
});

/* ---- Hero fade na scroll + nav scrolled state (rAF throttled) ---- */
const hero = document.getElementById('hero');
const nav  = document.getElementById('main-nav');
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

/* ---- Reveal animacije (IntersectionObserver) ---- */
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

/* ---- Logistika kalkulator ---- */
const DISTANCA = <?php echo (int)$dest['distanca_od_bg_km']; ?>;
const PUTARINA = <?php echo (float)$dest['prosecna_putarina_eur']; ?>;
function izracunajTrosak() {
    const putnici    = Math.max(1, parseInt(document.getElementById('in-putnici').value, 10) || 1);
    const potrosnja  = parseFloat(document.getElementById('in-potrosnja').value) || 7.0;
    const cenaGoriva = parseFloat(document.getElementById('in-cena-goriva').value) || 1.65;
    const gorivo     = (DISTANCA * 2 / 100) * potrosnja * cenaGoriva;
    const putarinaOba= PUTARINA * 2;
    const poOsobi    = (gorivo + putarinaOba) / putnici;
    const el = document.getElementById('rezultat-cena');
    el.classList.add('updating');
    setTimeout(() => { el.textContent = '€' + poOsobi.toFixed(2); el.classList.remove('updating'); }, 220);
    const sub = document.getElementById('rezultat-gorivo');
    sub.textContent = `Gorivo: €${gorivo.toFixed(2)} · Putarina: €${putarinaOba.toFixed(2)}`;
    sub.classList.add('visible');
}
document.getElementById('btn-trosak').addEventListener('click', izracunajTrosak);

/* ---- Ski pas kalkulator ---- */
<?php if (!empty($ski_pas_cene)): ?>
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
    const cena  = pasDani === 1 ? pasKategorija.dan1 : pasDani === 3 ? pasKategorija.dan3 : pasKategorija.dan6;
    const el = document.getElementById('pasRezultat');
    el.classList.add('updating');
    setTimeout(() => { el.textContent = '€' + (cena * osobe); el.classList.remove('updating'); }, 220);
    const daniLabel = pasDani === 1 ? '1 dan' : pasDani + ' dana';
    document.getElementById('pasSub').textContent = `${osobe} × ${pasKategorija.naziv.toLowerCase()} · ${daniLabel}`;
}
<?php endif; ?>

/* ---- FAQ accordion ---- */
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
    });
});
</script>

<?php include 'partials/footer.php'; ?>
