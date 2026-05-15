<?php
require_once 'db.php';

// Uzimamo ID destinacije iz URL-a (npr. destinacija.php?id=1)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
    // 1. Dohvatamo osnovne podatke o destinaciji i ski info (JOIN upit)
    $stmt = $pdo->prepare("
        SELECT d.*, s.ukupno_staza_km, s.plave_staze_km, s.crvene_staze_km, s.crne_staze_km, s.broj_zicara 
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $dest = $stmt->fetch();

    if (!$dest) {
        die("Destinacija nije pronadjena.");
    }

    // 2. Dohvatamo smestaj za ovu destinaciju
    $stmtSmestaj = $pdo->prepare("SELECT * FROM smestaj WHERE destinacija_id = ?");
    $stmtSmestaj->execute([$id]);
    $hoteli = $stmtSmestaj->fetchAll();

} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dest['naziv']); ?> | Peak and Palm Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- UNIKATNA PREMIJUM PALETA BOJA (Less AI look) --- */
        :root {
            --bg-deep: #050912;          /* Duboka, skoro crna noćno plava */
            --bg-panel: #0b111e;         /* Nijansu svetlija za panele */
            --bg-panel-hvr: #121929;     /* Hover panel */
            --text-main: #f3f4f6;        /* Skoro bela, meka za oči */
            --text-dim: #94a3b8;         /* Čelično siva za opise */
            --primary: #22d3ee;          /* Vibrantna tirkizna (Cyan) */
            --primary-dim: rgba(34, 211, 238, 0.1);
            --border: rgba(148, 163, 184, 0.1); /* Suptilna granica */
            
            --slope-blue: #38bdf8;
            --slope-red: #ef4444;
            --slope-black: #f1f5f9;
            --slope-park: #fb923c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; scroll-behavior: smooth; }

        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 200vh; /* Da bi imalo prostora za skrol */
        }

        /* --- BLURIVANA POZADINA --- */
        .fixed-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(to bottom, rgba(5, 9, 18, 0.8), var(--bg-deep)), 
                              url('https://images.unsplash.com/photo-1551524164-687a55dd1126?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            filter: blur(10px); /* Jaki blur za premijum osećaj */
            transform: scale(1.1); /* Da se ne vide ivice blura */
            z-index: -1;
        }

        nav {
            position: fixed; top: 0; width: 100%;
            padding: 20px 50px;
            background: rgba(5, 9, 18, 0.8);
            backdrop-filter: blur(10px);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
            z-index: 100;
        }
        nav a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 1.2rem; }
        nav a span { color: var(--primary); }
        .nav-links a { font-size: 0.9rem; margin-left: 25px; font-weight: 500; color: var(--text-dim); transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        /* =========================================
           SCROLL SEKCIJE (Glavna magija)
           ========================================= */
        
        .main-content { margin-top: 80px; }

        /* 1. HERO SEKCIJA (Fiksirana, nestaje na skrol) */
        .hero-section {
            position: fixed;
            top: 80px; left: 0; width: 100%; height: calc(100vh - 80px);
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px;
            padding: 60px;
            align-items: center;
            z-index: 10;
            transition: opacity 0.3s ease, transform 0.3s ease; /* Za gladak scroll efekat */
            will-change: transform, opacity;
        }

        /* Klasa koju JS dodaje kad se skroluje */
        .hero-section.scrolled {
            opacity: 0;
            transform: translateY(-50px) scale(0.95);
            pointer-events: none; /* Da ne smeta klikovima ispod */
        }

        .map-wrapper {
            background: rgba(0,0,0,0.4);
            border-radius: 20px; padding: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .resort-svg { width: 100%; height: auto; max-height: 500px; }
        
        /* SVG Interaktivnost */
        .staza { fill: none; stroke-width: 6; stroke-linecap: round; transition: 0.4s; opacity: 0.3; cursor: pointer; }
        .staza.active { opacity: 1; stroke-width: 12; filter: drop-shadow(0 0 15px currentColor); }
        
        .slope-item {
            background: var(--bg-panel); padding: 15px 20px;
            border-radius: 12px; border: 1px solid var(--border);
            margin-bottom: 12px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            transition: 0.3s;
        }
        .slope-item:hover { background: var(--bg-panel-hvr); border-color: var(--primary); transform: translateX(5px); }

        /* 2. CONTENT SEKCIJA (Scrolluje se PREKO Hero-a) */
        .scroll-content {
            position: relative;
            margin-top: 100vh; /* Počinje tek ispod Hero sekcije */
            background: var(--bg-deep);
            border-top: 1px solid var(--border);
            padding: 80px 60px;
            z-index: 20;
            box-shadow: 0 -20px 50px rgba(0,0,0,0.8);
        }

        .container-wide { max-width: 1300px; margin: 0 auto; }

        /* Animacija pojavljivanja elemenata (Scroll Reveal) */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .section-title { font-size: 2.2rem; margin-bottom: 40px; font-weight: 700; color: #fff; }
        .section-title span { color: var(--primary); }

        /* SMESTAJ GRID */
        .hotel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        
        .hotel-card {
            background: var(--bg-panel);
            border-radius: 16px; border: 1px solid var(--border);
            overflow: hidden;
            transition: 0.4s;
        }
        .hotel-card:hover { border-color: var(--primary); transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.6); }

        .hotel-img { width: 100%; height: 200px; object-fit: cover; }
        .hotel-body { padding: 25px; }
        .hotel-name { font-size: 1.4rem; font-weight: 600; color: #fff; margin-bottom: 10px; }
        .hotel-price { font-size: 1.6rem; color: #fff; font-weight: 700; border-top: 1px solid var(--border); padding-top: 15px; margin-top: 15px; }
        .hotel-price small { color: var(--text-dim); font-weight: 400; font-size: 0.9rem; }

        /* LOGISTIKA KALKULATOR */
        .logistika-panel {
            background: var(--bg-panel);
            padding: 40px; border-radius: 16px; border: 1px solid var(--border);
            margin-top: 60px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
            align-items: center;
        }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; color: var(--text-dim); font-size: 0.9rem; margin-bottom: 8px; }
        .input-group input {
            width: 100%; padding: 15px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border); border-radius: 8px;
            color: #fff; font-size: 1rem;
            transition: 0.3s;
        }
        .input-group input:focus { outline: none; border-color: var(--primary); background: rgba(0,0,0,0.5); }

        .result-box {
            background: rgba(34, 211, 238, 0.05);
            padding: 30px; border-radius: 12px; border: 1px solid rgba(34, 211, 238, 0.2);
            text-align: center;
        }
        .result-box span { color: var(--text-dim); font-size: 0.9rem; display: block; }
        .result-box strong { font-size: 2.5rem; color: var(--primary); font-weight: 700; margin-top: 5px; display: block; }

        .btn-calc {
            width: 100%; padding: 15px; background: var(--primary);
            color: var(--bg-deep); border: none; border-radius: 8px;
            font-weight: 600; font-size: 1rem; cursor: pointer;
            transition: 0.3s;
        }
        .btn-calc:hover { background: #fff; transform: translateY(-2px); }

    </style>
</head>
<body>

<div class="fixed-bg"></div>

<nav>
    <a href="index.php">Peak<span>&</span>Palm</a>
    <div class="nav-links">
        <a href="index.php">Katalog</a>
        <a href="#smestaj">Smestaj</a>
        <a href="#logistika">Logistika</a>
    </div>
</nav>

<div class="main-content">

    <section class="hero-section" id="hero">
        <div class="map-wrapper">
            <svg class="resort-svg" viewBox="0 0 200 150">
                <path style="stroke: rgba(255,255,255,0.1); stroke-width: 2; fill: none;" d="M10,140 L70,30 L120,80 L160,20 L190,140" />
                <path id="path-blue" class="staza slope-blue" style="stroke: var(--slope-blue);" d="M120,80 Q100,100 75,115 T30,140" />
                <path id="path-red" class="staza slope-red" style="stroke: var(--slope-red);" d="M160,20 Q130,60 120,80 T90,140" />
                <path id="path-black" class="staza slope-black" style="stroke: var(--slope-black);" d="M70,30 Q85,65 110,140" />
            </svg>
        </div>

        <div class="info-side">
            <span style="color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; font-weight: 600;">
                <?php echo htmlspecialchars($dest['drzava']); ?>
            </span>
            <h1 style="font-size: 3.5rem; margin-bottom: 10px; font-weight: 700;"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p style="color: var(--text-dim); margin-bottom: 30px; line-height: 1.6; max-width: 500px;">
                <?php echo htmlspecialchars($dest['opis']); ?>
            </p>

            <div class="interactive-list">
                <div class="slope-item" onmouseenter="toggleMap('path-blue', true)" onmouseleave="toggleMap('path-blue', false)">
                    <div>Plave staze (Lagane)</div>
                    <strong style="color: var(--slope-blue);"><?php echo $dest['plave_staze_km']; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-red', true)" onmouseleave="toggleMap('path-red', false)">
                    <div>Crvene staze (Srednje)</div>
                    <strong style="color: var(--slope-red);"><?php echo $dest['crvene_staze_km']; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-black', true)" onmouseleave="toggleMap('path-black', false)">
                    <div>Crne staze (Teske)</div>
                    <strong style="color: var(--slope-black);"><?php echo $dest['crne_staze_km']; ?> km</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="scroll-content">
        <div class="container-wide">

            <div class="reveal" id="smestaj">
                <h2 class="section-title">Premijum <span>Smestaj</span></h2>
                <div class="hotel-grid">
                    <?php foreach($hoteli as $h): ?>
                    <div class="hotel-card">
                        <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop" class="hotel-img" alt="Hotel">
                        <div class="hotel-body">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p style="color: var(--text-dim); font-size: 0.9rem;">
                                Kategorija: <?php echo $h['zvezdice']; ?> zvezdice. Kapacitet do <?php echo $h['kapacitet_osoba']; ?> osobe.
                            </p>
                            <div class="hotel-price">
                                €<?php echo $h['cena_po_noci_eur']; ?> <small>/ noc po osobi</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reveal logistika-panel" id="logistika" style="margin-top: 100px;">
                <div class="calc-inputs">
                    <h2 class="section-title" style="margin-bottom: 20px;">Kalkulator <span>Logistike</span></h2>
                    <p style="color: var(--text-dim); margin-bottom: 30px;">
                        Izracunajte okvirni trosak puta iz Beograda do <?php echo htmlspecialchars($dest['naziv']); ?>.
                    </p>
                    
                    <div class="input-group">
                        <label>Broj putnika u vozilu</label>
                        <input type="number" id="in-putnici" value="4" min="1" max="8">
                    </div>
                    
                    <div class="input-group">
                        <label>Prosecna potrosnja (L/100km)</label>
                        <input type="number" id="in-potrosnja" value="7.0" step="0.1">
                    </div>
                    
                    <button class="btn-calc" onclick="izracunajTrosak()">Izracunaj trosak</button>
                </div>

                <div class="calc-results reveal">
                    <div class="stat-item" style="margin-bottom: 15px;">
                        <p>Udaljenost (u jednom smeru)</p>
                        <strong><?php echo (int)$dest['distanca_od_bg_km']; ?> km</strong>
                    </div>
                    <div class="stat-item" style="margin-bottom: 25px;">
                        <p>Prosecna putarina (u jednom smeru)</p>
                        <strong>€<?php echo (float)$dest['prosecna_putarina_eur']; ?></strong>
                    </div>
                    <div class="result-box">
                        <span>Ukupni trosak puta <strong>po osobi</strong> (povratna):</span>
                        <strong id="rezultat-cena">€--.--strong>
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>

<script>
    // 1. JS ZA SVG INTERAKTIVNOST (Hover)
    function toggleMap(id, isActive) {
        const el = document.getElementById(id);
        if(isActive) el.classList.add('active');
        else el.classList.remove('active');
    }

    // 2. JS ZA SCROLL EFEKTE (Hero sekcija i Reveal elemenata)
    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        const hero = document.getElementById('hero');
        const reveals = document.querySelectorAll('.reveal');

        // A) Sklanjanje Hero sekcije
        if (scrolled > 100) {
            hero.classList.add('scrolled');
        } else {
            hero.classList.remove('scrolled');
        }

        // B) Reveal elemenata na skrol (Intersection Observer alternativa za jednostavnost)
        reveals.forEach(el => {
            const windowHeight = window.innerHeight;
            const elementTop = el.getBoundingClientRect().top;
            const elementVisible = 150;

            if (elementTop < windowHeight - elementVisible) {
                el.classList.add('visible');
            }
        });
    });

    // 3. JS ZA KALKULATOR LOGISTIKE
    function izracunajTrosak() {
        // Podaci iz baze (prebaceni u JS preko PHP-a)
        const distanca = <?php echo (int)$dest['distanca_od_bg_km']; ?>;
        const putarina = <?php echo (float)$dest['prosecna_putarina_eur']; ?>;
        const cenaGoriva = 1.65; // Fiksna cena goriva u EUR za potrebe simulacije

        // Podaci iz inputa
        const putnici = parseInt(document.getElementById('in-putnici').value) || 1;
        const potrosnja = parseFloat(document.getElementById('in-potrosnja').value) || 7.0;

        // Racunica (povratna karta = distanca * 2)
        const ukupnoLitara = (distanca * 2 / 100) * potrosnja;
        const trosakGoriva = ukupnoLitara * cenaGoriva;
        const trosakPutarine = putarina * 2;

        const ukupnoPoOsobi = (trosakGoriva + trosakPutarine) / putnici;

        // Ispis rezultata
        document.getElementById('rezultat-cena').innerText = '€' + ukupnoPoOsobi.toFixed(2);
        
        // Animacija rezultata
        document.querySelector('.calc-results').classList.add('visible');
    }
</script>

</body>
</html>