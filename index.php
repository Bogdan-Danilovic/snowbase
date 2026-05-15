<?php
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM destinacije");
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Greska pri ucitavanju baze: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peak and Palm - Katalog Destinacija</title>
    <style>
        :root {
            --booking-blue: #003580;
            --booking-light: #0071c2;
            --bg-gray: #f5f5f5;
            --text-dark: #262626;
            --border-color: #e7e7e7;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: sans-serif; }

        body { background-color: var(--bg-gray); color: var(--text-dark); }

        nav {
            background-color: var(--booking-blue);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        nav a { color: white; text-decoration: none; font-weight: 600; font-size: 1.2rem; }
        .nav-links a { font-size: 1rem; margin-left: 20px; font-weight: 500; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 0.7; }

        .hero { background-color: var(--booking-blue); color: white; padding: 60px 20px; text-align: center; }
        .hero h1 { font-size: 2.5rem; margin-bottom: 10px; }

        .katalog-container { 
            max-width: 1100px; 
            margin: -30px auto 50px; 
            padding: 0 20px; 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
            position: relative; 
            z-index: 10; 
        }

        .destinacija-card { 
            background: white; 
            border: 1px solid var(--border-color); 
            border-radius: 4px; 
            overflow: hidden; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
            display: flex; 
            flex-direction: column; 
        }

        .destinacija-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 12px 24px rgba(0,0,0,0.15); 
        }

        .card-img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.5s ease; }
        .destinacija-card:hover .card-img { transform: scale(1.05); }

        .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }

        .card-title { font-size: 1.2rem; font-weight: 700; color: var(--booking-light); margin-bottom: 5px; }
        .card-location { color: #6b6b6b; font-size: 0.9rem; margin-bottom: 15px; }

        .card-stats { margin-top: auto; margin-bottom: 15px; font-size: 0.9rem; }
        .stat-row { display: flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        .btn { 
            display: block; 
            width: 100%; 
            text-align: center; 
            background-color: var(--booking-light); 
            color: white; 
            padding: 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            transition: background-color 0.3s ease; 
        }

        .btn:hover { background-color: var(--booking-blue); }
    </style>
</head>
<body>

    <nav>
        <a href="index.php">Peak and Palm</a>
        <div class="nav-links">
            <a href="index.php">Katalog</a>
            <a href="#">Prijavi se</a>
        </div>
    </nav>

    <div class="hero">
        <h1>Pronadjite vasu sledecu stazu</h1>
        <p>Pretrazite najbolje zimske destinacije i organizujte putovanje.</p>
    </div>

    <div class="katalog-container">
        <?php foreach($destinacije as $dest): ?>
            <div class="destinacija-card">
                <img src="https://images.unsplash.com/photo-1605540436563-5bca919ae766?q=80&w=800&auto=format&fit=crop" alt="Planina" class="card-img">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($dest['naziv']); ?></h2>
                    <p class="card-location">Drzava: <?php echo htmlspecialchars($dest['drzava']); ?></p>
                    <div class="card-stats">
                        <div class="stat-row">
                            <span>Udaljenost:</span>
                            <strong><?php echo htmlspecialchars($dest['distanca_od_bg_km']); ?> km</strong>
                        </div>
                        <div class="stat-row">
                            <span>Putarina:</span>
                            <strong>EUR <?php echo htmlspecialchars($dest['prosecna_putarina_eur']); ?></strong>
                        </div>
                    </div>
                    <a href="destinacija.php?id=<?php echo $dest['id']; ?>" class="btn">Pogledajte ponudu</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>