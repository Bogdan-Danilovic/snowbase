<?php
require_once 'db.php';

try {
    // Čitamo sve destinacije iz baze i spajamo ih sa ski informacijama za brzi prikaz na karticama
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
    <title>Katalog Destinacija | Peak and Palm</title>
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

        body { background-color: var(--bg-deep); color: var(--text-main); overflow-x: hidden; }

        /* Isti zamućeni background kao na destinacijama */
        .fixed-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(to bottom, rgba(5, 9, 18, 0.85), var(--bg-deep)), 
                              url('pozadina zima.jpeg');
            background-size: cover; background-position: center; filter: blur(10px); transform: scale(1.1); z-index: -1;
        }

        /* Navigacija preslikana sa destinacija.php */
        nav {
            position: fixed; top: 0; width: 100%; padding: 20px 50px;
            background: rgba(5, 9, 18, 0.8); backdrop-filter: blur(10px);
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); z-index: 100;
        }
        nav a { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 1.2rem; }
        nav a span { color: var(--primary); }
        .nav-links a { font-size: 0.9rem; margin-left: 25px; font-weight: 500; color: var(--text-dim); transition: 0.3s; }
        .nav-links a.active, .nav-links a:hover { color: var(--primary); }

        .container { max-width: 1200px; margin: 120px auto 60px auto; padding: 0 20px; }

        .catalog-header { text-align: center; margin-bottom: 50px; }
        .catalog-header p { color: var(--text-dim); max-width: 600px; margin: 10px auto 0 auto; line-height: 1.6; }

        /* Mreža sa karticama */
        .dest-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        /* Izgled kartice usklađen sa stilom hotela iz šablona */
        .dest-card {
            background: var(--bg-panel);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .dest-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(34, 211, 238, 0.1);
        }

        .dest-img-container { position: relative; width: 100%; height: 200px; overflow: hidden; }
        .dest-img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s ease; }
        .dest-card:hover .dest-img { transform: scale(1.05); }

        .dest-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .dest-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 10px; color: var(--text-main); }
        .dest-desc { color: var(--text-dim); font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1; }

        /* Brze informacije na dnu kartice */
        .dest-meta {
            display: flex; justify-content: space-between;
            border-top: 1px solid var(--border); padding-top: 15px; margin-bottom: 20px;
        }
        .meta-item span { display: block; font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; }
        .meta-item strong { font-size: 1.1rem; color: var(--primary); }

        /* Dugme koje vodi na dinamički šablon destinacije */
        .btn-view {
            display: block; text-align: center; width: 100%; padding: 12px;
            background: rgba(34, 211, 238, 0.06); color: var(--primary);
            border: 1px solid rgba(34, 211, 238, 0.2); border-radius: 8px;
            font-weight: 600; text-decoration: none; transition: 0.2s;
        }
        .dest-card:hover .btn-view { background: var(--primary); color: var(--bg-deep); border-color: var(--primary); }

        /* Reveal efekat za elegantno pojavljivanje */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.6s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>

<div class="fixed-bg"></div>

<nav>
    <a href="index.php">Peak<span>&</span>Palm</a>
    <div class="nav-links">
        <a href="index.php" class="active">Katalog</a>
    </nav>
</nav>

<div class="container">
    
    <header class="catalog-header">
        <span style="color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; font-weight: 600;">Explore the Slopes</span>
        <h1 style="font-size: 2.5rem; font-weight: 700; margin-top: 5px;">Katalog Zimskih Destinacija</h1>
        <p>Izaberite željenu destinaciju, pregledajte interaktivnu mapu skijališta u realnom vremenu i izračunajte troškove logistike direktno iz Beograda.</p>
    </header>

    <div class="dest-grid">
        <?php foreach($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop" class="dest-img" alt="Ski Resort">
            </div>
            
            <div class="dest-body">
                <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                <p class="dest-desc">
                    <?php 
                        // Skraćujemo opis ako je predugačak za karticu
                        $opis = htmlspecialchars($d['opis']);
                        echo (strlen($opis) > 120) ? substr($opis, 0, 115) . '...' : $opis;
                    ?>
                </p>
                
                <div class="dest-meta">
                    <div class="meta-item">
                        <span>Ukupno staza</span>
                        <strong><?php echo $d['ukupno_staza_km'] ?? '0'; ?> km</strong>
                    </div>
                    <div class="meta-item">
                        <span>Broj žičara</span>
                        <strong><?php echo $d['broj_zicara'] ?? '0'; ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Udaljenost</span>
                        <strong><?php echo (int)$d['distanca_od_bg_km']; ?> km</strong>
                    </div>
                </div>

                <a href="destinacija.php?id=<?php echo $d['id']; ?>" class="btn-view">Pogledaj Detaljnije</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    // Reveal animacija pri učitavanju i skrolovanju
    window.addEventListener('DOMContentLoaded', () => {
        const reveals = document.querySelectorAll('.reveal');
        reveals.forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('visible');
            }, index * 100); // Pravi fini "fading cascade" efekat kartica
        });
    });
</script>

</body>
</html>