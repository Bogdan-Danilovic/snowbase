<?php
/**
 * admin/export-staza.php
 * Generise gotov INSERT blok iz tabele `staze_putanje`.
 * Iskopiras ga na kraj `seed.sql` — sledeci put kad reset-ujes bazu,
 * staze ce se automatski vratiti.
 *
 * Opciono ?download=1 — preuzima kao .sql fajl.
 */
require_once __DIR__ . '/../db.php';

$staze = $pdo->query("
    SELECT destinacija_id, tip_klasa, naziv, svg_d_putanja, duzina_km, redosled
    FROM staze_putanje
    ORDER BY destinacija_id, redosled, id
")->fetchAll();

/* Generisanje SQL bloka */
function sqlEscape(?string $v): string {
    if ($v === null) return 'NULL';
    return "'" . str_replace("'", "''", $v) . "'";
}

$sql  = "-- ============================================================================\n";
$sql .= "-- STAZE PUTANJE — automatski export iz admin/export-staza.php\n";
$sql .= "-- Generisano: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Ukupno staza: " . count($staze) . "\n";
$sql .= "-- ============================================================================\n\n";

if (empty($staze)) {
    $sql .= "-- (Nema staza u tabeli)\n";
} else {
    $sql .= "INSERT INTO `staze_putanje` (`destinacija_id`, `tip_klasa`, `naziv`, `svg_d_putanja`, `duzina_km`, `redosled`) VALUES\n";
    $redovi = [];
    foreach ($staze as $s) {
        $redovi[] = sprintf(
            "(%d, %s, %s, %s, %s, %d)",
            (int)$s['destinacija_id'],
            sqlEscape($s['tip_klasa']),
            sqlEscape($s['naziv']),
            sqlEscape($s['svg_d_putanja']),
            number_format((float)$s['duzina_km'], 1, '.', ''),
            (int)$s['redosled']
        );
    }
    $sql .= implode(",\n", $redovi) . ";\n";
}

/* Download opcija */
if (isset($_GET['download'])) {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="staze_putanje_export.sql"');
    echo $sql;
    exit;
}

/* Grupisano po destinaciji za prikaz statistike */
$po_destinaciji = [];
foreach ($staze as $s) {
    $did = (int)$s['destinacija_id'];
    if (!isset($po_destinaciji[$did])) $po_destinaciji[$did] = ['plava' => 0, 'crvena' => 0, 'crna' => 0];
    $po_destinaciji[$did][$s['tip_klasa']] = ($po_destinaciji[$did][$s['tip_klasa']] ?? 0) + 1;
}

$nazivi_dest = [];
$d = $pdo->query("SELECT id, naziv FROM destinacije ORDER BY id");
foreach ($d->fetchAll() as $row) $nazivi_dest[(int)$row['id']] = $row['naziv'];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snowbase admin — Export staza</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style 3.0.css">

    <style>
    .admin-shell {
        position: relative; z-index: 5;
        max-width: 1100px; margin: 0 auto; padding: 130px 32px 80px;
    }
    .admin-bar {
        display: flex; align-items: baseline; gap: 28px; flex-wrap: wrap;
        margin-bottom: 32px;
    }
    .admin-bar h1 {
        font-family: "Cormorant Garamond", serif;
        font-size: 44px; font-weight: 400; letter-spacing: -0.5px;
        color: var(--text-primary); margin: 0; line-height: 1;
    }
    .admin-bar h1 em { color: var(--ice); font-style: italic; font-weight: 300; }
    .admin-bar a {
        color: var(--text-secondary); text-decoration: none; font-size: 11px;
        letter-spacing: 2px; text-transform: uppercase; transition: color 0.2s;
    }
    .admin-bar a:hover { color: var(--ice); }

    .panel {
        background: var(--surface);
        border: 1px solid var(--border-card);
        border-radius: var(--r-md);
        padding: 24px;
        margin-bottom: 24px;
    }
    .panel h2 {
        font-family: "Outfit", sans-serif;
        font-size: 10px; letter-spacing: 3px; text-transform: uppercase;
        color: var(--ice); font-weight: 500; margin: 0 0 18px;
    }

    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }
    .stat-card {
        background: var(--abyss);
        border: 1px solid var(--border-subtle);
        border-radius: var(--r-sm);
        padding: 14px 16px;
    }
    .stat-card .ime {
        font-family: "Cormorant Garamond", serif;
        font-size: 18px; color: var(--text-primary); margin-bottom: 8px;
    }
    .stat-card .brojke { display: flex; gap: 14px; font-size: 13px; color: var(--text-secondary); }
    .stat-card .brojke span strong { color: var(--text-primary); font-weight: 500; }

    .pill-mini { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
    .pill-mini.plava  { background: #2382ff; }
    .pill-mini.crvena { background: #fa1a1a; }
    .pill-mini.crna   { background: #000; box-shadow: 0 0 0 1px var(--text-muted) inset; }

    .summary {
        font-family: "Cormorant Garamond", serif;
        font-size: 22px; color: var(--text-primary);
        margin-bottom: 24px;
    }
    .summary em { color: var(--ice); font-style: italic; }

    pre.sql-box {
        background: var(--void);
        border: 1px solid var(--border-subtle);
        border-radius: var(--r-sm);
        padding: 18px;
        color: var(--ice);
        font-family: "Courier New", monospace;
        font-size: 12px;
        line-height: 1.6;
        max-height: 480px;
        overflow: auto;
        white-space: pre;
        margin: 0;
        opacity: 0.92;
    }

    .btn-row { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
    .btn {
        padding: 13px 22px; border-radius: var(--r-pill); border: 1px solid transparent;
        cursor: pointer; font-size: 11px; font-weight: 500; letter-spacing: 2px;
        text-transform: uppercase; font-family: "Outfit", sans-serif;
        text-decoration: none; display: inline-block;
        transition: all 0.3s var(--ease-out);
    }
    .btn-primary {
        background: var(--ice); color: var(--void);
        box-shadow: var(--glow-ice);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 50px rgba(var(--ice-rgb), 0.5);
    }
    .btn-ghost {
        background: transparent; color: var(--text-primary);
        border-color: var(--border-card);
    }
    .btn-ghost:hover { background: var(--surface-hover); border-color: var(--border-hover); }

    .hint-box {
        background: var(--ice-soft);
        border: 1px solid rgba(var(--ice-rgb), 0.2);
        padding: 16px 18px;
        border-radius: var(--r-sm);
        font-size: 13px; line-height: 1.7; font-weight: 300;
        color: var(--text-primary);
    }
    .hint-box strong { color: var(--ice); font-weight: 500; }
    .hint-box code {
        background: var(--void); padding: 2px 7px; border-radius: 4px;
        font-family: "Courier New", monospace; color: var(--ice); font-size: 12px;
    }
    .hint-box ol { margin: 10px 0 0 22px; padding: 0; }
    .hint-box ol li { margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="fixed-bg"></div>

<?php include __DIR__ . '/../partials/nav.php'; ?>

<main class="admin-shell">

    <div class="admin-bar">
        <h1>Export <em>staza</em></h1>
        <a href="crtanje-staza.php">← nazad na crtanje</a>
    </div>

    <div class="summary">
        Trenutno u bazi: <em><?= count($staze) ?></em> staza
        kroz <em><?= count($po_destinaciji) ?></em> destinacij<?= count($po_destinaciji) === 1 ? 'u' : 'a' ?>
    </div>

    <?php if (!empty($po_destinaciji)): ?>
    <div class="panel">
        <h2>Statistika po destinaciji</h2>
        <div class="stats-grid">
            <?php foreach ($po_destinaciji as $did => $brojke): ?>
                <div class="stat-card">
                    <div class="ime"><?= htmlspecialchars($nazivi_dest[$did] ?? "Destinacija #$did") ?></div>
                    <div class="brojke">
                        <span><span class="pill-mini plava"></span> <strong><?= (int)$brojke['plava'] ?></strong> plave</span>
                        <span><span class="pill-mini crvena"></span> <strong><?= (int)$brojke['crvena'] ?></strong> crvene</span>
                        <span><span class="pill-mini crna"></span> <strong><?= (int)$brojke['crna'] ?></strong> crne</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Kako koristiti</h2>
        <div class="hint-box">
            <strong>Cilj:</strong> svaki put kad reset-uješ bazu, staze koje si nacrtao se automatski vrate.
            <ol>
                <li>Klikni <strong>Kopiraj SQL</strong> ili <strong>Preuzmi .sql fajl</strong></li>
                <li>Otvori <code>seed.sql</code> u editoru</li>
                <li>Pronađi liniju <code>-- NOTE: `staze_putanje` se popunjava kroz admin...</code></li>
                <li>Zameni je sadržajem koji si kopirao</li>
                <li>Sačuvaj fajl — gotovo</li>
            </ol>
        </div>
    </div>

    <div class="panel">
        <h2>Generisan SQL</h2>
        <pre class="sql-box" id="sqlBox"><?= htmlspecialchars($sql) ?></pre>
        <div class="btn-row">
            <button type="button" class="btn btn-primary" id="btnCopy">Kopiraj SQL</button>
            <a class="btn btn-ghost" href="?download=1">Preuzmi .sql fajl</a>
        </div>
    </div>

</main>

<script>
document.getElementById('btnCopy').addEventListener('click', async (e) => {
    const txt = document.getElementById('sqlBox').textContent;
    try {
        await navigator.clipboard.writeText(txt);
        e.target.textContent = '✓ Kopirano!';
        setTimeout(() => e.target.textContent = 'Kopiraj SQL', 2000);
    } catch (err) {
        alert('Kopiranje nije uspelo — koristi Preuzmi .sql fajl umesto toga.');
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
