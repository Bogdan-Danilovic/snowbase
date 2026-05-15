<?php
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
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
    <title><?php echo htmlspecialchars($dest['naziv']); ?> | Peak and Palm</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #050912;
            --bg-panel: #0b111e;
            --bg-panel-hvr: #121929;
            --text-main: #f3f4f6;
            --text-dim: #94a3b8;
            --primary: #22d3ee;
            --border: rgba(148, 163, 184, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; scroll-behavior: smooth; }

        body { background-color: var(--bg-deep); color: var(--text-main); overflow-x: hidden; min-height: 180vh; }

        .fixed-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(to bottom, rgba(5, 9, 18, 0.85), var(--bg-deep)), 
                              url('https://images.unsplash.com/photo-1551524164-687a55dd1126?q=80&w=2000&auto=format&fit=crop');
            background-size: cover; background-position: center; filter: blur(10px); transform: scale(1.1); z-index: -1;
        }

        nav {
            position: fixed; top: 0; width: 100%; padding: 20px 50px;
            background: rgba(5, 9, 18, 0.8); backdrop-filter: blur(10px);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); z-index: 100;
        }
        nav a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 1.2rem; }
        nav a span { color: var(--primary); }
        .nav-links a { font-size: 0.9rem; margin-left: 25px; font-weight: 500; color: var(--text-dim); transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        .main-content { margin-top: 80px; }

        .hero-section {
            position: fixed; top: 80px; left: 0; width: 100%; height: calc(100vh - 80px);
            display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; padding: 40px 60px;
            align-items: center; z-index: 10; transition: opacity 0.4s ease, transform 0.4s ease; will-change: transform, opacity;
        }

        .hero-section.scrolled { opacity: 0; transform: translateY(-40px) scale(0.96); pointer-events: none; }

        .prava-mapa-container {
            background: rgba(0,0,0,0.5); border-radius: 12px; padding: 10px;
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            overflow: hidden; position: relative;
        }
        
     /* Staze su stalno vidljive preko prave slike na 60% jacine */
        .staza { 
            fill: none; 
            stroke-width: 2; /* Smanjeno sa 5 na 2 za tanju osnovnu liniju */
            stroke-linecap: round; 
            opacity: 0.6; 
            transition: all 0.3s ease; 
            cursor: pointer; 
        }

        /* Kada se aktiviraju ili hoveruju, sada se podebljavaju na umereniju vrednost */
        .staza.active, .staza:hover { 
            opacity: 1; 
            stroke-width: 3; /* Smanjeno sa 10 na 5 da ne bude predebelo na hover */
            filter: drop-shadow(0 0 5px currentColor); 
        }
        .staza.plava { color: #38bdf8; stroke: #38bdf8; }
        .staza.crvena { color: #ef4444; stroke: #ef4444; }
        .staza.crna { color: #ffffff; stroke: #ffffff; }

        .slope-item {
            background: var(--bg-panel); padding: 15px 20px;
            border-radius: 12px; border: 1px solid var(--border);
            margin-bottom: 12px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            transition: 0.3s;
        }
        .slope-item:hover { background: var(--bg-panel-hvr); border-color: var(--primary); transform: translateX(5px); }

        /* SKROLJUCI SADRZAJ */
        .scroll-content {
            position: relative; margin-top: 100vh; background: var(--bg-deep);
            border-top: 1px solid var(--border); padding: 60px; z-index: 20;
            box-shadow: 0 -20px 50px rgba(0,0,0,0.9);
        }

        .container-wide { max-width: 1200px; margin: 0 auto; }

        .reveal { opacity: 0; transform: translateY(40px); transition: all 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        .section-title { font-size: 2rem; margin-bottom: 30px; font-weight: 700; }
        .section-title span { color: var(--primary); }

        .hotel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .hotel-card { background: var(--bg-panel); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; transition: 0.3s; }
        .hotel-card:hover { border-color: var(--primary); transform: translateY(-5px); }
        .hotel-img { width: 100%; height: 180px; object-fit: cover; }
        .hotel-body { padding: 20px; }
        .hotel-name { font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; }
        .hotel-price { font-size: 1.4rem; font-weight: 700; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }

        .logistika-panel {
            background: var(--bg-panel); padding: 40px; border-radius: 12px; border: 1px solid var(--border);
            margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;
        }

        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; color: var(--text-dim); font-size: 0.85rem; margin-bottom: 6px; }
        .input-group input {
            width: 100%; padding: 12px; background: rgba(0,0,0,0.4);
            border: 1px solid var(--border); border-radius: 6px; color: #fff; font-size: 0.95rem;
        }
        .input-group input:focus { outline: none; border-color: var(--primary); }

        .btn-calc {
            width: 100%; padding: 12px; background: var(--primary); color: var(--bg-deep);
            border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .btn-calc:hover { background: #fff; }

        .stat-item { padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px; margin-bottom: 12px; border: 1px solid var(--border); }
        .stat-item p { font-size: 0.8rem; color: var(--text-dim); }
        .result-box { background: rgba(34, 211, 238, 0.04); padding: 20px; border-radius: 8px; border: 1px solid rgba(34, 211, 238, 0.15); text-align: center; }
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
        <div class="prava-mapa-container">
            <svg viewBox="0 0 640 346" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg">
                
                <image href="les_orres_mapa.jpg" width="640" height="346" />

                <g>
                    <path id="path-blue" class="staza plava" transform="translate(0, 0) scale(1.0)" d="M 200 150 Q 300 220 400 300" />
                    
                    <path id="path-red" class="staza crvena" d="M306.5 123.5C295.3 123.9 292.167 118.667 292 116C295.5 110.5 294.5 111 289.5 103.5C297.1 100.7 303.5 97 305.5 90.5C330.3 92.5 330 90.5 330 84C340.5 74.5 341 74.5 333.5 68.5C335.9 60.1 327.833 58 324 58C319.5 58 317.403 63.6563 312 66.5C302.5 71.5 299.5 74 290.5 76C289.5 79 281.6 84.2 276 85C274 86.6667 268 92.5 268.5 101C265.5 104.5 262 104.7 256 107.5C254.5 109.333 251 114.2 249 119C246 121.333 238.9 127.6 234.5 134C233.667 135.333 231.8 138 231 138C230 138 205 134.5 180 140C175.455 141 170 144.5 167.5 147.5C164.5 154.5 163.1 157.8 149.5 159C134.5 163 126.5 167 122.5 169.5C114.5 169.833 99.3 173.3 102.5 184.5C104.833 188.167 108.7 195.5 105.5 195.5C101.5 195.5 99 201.5 100 201.5C101 201.5 108 200.5 108 207 M381.5 57.5C381.5 57.5 377.5 60.5 376 60.5C374.5 60.5 374 61 372 62.5C370 64 370 66 370 66C369.333 66.6667 367.5 69 363 69C359.167 71.5 360 69 357.5 70.5C356.167 70.5 353 71.8 351 77C348.833 80.8333 344.6 87.6 341 90C338.5 91.6667 331.9 97.1 325.5 105.5C325 106.333 324.1 108.5 324.5 110.5C325 113 316 114.5 312.5 121.5C311.833 122.667 310.1 125.4 308.5 127C306.5 129 311 129.5 303 139.5C302.333 142.5 300.5 149.1 298.5 151.5C296 154.5 306.5 151 296 164.5C296 165.5 290 177.5 290.5 184.5C290.667 188 289.6 195 284 195C277 195 275.5 192 272 204C268.5 216 260.287 214.832 253 222C245.027 229.843 237.5 238.5 233 242.5C228.5 246.5 226 246.5 202.5 267.5 M452 55.5C451.833 57 450.9 61.5 452.5 65.5M452.33 165.5C451.474 166.84 450.46 168.695 450.094 170.5C449.955 171.184 449.909 171.861 450 172.5C445.5 175.833 438.8 181 446 181C450 182.5 445.7 182.5 448.5 186.5C451.3 190.5 458.5 191 454.5 198C450.833 200.667 444 207.4 446 213C448.5 220 460 220.5 454.5 230.5C455.5 231.833 457.2 235.1 456 237.5C456.667 237 460 235.6 468 234C488.5 233.5 489 226 498 226C505.2 226 511.667 224 514 223C541.5 209.5 569.5 214 574.5 213C579.5 212 584 207 588.5 207C593 207 596 207.5 615.5 202C631.1 197.6 630 189.833 627.5 186.5C625.5 183.5 614 165.333 611.5 164.5C604 162 605 156.5 605.5 154.5C605.9 152.9 598.667 148.5 595 146.5C594 147 589.8 147.5 581 145.5C572.2 143.5 560.667 133.333 556 128.5C556 123 556 123.5 552.5 121.5C542.5 122.5 543 117 538.5 117C527.3 117.8 528 118 522.5 111C522.5 104.5 518.333 98.1667 516 96.5C516 86.1 512.5 84 510.5 84C498.5 85 493.5 78 491 76C487.815 73.4517 484 73.3333 483 72.5C483.8 64.9 473.667 65 468.5 66C470.167 64.6667 473.6 61.6 474 60C474.5 58 462.5 56.6778 461 56.6778C459.8 56.6778 454.5 55.8926 452 55.5C444.377 56.589 426.803 56.9921 413.5 56.6778C402.372 56.4149 393.183 56 392.5 56C390.066 57.1063 387.826 57.5496 386 57.6686M452.5 65.5C450.5 64.5 445 62.5 438.5 63M413.5 56.6778C413 59.3556 432 61.5 438.5 63L452.5 65.5M452.5 65.5C456.167 69.8333 463.4 78.7 467 79.5C469.667 81.5 476.4 85.2 482 84M452.33 165.5C456.16 165 462 166 465 167.5C468 169 478 170.5 483 167.5C488 164.5 497.5 159.5 500 159C502.5 158.5 506 154.5 518.5 151.5C531 148.5 533.5 143.5 536.5 139.805C555.5 130.61 532 131 525.5 131C519 131 516 125 515 124C514 123 504.5 120.5 497.5 116C490.5 111.5 488.5 112 468.5 106.5C454.5 96.9 474.333 88.8333 486 86C482.8 86 482 84.6667 482 84C471.667 84.6667 450.2 86.5 447 88.5C443 91 438 92.5 433 93.5C428 94.5 422 96.5 421 98.5C420 100.5 421.5 104.5 422.5 105.5C423.5 106.5 419.5 112 424 116C428.5 120 429.5 120.5 421 127.5C421 131 422 132 427.5 135C427.5 139 430.5 139 430.5 143.5M486 86C480 86.8333 466.8 89.5 462 93.5C456 98.5 460.59 99.5 455 99.5C449 99.5 447.5 100 443 103C441 105.5 442 106.5 441 111C437.5 114.5 435.5 116 435.5 124C434.833 126.333 431.933 130 429.5 130C428.5 132 428.5 136.972 429.5 139.805M359 159C365 149.8 368.5 150 377 150C381 146.5 374.5 143.5 377 139.805C388.5 133.61 384 128.5 384.5 121C384.9 115 381 110.167 379 108.5C357 113.5 361 104.839 370.5 103C375.667 102 387 101.7 391 108.5C396 117 394 116.5 397 119C400 121.5 404 125 406 127.5C408 130 405.5 134.5 414 138C416 147 424 145.5 430.5 149C435.5 151.333 443.7 154.5 448.5 154.5C452 156 457.8 160.1 453 164.5C452.802 164.784 452.572 165.121 452.33 165.5M359 159C362.333 161 367.9 166.5 363.5 172.5C359.333 172.5 348 174.2 336 181C334 191 334.3 190.5 305.5 206.5C303.167 206.667 298.6 207.7 299 210.5C299.5 214 305 212.5 300 216.5C295 217.333 284.3 220.3 281.5 225.5C279.833 227.167 276 230.7 274 231.5C246 242.7 246.5 242 203 268M299 208.5C307 202.1 305.5 201.591 302 200C296.5 197.5 292.5 181 296 170.5C298.238 167.983 303.709 166.769 309 166.247C313.168 165.836 317.224 165.853 319.5 166C329.5 160 350 158.833 359 159M386 57.6686C384.525 57.7646 383.319 57.649 382.5 57.5L386 57.6686ZM386 57.6686C388 58.779 391 62 384.5 67.5C378.5 73.5 375.5 76.5 375.5 80.5C373.5 84 374.5 87.8 366.5 91M306 123.5C308.5 122.5 308 124.5 311 124.5C313.4 124.5 326 121.167 332 119.5C334.167 120 339 120.7 341 119.5C343 118.3 342.167 114 341.5 112C347 108.5 350 100 350.5 99.5C355.3 96.7 362.167 92.6667 365.5 91M332 119.5C330.667 122.167 326.57 126.005 326 127.5C322 138 312.5 134.11 310 139.805C309 145 310 150 307 154.5C306 159 311.4 161.245 309 166.247M248.5 170.5C240.5 179 233.5 180.5 230.5 184C230.5 190 226.5 193.5 220.5 197.5L216 202.5C216 205.167 215 209.5 210.5 212C205.5 213.5 201.8 212.8 197 218M196 218C192 225.5 183 230 200.5 231.5C206 235.5 207.5 243.5 205 246C201.5 249.5 201 248.5 201.5 254C200 256 197 260.6 197 263M176.5 203.5C182 202.833 194.5 202.4 200.5 206C208 210.5 195.5 215 190 219.5C184.5 224 185 231.5 190 234C195 236.5 199 238.5 196 239C193 239.5 192.8 241 192.5 244C191.8 251 188 255.5 187 257.5M154.5 204.5C152.5 209.167 147 217.5 137 219.5C132.5 220.4 129.833 227 132.5 229.5C132.5 241 132.5 243.5 122 252C117.667 254.333 108.7 259.5 107.5 261.5C106 264 98 267 99 275C95.5 278 91.3333 281.833 87.5 282.5V293.5C85 295.833 80 300.8 80 302C80 303.5 79 309 81 310M450.094 170.5C449.062 170.167 446.4 170.1 444 172.5C441 175.5 441 178 438.5 178C436 178 434.5 180 434.5 182C434.5 184 436.5 186.5 433.5 188C430.5 189.5 429 190.5 428 193.5C427 196.5 420.5 200 417 201C413.5 202 410 199.5 406.5 202C403 204.5 407 203.5 397 204.5" />                    
                    <path id="path-black" class="staza crna" transform="translate(0, 0) scale(1.0)" d="M 320 80 L 310 180 L 290 290" />
                </g>
                
            </svg>
        </div>

        <div class="info-side">
            <span style="color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; font-weight: 600;">
                Katalog Destinacija
            </span>
            <h1 style="font-size: 3rem; margin-bottom: 10px; font-weight: 700;"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p style="color: var(--text-dim); margin-bottom: 25px; line-height: 1.6;">
                <?php echo htmlspecialchars($dest['opis']); ?>
            </p>

            <div class="interactive-list">
                <div class="slope-item" onmouseenter="toggleMap('path-blue', true)" onmouseleave="toggleMap('path-blue', false)">
                    <div>Plave staze (Grand Cabane)</div>
                    <strong style="color: #38bdf8;"><?php echo $dest['plave_staze_km']; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-red', true)" onmouseleave="toggleMap('path-red', false)">
                    <div>Crvene staze (Pramouton)</div>
                    <strong style="color: #ef4444;"><?php echo $dest['crvene_staze_km']; ?> km</strong>
                </div>
                <div class="slope-item" onmouseenter="toggleMap('path-black', true)" onmouseleave="toggleMap('path-black', false)">
                    <div>Crne staze (L'Horrible)</div>
                    <strong style="color: #ffffff;"><?php echo $dest['crne_staze_km']; ?> km</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="scroll-content">
        <div class="container-wide">

            <div class="reveal" id="smestaj">
                <h2 class="section-title">Raspoloziv <span>Smestaj</span></h2>
                <div class="hotel-grid">
                    <?php foreach($hoteli as $h): ?>
                    <div class="hotel-card">
                        <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop" class="hotel-img" alt="Hotel">
                        <div class="hotel-body">
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 15px;">
                                Kategorija: <?php echo $h['zvezdice']; ?> zvezdice. Kapacitet objekta je <?php echo $h['kapacitet_osoba']; ?> osobe.
                            </p>
                            <div class="hotel-price">
                                €<?php echo $h['cena_po_noci_eur']; ?> <small>/ noc</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reveal logistika-panel" id="logistika">
                <div>
                    <h2 class="section-title" style="margin-bottom: 15px;">Kalkulator <span>Logistike</span></h2>
                    <p style="color: var(--text-dim); margin-bottom: 25px;">Unesite parametre vaseg vozila za proracun troskova povratnog puta iz Beograda.</p>
                    
                    <div class="input-group">
                        <label>Broj putnika</label>
                        <input type="number" id="in-putnici" value="4" min="1">
                    </div>
                    <div class="input-group">
                        <label>Potrosnja goriva (L/100km)</label>
                        <input type="number" id="in-potrosnja" value="7.0" step="0.1">
                    </div>
                    <button class="btn-calc" onclick="izracunajTrosak()">Izracunaj trosak</button>
                </div>

                <div>
                    <div class="stat-item">
                        <p>Udaljenost do destinacije</p>
                        <strong><?php echo (int)$dest['distanca_od_bg_km']; ?> km</strong>
                    </div>
                    <div class="stat-item">
                        <p>Prosecna putarina</p>
                        <strong>€<?php echo (float)$dest['prosecna_putarina_eur']; ?></strong>
                    </div>
                    <div class="result-box">
                        <p style="font-size: 0.85rem; color: var(--text-dim);">Cena povratnog puta po osobi:</p>
                        <strong id="rezultat-cena" style="font-size: 2rem; color: var(--primary); margin-top: 5px; display: block;">€--.--</strong>
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>

<script>
    function toggleMap(id, isActive) {
        const el = document.getElementById(id);
        if(el) {
            if(isActive) el.classList.add('active');
            else el.classList.remove('active');
        }
    }

    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        const hero = document.getElementById('hero');
        const reveals = document.querySelectorAll('.reveal');

        if (scrolled > 80) {
            hero.classList.add('scrolled');
        } else {
            hero.classList.remove('scrolled');
        }

        reveals.forEach(el => {
            const windowHeight = window.innerHeight;
            const elementTop = el.getBoundingClientRect().top;
            if (elementTop < windowHeight - 100) {
                el.classList.add('visible');
            }
        });
    });

    function izracunajTrosak() {
        const distanca = <?php echo (int)$dest['distanca_od_bg_km']; ?>;
        const putarina = <?php echo (float)$dest['prosecna_putarina_eur']; ?>;
        const cenaGoriva = 1.65;

        const putnici = parseInt(document.getElementById('in-putnici').value) || 1;
        const potrosnja = parseFloat(document.getElementById('in-potrosnja').value) || 7.0;

        const ukupnoLitara = (distanca * 2 / 100) * potrosnja;
        const trosakGoriva = ukupnoLitara * cenaGoriva;
        const trosakPutarine = putarina * 2;

        const ukupnoPoOsobi = (trosakGoriva + trosakPutarine) / putnici;

        document.getElementById('rezultat-cena').innerText = '€' + ukupnoPoOsobi.toFixed(2);
    }
</script>

</body>
</html>