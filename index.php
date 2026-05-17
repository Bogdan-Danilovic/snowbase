<?php
/**
 * index.php — Snowbase katalog
 * Hero, live ticker, total-budget kalkulator, partneri, mapa Evrope,
 * recenzije karusel, katalog ski destinacija.
 */
require_once 'db.php';

/* ============================================================
   1. DESTINACIJE iz baze (sa ski_info JOIN-om)
   ============================================================ */
try {
    $stmt = $pdo->query("
        SELECT d.*, s.ukupno_staza_km, s.broj_zicara
        FROM   destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
        ORDER BY d.id
    ");
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/* ============================================================
   2. AGREGATI ZA TOTAL BUDGET KALKULATOR (po destinaciji)
   Prosek smestaja + Odrasli ski pas + Auto prevoz.
   Šaljemo JS-u kao JSON tako da kalkulator radi BEZ Ajax-a.
   ============================================================ */
$budget_data = [];
foreach ($destinacije as $d) {
    /* prosečna cena hotela */
    $h = $pdo->prepare("SELECT AVG(cena_po_noci_eur) FROM smestaj WHERE destinacija_id = ?");
    $h->execute([$d['id']]);
    $avgHotel = (float)$h->fetchColumn();

    /* odrasli ski pas — najjeftinija (1 dan), srednja (3 dana), nedeljna (6 dana) */
    $p = $pdo->prepare("SELECT cena_1dan, cena_3dana, cena_6dana FROM ski_pas_cene WHERE destinacija_id = ? AND kategorija = 'Odrasli' LIMIT 1");
    $p->execute([$d['id']]);
    $pas = $p->fetch() ?: ['cena_1dan' => 0, 'cena_3dana' => 0, 'cena_6dana' => 0];

    $budget_data[$d['id']] = [
        'naziv'          => $d['naziv'],
        'hotel_prosek'   => round($avgHotel, 1),
        'pas_1'          => (float)$pas['cena_1dan'],
        'pas_3'          => (float)$pas['cena_3dana'],
        'pas_6'          => (float)$pas['cena_6dana'],
        'distanca_km'    => (int)($d['distanca_od_bg_km'] ?? 0),
        'putarina'       => (float)($d['prosecna_putarina_eur'] ?? 0),
    ];
}

/* ============================================================
   3. TICKER + HOMEPAGE RECENZIJE
   ============================================================ */
$ticker_items = [];
$recenzije    = [];
try {
    $s = $pdo->query("SELECT tekst FROM ticker_items WHERE aktivan = 1 ORDER BY redosled, id");
    $ticker_items = $s->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}
try {
    $s = $pdo->query("SELECT ime, avatar, tekst, ocena, datum_prikaza AS datum, lokacija AS dest FROM recenzije WHERE na_homepage = 1 ORDER BY redosled, id LIMIT 8");
    $recenzije = $s->fetchAll();
} catch (PDOException $e) {}

/* ============================================================
   4. KONFIGURACIJA STRANICE
   ============================================================ */
$page_title = 'Snowbase — Premium Alpine Travel';
include 'partials/head.php';
?>
<body>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<!-- ============================================================
     1. VIDEO HERO
     ============================================================ -->
<section class="video-hero" id="hero">

    <div class="vhero-video-wrap">
        <div class="vhero-fallback"></div>
    </div>
    <div class="vhero-overlay"></div>

    <div class="vhero-content">
        <div class="vhero-eyebrow">Vrh Sezone</div>
        <h1 class="vhero-title">
            Od Beograda do<br>
            <em>najvećih</em> Alpa
        </h1>
        <p class="vhero-subtitle">
            Snowbase je premium ski katalog za one koji traže više od skijališta —
            logistika, smeštaj, oprema i atmosfera na jednom mestu.
        </p>
        <div class="vhero-cta-group">
            <a href="#katalog" class="vhero-cta-primary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="7" cy="7" r="6"/><polyline points="7,4 7,7 9,9"/>
                </svg>
                Istraži katalog
            </a>
            <a href="#budget" class="vhero-cta-secondary">
                Brzi kalkulator →
            </a>
        </div>
    </div>

    <div class="vhero-scroll">
        <span>Skroluj</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- ============================================================
     2. LIVE TICKER
     ============================================================ -->
<?php if (!empty($ticker_items)): ?>
<div class="ticker-section">
    <div class="ticker-inner">
        <div class="ticker-label">
            <div class="ticker-label-dot"></div>
            Live
        </div>
        <div class="ticker-track">
            <div class="ticker-tape" id="tickerTape">
                <?php $all = array_merge($ticker_items, $ticker_items); foreach ($all as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     3. TOTAL BUDGET KALKULATOR
     ============================================================ -->
<section class="route-finder-section" id="budget">
    <div class="route-finder-wrap reveal">
        <div class="rf-header">
            <span class="rf-eyebrow">Brzi proračun</span>
            <h2 class="rf-title">Kalkulator ukupnog budžeta</h2>
            <p class="rf-subtitle">Izaberite destinaciju, broj osoba i trajanje — vidite okvirnu cenu smeštaja, ski pasa i prevoza za celu grupu.</p>
        </div>

        <div class="rf-grid">
            <div class="rf-field">
                <label class="rf-label" for="bg-dest">Destinacija</label>
                <div class="rf-select-wrap">
                    <select class="rf-select" id="bg-dest">
                        <option value="">— Izaberite skijalište —</option>
                        <?php foreach ($destinacije as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['naziv']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="rf-field">
                <label class="rf-label" for="bg-osobe">Broj osoba</label>
                <input type="number" class="rf-input" id="bg-osobe" min="1" max="9" value="2">
            </div>
            <div class="rf-field">
                <label class="rf-label" for="bg-dani">Trajanje (dana)</label>
                <input type="number" class="rf-input" id="bg-dani" min="1" max="21" value="6">
            </div>
            <div>
                <button type="button" class="rf-btn" id="bg-submit">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="7" y1="1" x2="7" y2="13"/>
                        <polyline points="3,9 7,13 11,9"/>
                    </svg>
                    Izračunaj
                </button>
            </div>
        </div>

        <!-- Inline rezultat (po dobijanju kalkulacije) -->
        <div class="bg-result" id="bg-result" aria-hidden="true">
            <div class="bg-result-grid">
                <div class="bg-result-item">
                    <span class="bg-result-label">Smeštaj</span>
                    <strong class="bg-result-value" id="bg-r-hotel">€0</strong>
                    <small class="bg-result-hint" id="bg-r-hotel-h"></small>
                </div>
                <div class="bg-result-item">
                    <span class="bg-result-label">Ski pas</span>
                    <strong class="bg-result-value" id="bg-r-pas">€0</strong>
                    <small class="bg-result-hint" id="bg-r-pas-h"></small>
                </div>
                <div class="bg-result-item">
                    <span class="bg-result-label">Prevoz</span>
                    <strong class="bg-result-value" id="bg-r-prevoz">€0</strong>
                    <small class="bg-result-hint" id="bg-r-prevoz-h"></small>
                </div>
                <div class="bg-result-item bg-result-total">
                    <span class="bg-result-label">Ukupno</span>
                    <strong class="bg-result-value" id="bg-r-total">€0</strong>
                    <small class="bg-result-hint" id="bg-r-total-h"></small>
                </div>
            </div>
            <a href="#" id="bg-detalji" class="bg-result-cta">Pogledaj detaljnu destinaciju →</a>
        </div>
    </div>
</section>

<!-- ============================================================
     4. PARTNERS
     ============================================================ -->
<section class="partners-section" id="partneri">
    <div class="partners-label">Premium Partneri &amp; Preporučena Oprema</div>
    <div class="partners-track">
        <a class="partner-item" data-category="Skije" href="https://www.elanskis.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">ELAN<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije" href="https://www.fischersports.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Fischer<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije &amp; Oprema" href="https://www.atomic.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Atomic<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Garderoba" href="https://www.salomon.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Salomon<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Garderoba" href="https://www.bogner.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Bogner<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije" href="https://www.voelkl.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Völkl<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Prevoz" href="https://www.flixbus.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">FlixBus<span class="logo-tag">®</span></span>
        </a>
    </div>
</section>

<!-- ============================================================
     5. MAPA EVROPE
     ============================================================ -->
<section class="europe-section" id="mapa">
    <div class="europe-header reveal">
        <span class="section-eyebrow">Logistika iz Beograda</span>
        <h2 class="section-heading">Naše <span>Destinacije</span> na mapi</h2>
    </div>

    <div class="europe-map-container reveal" id="europeMapContainer">
        <div class="map-tooltip" id="mapTooltip"></div>
        <svg viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg"
             style="background: rgba(7,12,24,0.6); border-radius: 20px; border: 1px solid rgba(var(--ice-rgb),0.08);">
            <defs>
                <filter id="lineGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur"/>
                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="dotGlow" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="4" result="blur"/>
                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <radialGradient id="bgGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%"   stop-color="#0a1428" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#04060d" stop-opacity="1"/>
                </radialGradient>
                <pattern id="dotGrid" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="0.7" fill="rgba(255,255,255,0.06)"/>
                </pattern>
            </defs>
            <rect width="800" height="500" fill="url(#bgGrad)" rx="20"/>
            <rect width="800" height="500" fill="url(#dotGrid)" rx="20"/>

            <!-- Animovane linije -->
            <path d="M 480 300 Q 380 200 230 245" stroke="rgba(var(--ice-rgb),0.45)" stroke-width="1.5" fill="none" stroke-dasharray="6 4" filter="url(#lineGlow)" style="animation: dash-flow 4s linear infinite;"></path>
            <path d="M 480 300 Q 420 260 310 275" stroke="rgba(var(--ice-rgb),0.40)" stroke-width="1.5" fill="none" stroke-dasharray="6 4" filter="url(#lineGlow)" style="animation: dash-flow 3.5s linear infinite 0.6s;"></path>
            <path d="M 480 300 Q 445 255 370 228" stroke="rgba(var(--ice-rgb),0.45)" stroke-width="1.5" fill="none" stroke-dasharray="6 4" filter="url(#lineGlow)" style="animation: dash-flow 3s linear infinite 1.2s;"></path>
            <path d="M 480 300 Q 400 230 285 240" stroke="rgba(var(--ice-rgb),0.35)" stroke-width="1.5" fill="none" stroke-dasharray="6 4" filter="url(#lineGlow)" style="animation: dash-flow 4.5s linear infinite 0.3s;"></path>

            <!-- Pinovi destinacija -->
            <g class="map-pin" data-dest="Chamonix / Les Orres" data-country="Francuska" data-km="1580" data-ski="280 km staza" transform="translate(228, 243)">
                <circle r="14" fill="rgba(var(--ice-rgb),0.08)" style="animation: map-ping 2.4s ease-out infinite;"/>
                <circle r="6"  fill="rgba(var(--ice-rgb),0.18)" stroke="rgba(var(--ice-rgb),0.5)" stroke-width="1"/>
                <circle r="3"  fill="var(--ice)" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Cortina d'Ampezzo" data-country="Italija" data-km="1190" data-ski="140 km staza" transform="translate(310, 273)">
                <circle r="14" fill="rgba(var(--ice-rgb),0.08)" style="animation: map-ping 2.4s ease-out infinite 0.5s;"/>
                <circle r="6"  fill="rgba(var(--ice-rgb),0.18)" stroke="rgba(var(--ice-rgb),0.5)" stroke-width="1"/>
                <circle r="3"  fill="var(--ice)" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="St. Anton / Arlberg" data-country="Austrija" data-km="1025" data-ski="305 km staza" transform="translate(368, 226)">
                <circle r="14" fill="rgba(var(--ice-rgb),0.08)" style="animation: map-ping 2.4s ease-out infinite 1.0s;"/>
                <circle r="6"  fill="rgba(var(--ice-rgb),0.18)" stroke="rgba(var(--ice-rgb),0.5)" stroke-width="1"/>
                <circle r="3"  fill="var(--ice)" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Zermatt" data-country="Švajcarska" data-km="1350" data-ski="360 km staza" transform="translate(284, 238)">
                <circle r="14" fill="rgba(var(--ice-rgb),0.08)" style="animation: map-ping 2.4s ease-out infinite 1.5s;"/>
                <circle r="6"  fill="rgba(var(--ice-rgb),0.18)" stroke="rgba(var(--ice-rgb),0.5)" stroke-width="1"/>
                <circle r="3"  fill="var(--ice)" filter="url(#dotGlow)"/>
            </g>

            <!-- Beograd polazna tacka -->
            <g transform="translate(479, 300)">
                <circle r="18" fill="rgba(var(--ice-rgb),0.05)" style="animation: map-ping 2s ease-out infinite;"/>
                <circle r="10" fill="rgba(var(--ice-rgb),0.14)" stroke="rgba(var(--ice-rgb),0.6)" stroke-width="1.5"/>
                <circle r="5"  fill="var(--ice)" filter="url(#dotGlow)"/>
                <text y="-18" text-anchor="middle" font-family="'Outfit',sans-serif" font-size="9.5" font-weight="600" letter-spacing="1" fill="rgba(var(--ice-rgb),0.9)">BEOGRAD</text>
            </g>

            <text x="225" y="235" text-anchor="middle" font-family="'Outfit',sans-serif" font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">FR</text>
            <text x="310" y="315" text-anchor="middle" font-family="'Outfit',sans-serif" font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">IT</text>
            <text x="368" y="238" text-anchor="middle" font-family="'Outfit',sans-serif" font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">AT</text>
            <text x="480" y="318" text-anchor="middle" font-family="'Outfit',sans-serif" font-size="8" fill="rgba(var(--ice-rgb),0.35)" letter-spacing="1">RS</text>
        </svg>
    </div>
</section>

<!-- ============================================================
     6. RECENZIJE
     ============================================================ -->
<?php if (!empty($recenzije)): ?>
<section class="testimonials-section" id="utisci">
    <div class="testimonials-header reveal">
        <span class="section-eyebrow">Putnici o nama</span>
        <h2 class="section-heading">Pravi <span>Utisci</span></h2>
    </div>
    <div class="reviews-carousel reveal" id="reviewsCarousel">
        <button class="carousel-arrow prev" type="button" data-dir="-1" aria-label="Prethodni">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="10,3 5,8 10,13"/></svg>
        </button>
        <div class="reviews-track" id="reviewsTrack">
            <?php foreach ($recenzije as $i => $rev): ?>
            <div class="review-slide">
                <div class="review-card-main<?php echo $i === 0 ? ' active-slide' : ''; ?>">
                    <div class="review-stars">
                        <?php for ($s = 0; $s < (int)$rev['ocena']; $s++): ?><span class="star">★</span><?php endfor; ?>
                    </div>
                    <p class="review-text-main">"<?php echo htmlspecialchars($rev['tekst']); ?>"</p>
                    <div class="review-author">
                        <div class="review-avatar-main"><?php echo htmlspecialchars($rev['avatar']); ?></div>
                        <div>
                            <div class="review-meta-name"><?php echo htmlspecialchars($rev['ime']); ?></div>
                            <div class="review-meta-dest"><?php echo htmlspecialchars($rev['dest']); ?></div>
                            <div class="review-meta-date"><?php echo htmlspecialchars($rev['datum']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-arrow next" type="button" data-dir="1" aria-label="Sledeći">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,3 11,8 6,13"/></svg>
        </button>
        <div class="carousel-nav" id="carouselNav">
            <?php foreach ($recenzije as $i => $rev): ?>
                <button class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>" type="button" data-index="<?php echo (int)$i; ?>" aria-label="Recenzija <?php echo (int)$i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     KATALOG DESTINACIJA
     ============================================================ -->
<section class="catalog-section" id="katalog">
    <div class="catalog-header-new reveal">
        <span class="section-eyebrow">Snowbase Katalog</span>
        <h2 class="section-heading">Ski <span>Destinacije</span></h2>
        <p class="catalog-intro">
            Od Kopaonika do Zermatt-a — kompletan paket za svaku destinaciju: logistika, smeštaj, oprema i mapa staza.
        </p>
        <div class="section-divider"></div>
    </div>

    <div class="dest-grid">
        <?php foreach ($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&w=800&auto=format&fit=crop"
                     class="dest-img" alt="<?php echo htmlspecialchars($d['naziv']); ?>"
                     width="800" height="500" loading="lazy" decoding="async">
            </div>
            <div class="dest-body">
                <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                <p class="dest-desc">
                    <?php
                        $opis = htmlspecialchars($d['opis'] ?? '');
                        echo (strlen($opis) > 120) ? mb_substr($opis, 0, 115) . '...' : $opis;
                    ?>
                </p>
                <div class="dest-meta">
                    <div class="meta-item">
                        <span>Ukupno staza</span>
                        <strong><?php echo (int)($d['ukupno_staza_km'] ?? 0); ?> km</strong>
                    </div>
                    <div class="meta-item">
                        <span>Žičara</span>
                        <strong><?php echo (int)($d['broj_zicara'] ?? 0); ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Udaljenost</span>
                        <strong><?php echo (int)($d['distanca_od_bg_km'] ?? 0); ?> km</strong>
                    </div>
                </div>
                <a href="destinacija.php?id=<?php echo (int)$d['id']; ?>" class="btn-view">
                    Pogledaj detaljnije
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
/* Nav scroll efekat */
const mainNav = document.getElementById('main-nav');
window.addEventListener('scroll', () => {
    mainNav?.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

/* Reveal animacije */
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
document.querySelectorAll('.reveal').forEach((el) => {
    const grid = el.closest('.dest-grid');
    if (grid) {
        const idx = Array.from(grid.children).indexOf(el);
        el.style.transitionDelay = (idx * 0.08) + 's';
    }
    revealObs.observe(el);
});

/* ================================================================
   TOTAL BUDGET KALKULATOR — sve se računa lokalno iz preloaded JSON-a
   ================================================================ */
const BUDGET_DATA = <?php echo json_encode($budget_data, JSON_UNESCAPED_UNICODE); ?>;
const bgDest    = document.getElementById('bg-dest');
const bgOsobe   = document.getElementById('bg-osobe');
const bgDani    = document.getElementById('bg-dani');
const bgSubmit  = document.getElementById('bg-submit');
const bgResult  = document.getElementById('bg-result');
const bgDetalji = document.getElementById('bg-detalji');

function calcBudget() {
    const destId = parseInt(bgDest.value, 10);
    const osobe  = Math.max(1, parseInt(bgOsobe.value, 10) || 1);
    const dani   = Math.max(1, parseInt(bgDani.value, 10) || 1);

    if (!destId || !BUDGET_DATA[destId]) {
        bgDest.classList.add('is-error');
        setTimeout(() => bgDest.classList.remove('is-error'), 1800);
        return;
    }

    const d = BUDGET_DATA[destId];

    /* Smestaj: prosek × dani × osobe */
    const hotelTotal = d.hotel_prosek * dani * osobe;

    /* Ski pas Odrasli: izbor najbliže cene po danu */
    let pasPerOsoba = 0;
    if (dani <= 1)      pasPerOsoba = d.pas_1;
    else if (dani <= 3) pasPerOsoba = d.pas_3;
    else if (dani <= 6) pasPerOsoba = d.pas_6;
    else                pasPerOsoba = d.pas_6 + (d.pas_1 * (dani - 6)); /* aproksimacija */
    const pasTotal = pasPerOsoba * osobe;

    /* Prevoz auto: distanca × 2 × 0.06 EUR/km + putarina × 2 (po grupi, ne po osobi) */
    const gorivo  = d.distanca_km * 2 * 0.06;
    const putar   = d.putarina    * 2;
    const prevozTotal = gorivo + putar;

    const total = hotelTotal + pasTotal + prevozTotal;

    /* Prikazi rezultate sa animacijom */
    setVal('bg-r-hotel',  '€' + Math.round(hotelTotal));
    setVal('bg-r-pas',    '€' + Math.round(pasTotal));
    setVal('bg-r-prevoz', '€' + Math.round(prevozTotal));
    setVal('bg-r-total',  '€' + Math.round(total));

    document.getElementById('bg-r-hotel-h').textContent  = `prosek €${d.hotel_prosek.toFixed(0)}/noć × ${dani} × ${osobe}`;
    document.getElementById('bg-r-pas-h').textContent    = `Odrasli × ${osobe} × ${dani} dana`;
    document.getElementById('bg-r-prevoz-h').textContent = `auto · ${d.distanca_km * 2} km povratno`;
    document.getElementById('bg-r-total-h').textContent  = `okvirno za grupu od ${osobe} ${osobe === 1 ? 'osobe' : 'osoba'}`;

    bgDetalji.href = 'destinacija.php?id=' + destId;
    bgResult.classList.add('visible');
    bgResult.setAttribute('aria-hidden', 'false');
}
function setVal(id, val) {
    const el = document.getElementById(id);
    el.classList.add('updating');
    setTimeout(() => { el.textContent = val; el.classList.remove('updating'); }, 200);
}
bgSubmit.addEventListener('click', calcBudget);
[bgDest, bgOsobe, bgDani].forEach(el => {
    el.addEventListener('keydown', e => { if (e.key === 'Enter') calcBudget(); });
});
/* Auto-recalculate na promenu (UX kao u Booking-u) */
bgDest.addEventListener('change', () => { if (bgDest.value) calcBudget(); });

/* ================================================================
   EUROPE MAP — tooltip
   ================================================================ */
const tooltip = document.getElementById('mapTooltip');
const mapContainer = document.getElementById('europeMapContainer');
if (tooltip && mapContainer) {
    document.querySelectorAll('.map-pin').forEach(pin => {
        pin.style.cursor = 'pointer';
        pin.addEventListener('mouseenter', function() {
            const { dest, country, km, ski } = this.dataset;
            tooltip.innerHTML = `<div class="tt-dest">${dest}</div><div class="tt-country">${country}</div><div class="tt-km"><strong>${km} km</strong> od Beograda</div><div class="tt-ski">${ski}</div>`;
            const cr = mapContainer.getBoundingClientRect();
            const pr = this.getBoundingClientRect();
            tooltip.style.left = (pr.left + pr.width/2 - cr.left) + 'px';
            tooltip.style.top  = (pr.top  - cr.top - tooltip.offsetHeight - 18) + 'px';
            tooltip.classList.add('visible');
        });
        pin.addEventListener('mouseleave', () => tooltip.classList.remove('visible'));
        pin.addEventListener('click', () => document.getElementById('katalog').scrollIntoView({ behavior: 'smooth' }));
    });
}

/* ================================================================
   TESTIMONIALS CAROUSEL
   ================================================================ */
const carouselTotal = <?php echo (int)count($recenzije); ?>;
const carouselTrack = document.getElementById('reviewsTrack');
const carousel      = document.getElementById('reviewsCarousel');
if (carouselTotal > 0 && carousel) {
    let curr = 0, timer;
    function go(i) {
        curr = (i + carouselTotal) % carouselTotal;
        carouselTrack.style.transform = `translateX(-${curr * 100}%)`;
        document.querySelectorAll('.review-card-main').forEach((s, n) => s.classList.toggle('active-slide', n === curr));
        document.querySelectorAll('.carousel-dot').forEach((d, n) => d.classList.toggle('active', n === curr));
    }
    function move(dir) { go(curr + dir); }
    document.querySelectorAll('.carousel-arrow').forEach(btn => btn.addEventListener('click', () => move(parseInt(btn.dataset.dir, 10))));
    document.querySelectorAll('.carousel-dot').forEach(dot => dot.addEventListener('click', () => go(parseInt(dot.dataset.index, 10))));
    function startTimer() { timer = setInterval(() => move(1), 5000); }
    startTimer();
    carousel.addEventListener('mouseenter', () => clearInterval(timer));
    carousel.addEventListener('mouseleave', startTimer);
    let tx = 0;
    carousel.addEventListener('touchstart', e => { tx = e.changedTouches[0].clientX; }, { passive: true });
    carousel.addEventListener('touchend',   e => { const diff = tx - e.changedTouches[0].clientX; if (Math.abs(diff) > 40) move(diff > 0 ? 1 : -1); });
}
</script>

<?php include 'partials/footer.php'; ?>
