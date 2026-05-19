<?php
/**
 * index.php — Snowbase katalog
 * Hero, live ticker, rich kalkulator, partneri, mapa Evrope,
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
   2. MAPA EVROPE — Yandex Maps (Kosovo prikazano kao deo Srbije)
   ============================================================ */
$map_destinations = [];
foreach ($destinacije as $d) {
    if ($d['lat'] === null || $d['lng'] === null) continue;
    $map_destinations[] = [
        'id'     => (int)$d['id'],
        'naziv'  => $d['naziv'],
        'zemlja' => $d['zemlja'],
        'km'     => (int)$d['distanca_od_bg_km'],
        'staze'  => (int)$d['ukupno_staza_km'],
        'lat'    => (float)$d['lat'],
        'lng'    => (float)$d['lng'],
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
            <a href="#rcalc" class="vhero-cta-secondary">
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
     3. RICH KALKULATOR (partials/calculator.php)
     ============================================================ -->
<?php include 'partials/calculator.php'; ?>

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
     5. MAPA EVROPE — Yandex (Kosovo prikazano kao deo Srbije)
     ============================================================ -->
<section class="ymap-section" id="mapa">
    <div class="ymap-header reveal">
        <span class="section-eyebrow">Logistika iz Beograda</span>
        <h2 class="section-heading">Naše <span>Destinacije</span> na mapi</h2>
        <p class="ymap-subtitle">Realna geografska mapa sa svih 8 ski destinacija</p>
    </div>

    <div class="ymap-container reveal">
        <div id="ymap"></div>
        <div class="ymap-hint" id="ymapHint">Klikni mapu za zoom · prevuci za pomeranje</div>
    </div>
</section>

<!--
    Yandex Maps JS API
    NAPOMENA: za production preporučujemo registraciju besplatnog API key-a
    na developer.tech.yandex.ru (limit 25.000 zahteva/dan). Bez ključa mapa
    radi za development sa watermark-om.
    Locale 'en_US' — labele na engleskom (univerzalno); za ćirilične nazive
    promenite u 'ru_RU'.
-->
<script src="https://api-maps.yandex.ru/2.1/?lang=en_US" defer></script>

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
    </div>

    <div class="dest-grid">
        <?php foreach ($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="Slike/<?php echo htmlspecialchars($d['slug']); ?>/hero.jpg"
                     class="dest-img" alt="<?php echo htmlspecialchars($d['naziv']); ?>"
                     width="800" height="500" loading="lazy" decoding="async"
                     onerror="this.src='https://images.unsplash.com/photo-1551524559-8af4e6624178?q=80&amp;w=800&amp;auto=format&amp;fit=crop'">
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
   YANDEX MAP — Kosovo se prikazuje kao deo Srbije
   ================================================================ */
const MAP_DEST = <?php echo json_encode($map_destinations, JSON_UNESCAPED_UNICODE); ?>;
const BEOGRAD  = [44.8176, 20.4633];

/* Čekaj da se Yandex API učita (defer + ymaps.ready) */
function initYandexMap() {
    if (typeof ymaps === 'undefined' || !ymaps.ready) {
        /* Skripta još nije gotova — pokušaj ponovo */
        setTimeout(initYandexMap, 100);
        return;
    }
    ymaps.ready(() => {
        const map = new ymaps.Map('ymap', {
            center: [46.8, 12.0],
            zoom: 5,
            controls: ['zoomControl'],
            /* yandex#map = standard street map, biće dark-inverted preko CSS-a */
            type: 'yandex#map'
        }, {
            suppressMapOpenBlock: true,
            yandexMapDisablePoiInteractivity: true
        });
        map.behaviors.disable('scrollZoom');

        /* Prvi klik aktivira scroll zoom + sakriva hint */
        const hint = document.getElementById('ymapHint');
        let hintRemoved = false;
        map.events.add('click', () => {
            if (hintRemoved) return;
            map.behaviors.enable('scrollZoom');
            hint?.classList.add('hidden');
            hintRemoved = true;
        });

        /* Custom pin layout — cyan ice glow */
        const DestPin = ymaps.templateLayoutFactory.createClass(
            '<div class="ymap-pin"><span class="ymap-pin-ping"></span><span class="ymap-pin-core"></span></div>'
        );
        const BgPin = ymaps.templateLayoutFactory.createClass(
            '<div class="ymap-pin ymap-pin-bg">' +
                '<span class="ymap-pin-ping"></span>' +
                '<span class="ymap-pin-core"></span>' +
                '<span class="ymap-pin-label">BEOGRAD</span>' +
            '</div>'
        );

        /* Beograd marker */
        const bgMarker = new ymaps.Placemark(BEOGRAD, {
            balloonContent: '<strong>Beograd</strong><br>Polazna tačka'
        }, {
            iconLayout: BgPin,
            iconShape: { type: 'Circle', coordinates: [0, 0], radius: 14 },
            hideIconOnBalloonOpen: false
        });
        map.geoObjects.add(bgMarker);

        /* Destinacije + linije */
        MAP_DEST.forEach((d, i) => {
            /* Linija od Beograda do destinacije */
            const line = new ymaps.Polyline([BEOGRAD, [d.lat, d.lng]], {}, {
                strokeColor: '#00e5ff',
                strokeWidth: 1.4,
                strokeOpacity: 0.5,
                strokeStyle: { style: 'dash', size: 7, space: 5 }
            });
            map.geoObjects.add(line);

            /* Pin */
            const pin = new ymaps.Placemark([d.lat, d.lng], {
                hintContent:
                    '<div class="ymap-hint-card">' +
                        '<strong>' + d.naziv + '</strong>' +
                        '<span>' + d.zemlja + '</span>' +
                        '<small>' + d.km + ' km od Beograda · ' + d.staze + ' km staza</small>' +
                    '</div>'
            }, {
                iconLayout: DestPin,
                iconShape: { type: 'Circle', coordinates: [0, 0], radius: 14 },
                hideIconOnBalloonOpen: false,
                hintCloseTimeout: 100
            });
            pin.events.add('click', () => {
                window.location.href = 'destinacija.php?id=' + d.id;
            });
            map.geoObjects.add(pin);
        });
    });
}

/* Pokreni inicijalizaciju kad je strana spremna */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initYandexMap);
} else {
    initYandexMap();
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
