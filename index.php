<?php
require_once 'db.php';

/* ---------------------------------------------------------------
   1. PODACI IZ BAZE
   --------------------------------------------------------------- */
try {
    $stmt = $pdo->query("
        SELECT d.*, s.ukupno_staza_km, s.broj_zicara
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
    ");
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/* ---------------------------------------------------------------
   2. TICKER + RECENZIJE — direktno iz baze (admin panel ih popunjava)
   Soft-fail: ako tabele jos ne postoje, sekcije se prikazuju prazne
   umesto da cela stranica pukne.
   --------------------------------------------------------------- */
$ticker_items = [];
$recenzije    = [];

try {
    $stmt = $pdo->prepare("
        SELECT tekst
        FROM ticker_items
        WHERE aktivan = 1
        ORDER BY redosled, id
    ");
    $stmt->execute();
    $ticker_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    /* tabela jos ne postoji — pokrenuti migracija.sql */
}

try {
    $stmt = $pdo->prepare("
        SELECT ime, avatar, tekst, ocena,
               datum_prikaza AS datum,
               lokacija       AS dest
        FROM recenzije
        WHERE na_homepage = 1
        ORDER BY redosled, id
        LIMIT 8
    ");
    $stmt->execute();
    $recenzije = $stmt->fetchAll();
} catch (PDOException $e) {
    /* tabela jos ne postoji */
}

/* ---------------------------------------------------------------
   3. KONFIGURACIJA STRANICE (za partials/head.php i nav.php)
   --------------------------------------------------------------- */
$page_title = 'Katalog Destinacija | Peak and Palm';
$nav_links  = [
    ['href' => 'index.php',     'label' => 'Katalog',       'active' => true],
    ['href' => '#route-finder', 'label' => 'Planiraj rutu'],
    ['href' => '#mapa',         'label' => 'Destinacije'],
    ['href' => '#katalog',      'label' => 'Skijalista'],
];

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
        <!--
            ZAMENA VIDEA:
            A) YouTube iframe:
               <iframe src="https://www.youtube.com/embed/VIDEO_ID?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playlist=VIDEO_ID&rel=0" allow="autoplay" frameborder="0"></iframe>
            B) Lokalni fajl:
               <video autoplay muted loop playsinline><source src="videos/hero-ski.mp4" type="video/mp4"></video>
            Trenutno: CSS fallback animacija ispod.
        -->
        <div class="vhero-fallback"></div>
    </div>

    <div class="vhero-overlay"></div>

    <div class="vhero-content">
        <div class="vhero-eyebrow">Premium Alpine Travel</div>
        <h1 class="vhero-title">
            Gde se završava<br>
            asfalt, tu počinje <em>avantura</em>
        </h1>
        <p class="vhero-subtitle">
            Direktno iz Beograda do najlepših ski centara Alpa.
            Organizacija, logistika i komfor — sve na jednom mestu.
        </p>
        <div class="vhero-cta-group">
            <a href="#katalog" class="vhero-cta-primary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="7" cy="7" r="6"/><polyline points="7,4 7,7 9,9"/>
                </svg>
                Istraži katalog
            </a>
            <a href="#route-finder" class="vhero-cta-secondary">
                Planiranje rute →
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
<div class="ticker-section">
    <div class="ticker-inner">
        <div class="ticker-label">
            <div class="ticker-label-dot"></div>
            Live
        </div>
        <div class="ticker-track">
            <div class="ticker-tape" id="tickerTape">
                <?php
                /* Dupliramo niz da ticker animacija (translateX -50%) izgleda kao beskonacna petlja */
                $all_items = array_merge($ticker_items, $ticker_items);
                foreach ($all_items as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     3. QUICK ROUTE FINDER
     ============================================================ -->
<section class="route-finder-section" id="route-finder">
    <div class="route-finder-wrap reveal">
        <div class="rf-header">
            <span class="rf-eyebrow">Planiranje puta</span>
            <h2 class="rf-title">Brzi kalkulator rute i troškova</h2>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label class="rf-label" for="rf-dest">Destinacija</label>
                <div class="rf-select-wrap">
                    <select class="rf-select" id="rf-dest">
                        <option value="">— Izaberite skijalište —</option>
                        <?php foreach ($destinacije as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>">
                                <?php echo htmlspecialchars($d['naziv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-osobe">Broj osoba</label>
                <input type="number" class="rf-input" id="rf-osobe"
                       min="1" max="9" value="2" placeholder="npr. 4">
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-dani">Trajanje (dana)</label>
                <input type="number" class="rf-input" id="rf-dani"
                       min="1" max="21" value="7" placeholder="npr. 7">
            </div>
            <div>
                <button type="button" class="rf-btn" id="rf-submit">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="7" y1="1" x2="7" y2="13"/>
                        <polyline points="3,9 7,13 11,9"/>
                    </svg>
                    Izračunaj
                </button>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     4. PARTNERS
     ============================================================ -->
<section class="partners-section" id="partneri">
    <div class="partners-label">Premium Partneri &amp; Preporučena Oprema</div>
    <div class="partners-track">
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">ELAN<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">Fischer<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije &amp; Oprema">
            <span class="partner-logo">Atomic<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Garderoba">
            <span class="partner-logo">Salomon<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Garderoba">
            <span class="partner-logo">Bogner<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">Völkl<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Prevoz">
            <span class="partner-logo">FlixBus<span class="logo-tag">®</span></span>
        </div>
    </div>
</section>

<!-- ============================================================
     5. INTERAKTIVNA MAPA EVROPE
     ============================================================ -->
<section class="europe-section" id="mapa">
    <div class="europe-header reveal">
        <span class="section-eyebrow">Logistika iz Beograda</span>
        <h2 class="section-heading">Naše <span>Destinacije</span> na mapi</h2>
    </div>

    <div class="europe-map-container reveal" id="europeMapContainer">

        <div class="map-tooltip" id="mapTooltip"></div>

        <svg viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg"
             style="background: rgba(7,12,24,0.6); border-radius: 20px; border: 1px solid rgba(0,229,255,0.08);">

            <defs>
                <filter id="lineGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
                <filter id="dotGlow" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="4" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
                <radialGradient id="bgGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%"   stop-color="#0a1428" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#04060d" stop-opacity="1"/>
                </radialGradient>
            </defs>

            <rect width="800" height="500" fill="url(#bgGrad)" rx="20"/>

            <!-- Simplified country shapes -->
            <path d="M 70 280 L 72 230 L 105 205 L 155 198 L 175 218 L 178 255 L 165 285 L 130 298 L 85 292 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <path d="M 55 250 L 70 230 L 72 280 L 58 282 Z"
                  fill="rgba(255,255,255,0.025)" stroke="rgba(255,255,255,0.06)" stroke-width="0.8"/>
            <path d="M 155 198 L 205 175 L 265 178 L 280 200 L 285 230 L 270 262 L 240 280 L 200 285 L 175 270 L 165 245 L 165 218 Z"
                  fill="rgba(255,255,255,0.032)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8" id="map-france"/>
            <path d="M 240 280 L 270 262 L 300 270 L 320 295 L 330 340 L 320 390 L 300 420 L 280 400 L 275 360 L 265 320 L 250 305 Z"
                  fill="rgba(255,255,255,0.032)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8" id="map-italy"/>
            <path d="M 265 220 L 295 215 L 310 228 L 305 248 L 280 252 L 265 242 Z"
                  fill="rgba(255,255,255,0.04)" stroke="rgba(255,255,255,0.09)" stroke-width="0.8" id="map-swiss"/>
            <path d="M 265 178 L 320 162 L 380 158 L 400 175 L 392 210 L 370 228 L 330 235 L 300 228 L 280 210 L 278 192 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8" id="map-germany"/>
            <path d="M 312 218 L 380 210 L 410 218 L 420 232 L 405 248 L 370 252 L 340 250 L 318 240 Z"
                  fill="rgba(255,255,255,0.040)" stroke="rgba(255,255,255,0.09)" stroke-width="0.8" id="map-austria"/>
            <path d="M 370 252 L 400 248 L 415 260 L 408 272 L 382 268 Z"
                  fill="rgba(255,255,255,0.035)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8"/>
            <path d="M 395 262 L 430 255 L 450 270 L 460 295 L 450 330 L 430 345 L 415 330 L 410 305 L 408 282 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <path d="M 445 272 L 490 265 L 510 280 L 508 320 L 490 345 L 462 342 L 448 320 L 450 295 Z"
                  fill="rgba(0, 229, 255, 0.06)" stroke="rgba(0,229,255,0.18)" stroke-width="1"/>
            <path d="M 410 220 L 460 212 L 490 225 L 498 248 L 480 262 L 445 265 L 418 255 L 410 238 Z"
                  fill="rgba(255,255,255,0.025)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <path d="M 320 162 L 390 150 L 430 158 L 445 172 L 440 198 L 405 200 L 370 198 L 335 192 Z"
                  fill="rgba(255,255,255,0.022)" stroke="rgba(255,255,255,0.06)" stroke-width="0.8"/>
            <path d="M 180 145 L 240 135 L 268 150 L 265 178 L 205 175 L 185 165 Z"
                  fill="rgba(255,255,255,0.020)" stroke="rgba(255,255,255,0.05)" stroke-width="0.8"/>
            <path d="M 50 450 L 800 450 L 800 500 L 50 500 Z"
                  fill="rgba(255,255,255,0.010)" stroke="rgba(255,255,255,0.04)" stroke-width="0.5"/>

            <text x="300" y="460" text-anchor="middle"
                  font-family="'Cormorant Garamond', serif" font-size="11" font-style="italic"
                  fill="rgba(255,255,255,0.14)" letter-spacing="2">Sredozemno more</text>

            <!-- Animovane linije Beograd → destinacije -->
            <path d="M 480 300 Q 380 200 230 245"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4s linear infinite;"></path>
            <path d="M 480 300 Q 420 260 310 275"
                  stroke="rgba(0,229,255,0.40)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3.5s linear infinite 0.6s;"></path>
            <path d="M 480 300 Q 445 255 370 228"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3s linear infinite 1.2s;"></path>
            <path d="M 480 300 Q 400 230 285 240"
                  stroke="rgba(0,229,255,0.35)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4.5s linear infinite 0.3s;"></path>

            <!-- Destination pins -->
            <g class="map-pin" data-dest="Chamonix / Les Orres" data-country="Francuska"
               data-km="1580" data-ski="280 km staza" transform="translate(228, 243)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Cortina d'Ampezzo" data-country="Italija"
               data-km="1190" data-ski="140 km staza" transform="translate(310, 273)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 0.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Innsbruck / Arlberg" data-country="Austrija"
               data-km="1025" data-ski="340 km staza" transform="translate(368, 226)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.0s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Zermatt / Davos" data-country="Švajcarska"
               data-km="1350" data-ski="360 km staza" transform="translate(284, 238)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- Beograd polazna tacka -->
            <g transform="translate(479, 300)">
                <circle r="18" fill="rgba(0,229,255,0.05)" style="animation: map-ping 2s ease-out infinite;"/>
                <circle r="10" fill="rgba(0,229,255,0.14)" stroke="rgba(0,229,255,0.6)" stroke-width="1.5"/>
                <circle r="5"  fill="#00e5ff" filter="url(#dotGlow)"/>
                <text y="-18" text-anchor="middle" font-family="'Outfit',sans-serif"
                      font-size="9.5" font-weight="600" letter-spacing="1"
                      fill="rgba(0,229,255,0.9)">BEOGRAD</text>
            </g>

            <text x="225" y="235" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">FR</text>
            <text x="310" y="315" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">IT</text>
            <text x="368" y="238" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">AT</text>
            <text x="480" y="318" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(0,229,255,0.35)" letter-spacing="1">RS</text>

        </svg>
    </div>
</section>

<!-- ============================================================
     6. TESTIMONIALS CAROUSEL
     ============================================================ -->
<section class="testimonials-section" id="utisci">
    <div class="testimonials-header reveal">
        <span class="section-eyebrow">Putnici o nama</span>
        <h2 class="section-heading">Pravi <span>Utisci</span></h2>
    </div>

    <div class="reviews-carousel reveal" id="reviewsCarousel">

        <button class="carousel-arrow prev" type="button" data-dir="-1" aria-label="Prethodni">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="10,3 5,8 10,13"/>
            </svg>
        </button>

        <div class="reviews-track" id="reviewsTrack">
            <?php foreach ($recenzije as $i => $rev): ?>
            <div class="review-slide">
                <div class="review-card-main<?php echo $i === 0 ? ' active-slide' : ''; ?>">
                    <div class="review-stars">
                        <?php for ($s = 0; $s < (int)$rev['ocena']; $s++): ?>
                            <span class="star">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="review-text-main">
                        "<?php echo htmlspecialchars($rev['tekst']); ?>"
                    </p>
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
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6,3 11,8 6,13"/>
            </svg>
        </button>

        <div class="carousel-nav" id="carouselNav">
            <?php foreach ($recenzije as $i => $rev): ?>
                <button class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>"
                        type="button" data-index="<?php echo (int)$i; ?>"
                        aria-label="Recenzija <?php echo (int)$i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     KATALOG GRID
     ============================================================ -->
<section class="catalog-section" id="katalog">

    <div class="catalog-header-new reveal">
        <span class="section-eyebrow">Explore the Slopes</span>
        <h2 class="section-heading">Katalog <span>Ski Destinacija</span></h2>
        <p class="catalog-intro">
            Izaberite destinaciju, pregledajte interaktivnu mapu staza i izračunajte troškove logistike iz Beograda.
        </p>
        <div class="section-divider"></div>
    </div>

    <div class="dest-grid">
        <?php foreach ($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop"
                     class="dest-img"
                     alt="<?php echo htmlspecialchars($d['naziv']); ?>"
                     width="800" height="500"
                     loading="lazy" decoding="async">
            </div>
            <div class="dest-body">
                <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                <p class="dest-desc">
                    <?php
                        $opis = htmlspecialchars($d['opis']);
                        echo (strlen($opis) > 120) ? substr($opis, 0, 115) . '...' : $opis;
                    ?>
                </p>
                <div class="dest-meta">
                    <div class="meta-item">
                        <span>Ukupno staza</span>
                        <strong><?php echo (int)($d['ukupno_staza_km'] ?? 0); ?> km</strong>
                    </div>
                    <div class="meta-item">
                        <span>Broj žičara</span>
                        <strong><?php echo (int)($d['broj_zicara'] ?? 0); ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Udaljenost</span>
                        <strong><?php echo (int)$d['distanca_od_bg_km']; ?> km</strong>
                    </div>
                </div>
                <a href="destinacija.php?id=<?php echo (int)$d['id']; ?>" class="btn-view">
                    Pogledaj Detaljnije
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
/* ================================================================
   NAV — scroll efekat
   ================================================================ */
const mainNav = document.getElementById('main-nav');
window.addEventListener('scroll', () => {
    mainNav.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

/* ================================================================
   REVEAL ANIMACIJA (IntersectionObserver)
   ================================================================ */
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

document.querySelectorAll('.reveal').forEach((el) => {
    /* Stepenasti delay za kartice u katalogu */
    const grid = el.closest('.dest-grid');
    if (grid) {
        const idx = Array.from(grid.children).indexOf(el);
        el.style.transitionDelay = (idx * 0.08) + 's';
    }
    revealObs.observe(el);
});

/* ================================================================
   ROUTE FINDER — redirect na destinacija.php sa parametrima
   ================================================================ */
const rfDest   = document.getElementById('rf-dest');
const rfOsobe  = document.getElementById('rf-osobe');
const rfDani   = document.getElementById('rf-dani');
const rfSubmit = document.getElementById('rf-submit');

function routeFinderGo() {
    if (!rfDest.value) {
        rfDest.classList.add('is-error');
        setTimeout(() => rfDest.classList.remove('is-error'), 1800);
        return;
    }
    const params = new URLSearchParams({
        id:    rfDest.value,
        osobe: rfOsobe.value || 2,
        dani:  rfDani.value  || 7,
    });
    window.location.href = `destinacija.php?${params.toString()}#logistika`;
}

rfSubmit.addEventListener('click', routeFinderGo);

/* Enter taster u poljima */
[rfDest, rfOsobe, rfDani].forEach(el => {
    el.addEventListener('keydown', e => { if (e.key === 'Enter') routeFinderGo(); });
});

/* ================================================================
   EUROPE MAP — tooltip
   ================================================================ */
const tooltip      = document.getElementById('mapTooltip');
const mapContainer = document.getElementById('europeMapContainer');

document.querySelectorAll('.map-pin').forEach(pin => {
    pin.style.cursor = 'pointer';

    pin.addEventListener('mouseenter', function() {
        const { dest, country, km, ski } = this.dataset;
        tooltip.innerHTML = `
            <div class="tt-dest">${dest}</div>
            <div class="tt-country">${country}</div>
            <div class="tt-km"><strong>${km} km</strong> od Beograda</div>
            <div class="tt-ski">${ski}</div>
        `;

        const containerRect = mapContainer.getBoundingClientRect();
        const pinRect       = this.getBoundingClientRect();
        const pinCenterX    = pinRect.left + pinRect.width / 2 - containerRect.left;
        const pinTopY       = pinRect.top  - containerRect.top;

        tooltip.style.left = pinCenterX + 'px';
        tooltip.style.top  = (pinTopY - tooltip.offsetHeight - 18) + 'px';
        tooltip.classList.add('visible');
    });

    pin.addEventListener('mouseleave', () => tooltip.classList.remove('visible'));

    /* Klik na pin -> scroll do kataloga */
    pin.addEventListener('click', () => {
        document.getElementById('katalog').scrollIntoView({ behavior: 'smooth' });
    });
});

/* ================================================================
   TESTIMONIALS CAROUSEL
   ================================================================ */
const carouselTotal = <?php echo (int)count($recenzije); ?>;
const carouselTrack = document.getElementById('reviewsTrack');
const carousel      = document.getElementById('reviewsCarousel');

if (carouselTotal > 0 && carousel) {
    let carouselCurrent = 0;
    let carouselTimer;

    function carouselGoTo(index) {
        carouselCurrent = (index + carouselTotal) % carouselTotal;
        carouselTrack.style.transform = `translateX(-${carouselCurrent * 100}%)`;

        document.querySelectorAll('.review-card-main').forEach((s, i) => {
            s.classList.toggle('active-slide', i === carouselCurrent);
        });
        document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === carouselCurrent);
        });
    }

    function carouselMove(dir) { carouselGoTo(carouselCurrent + dir); }

    /* Strelice + tackice (event delegation umesto inline onclick) */
    document.querySelectorAll('.carousel-arrow').forEach(btn => {
        btn.addEventListener('click', () => carouselMove(parseInt(btn.dataset.dir, 10)));
    });
    document.querySelectorAll('.carousel-dot').forEach(dot => {
        dot.addEventListener('click', () => carouselGoTo(parseInt(dot.dataset.index, 10)));
    });

    /* Auto-rotate */
    function startCarouselTimer() {
        carouselTimer = setInterval(() => carouselMove(1), 5000);
    }
    startCarouselTimer();

    carousel.addEventListener('mouseenter', () => clearInterval(carouselTimer));
    carousel.addEventListener('mouseleave', startCarouselTimer);

    /* Touch / swipe */
    let touchStartX = 0;
    carousel.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].clientX;
    }, { passive: true });
    carousel.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) carouselMove(diff > 0 ? 1 : -1);
    });
}
</script>

<?php include 'partials/footer.php'; ?>
