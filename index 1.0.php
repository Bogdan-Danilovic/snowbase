<?php
require_once 'db.php';

try {
    // Vučemo destinacije i osnovne ski informacije za kartice
    $stmt = $pdo->query("
        SELECT d.*, s.ukupno_staza_km, s.broj_zicara 
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
    ");
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peak & Palm | Premium Zimske Destinacije</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style 3.0.css">

    <style>
        /* Specifični stilovi samo za index stranicu */
        .hero-home {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        .hero-home h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(3rem, 8vw, 6rem);
            font-weight: 600;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, var(--ice) 50%, #fff 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: text-shimmer 6s linear infinite;
        }
        .hero-home p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin-bottom: 40px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 60px 0 100px;
        }
        .feature-box {
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border-card);
            padding: 40px 30px;
            border-radius: var(--r-md);
            text-align: center;
            transition: 0.3s;
        }
        .feature-box:hover {
            border-color: rgba(0, 229, 255, 0.3);
            transform: translateY(-5px);
            background: rgba(0, 229, 255, 0.02);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .catalog-section {
            padding-bottom: 120px;
        }

        footer {
            background: var(--void);
            border-top: 1px solid var(--border-subtle);
            padding: 60px 40px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="cursor-dot" id="cursorDot"></div>
<div class="cursor-ring" id="cursorRing"></div>

<div class="fixed-bg"></div>

<nav>
    <a href="index.php" class="logo">Peak<span>&</span>Palm</a>
    <div class="nav-links">
        <a href="#katalog" class="active">Katalog Destinacija</a>
        <a href="#o-nama">O platformi</a>
    </div>
</nav>

<div class="main-content">

    <header class="hero-home reveal">
        <span class="section-eyebrow" style="color: var(--ice); margin-bottom: 15px;">Smart Tourism Platform</span>
        <h1>Zima na<br>višem nivou</h1>
        <p>Istražite najlepša evropska skijališta uz interaktivne mape staza, real-time vremenske uslove i precizan inženjerski proračun putnih troškova.</p>
        <a href="#katalog" class="btn btn-primary" style="padding: 15px 35px; font-size: 1rem;">Otvori Katalog ↓</a>
    </header>

    <div class="container" id="o-nama" style="padding-top: 20px; padding-bottom: 20px;">
        <div class="features-grid reveal">
            <div class="feature-box">
                <span class="feature-icon">🗺️</span>
                <h3 style="margin-bottom: 10px; font-weight: 500;">Interaktivne Mape</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Precizni vektorski prikazi staza sa mogućnošću filtriranja i vizuelizacije dužina ruta u realnom vremenu.</p>
            </div>
            <div class="feature-box">
                <span class="feature-icon">⚙️</span>
                <h3 style="margin-bottom: 10px; font-weight: 500;">Napredna Logistika</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Optimizacija putovanja uz integrisane kalkulatore za izračunavanje potrošnje goriva, putarina i organizaciju prevoza.</p>
            </div>
            <div class="feature-box">
                <span class="feature-icon">⛷️</span>
                <h3 style="margin-bottom: 10px; font-weight: 500;">Dinamički Cene</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Centralizovan sistem za pametnu kalkulaciju ski-pasa, smeštaja i rentiranja tehničke opreme na klik.</p>
            </div>
        </div>
    </div>

    <section class="catalog-section" id="katalog">
        <div class="container" style="padding-top: 0;">
            <div class="catalog-header reveal">
                <span>Odaberite lokaciju</span>
                <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 3rem; margin-top: 10px;">Sve <strong>Destinacije</strong></h2>
            </div>

            <div class="dest-grid">
                <?php foreach($destinacije as $d): ?>
                <div class="dest-card reveal">
                    <div class="dest-img-container">
                        <img src="Slike/les_orres_mapa.jpg" class="dest-img" alt="<?php echo htmlspecialchars($d['naziv']); ?>">
                    </div>
                    
                    <div class="dest-body">
                        <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                        <p class="dest-desc">
                            <?php 
                                $opis = htmlspecialchars($d['opis']);
                                echo (strlen($opis) > 110) ? substr($opis, 0, 105) . '...' : $opis;
                            ?>
                        </p>
                        
                        <div class="dest-meta">
                            <div class="meta-item">
                                <span>Staze</span>
                                <strong><?php echo $d['ukupno_staza_km'] ?? '0'; ?> <small style="font-size: 0.8rem;">km</small></strong>
                            </div>
                            <div class="meta-item">
                                <span>Žičare</span>
                                <strong><?php echo $d['broj_zicara'] ?? '0'; ?></strong>
                            </div>
                            <div class="meta-item">
                                <span>Ruta</span>
                                <strong><?php echo (int)$d['distanca_od_bg_km']; ?> <small style="font-size: 0.8rem;">km</small></strong>
                            </div>
                        </div>

                        <a href="destinacija.php?id=<?php echo $d['id']; ?>" class="btn-view">Otvori Destinaciju</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</div>

<footer>
    <div class="logo" style="margin-bottom: 15px; font-size: 1.2rem;">Peak<span>&</span>Palm</div>
    <p>Softversko rešenje za organizaciju turizma i logistike zimskih destinacija.</p>
    <p style="margin-top: 20px; opacity: 0.5;">&copy; <?php echo date("Y"); ?> Sva prava zadržana.</p>
</footer>

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

    document.querySelectorAll('a, button, .feature-box, .dest-card').forEach(el => {
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

    /* ---- Scroll Reveal & Nav BG ---- */
    window.addEventListener('scroll', onScroll, { passive: true });

    function onScroll() {
        const scrolled = window.scrollY;
        const nav  = document.querySelector('nav');

        nav?.classList.toggle('scrolled', scrolled > 40);

        document.querySelectorAll('.reveal').forEach(el => {
            if (el.getBoundingClientRect().top < window.innerHeight - 90) {
                el.classList.add('visible');
            }
        });
    }

    // Trigger na startu
    window.addEventListener('DOMContentLoaded', () => {
        onScroll();
        setTimeout(onScroll, 120);
    });
</script>

</body>
</html>