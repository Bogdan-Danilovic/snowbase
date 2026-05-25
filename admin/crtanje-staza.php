<?php
/**
 * admin/crtanje-staza.php — Snowbase admin
 * Interfejs za rucno crtanje ski staza preko mape destinacije.
 * Sve sto admin ucrta misem upisuje se kao SVG `d` u tabelu `staze_putanje`.
 *
 * Koristi postojeci style 3.0.css (klasa .staza sa fill:none) — bez crnih mrlja.
 */
require_once __DIR__ . '/../db.php';

$destinacije = $pdo->query("
    SELECT d.id, d.naziv, d.slug,
           (SELECT url FROM destinacije_slike
             WHERE destinacija_id = d.id AND tip = 'mapa_staza'
             ORDER BY redosled, id LIMIT 1) AS mapa_url
    FROM destinacije d
    ORDER BY d.naziv
")->fetchAll();

$selectedId = isset($_GET['dest']) ? (int)$_GET['dest'] : ($destinacije[0]['id'] ?? 0);
$selected = null;
foreach ($destinacije as $d) {
    if ((int)$d['id'] === $selectedId) { $selected = $d; break; }
}
if (!$selected && !empty($destinacije)) { $selected = $destinacije[0]; $selectedId = (int)$selected['id']; }

$postojece = [];
if ($selectedId) {
    $s = $pdo->prepare("SELECT id, tip_klasa, naziv, svg_d_putanja, duzina_km
                        FROM staze_putanje
                        WHERE destinacija_id = ?
                        ORDER BY redosled, id");
    $s->execute([$selectedId]);
    $postojece = $s->fetchAll();
}

$ok       = isset($_GET['ok'])    ? (int)$_GET['ok']    : 0;
$err      = isset($_GET['err'])   ? $_GET['err']        : '';
$obrisano = isset($_GET['del'])   ? (int)$_GET['del']   : 0;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#04060d">
    <title>Snowbase admin — Crtanje staza</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style 3.0.css">

    <style>
    /* ===== Admin layout — koristi sajt-CSS varijable (Alpine Noir / Snow.Base) ===== */
    .admin-shell {
        position: relative; z-index: 5;
        max-width: 1500px; margin: 0 auto; padding: 130px 32px 80px;
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
    .admin-bar h1 em {
        color: var(--ice); font-style: italic; font-weight: 300;
    }
    .admin-bar a {
        color: var(--text-secondary); text-decoration: none; font-size: 11px;
        letter-spacing: 2px; text-transform: uppercase;
        transition: color 0.2s;
    }
    .admin-bar a:hover { color: var(--ice); }

    .admin-grid {
        display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 32px;
    }
    @media (max-width: 1100px) { .admin-grid { grid-template-columns: 1fr; } }

    /* === Mapa canvas === */
    .crtez-canvas {
        position: relative;
        width: 100%;
        border-radius: var(--r-md);
        overflow: hidden;
        background: var(--abyss);
        border: 1px solid var(--border-card);
        box-shadow: var(--shadow-card);
        user-select: none;
    }
    .crtez-canvas img { display: block; width: 100%; height: auto; }
    .crtez-canvas svg.bg-layer,
    .crtez-canvas svg.draw-layer {
        position: absolute; inset: 0; width: 100%; height: 100%;
    }
    .crtez-canvas svg.bg-layer  { pointer-events: none; }
    .crtez-canvas svg.draw-layer { cursor: crosshair; }

    /* Garantovano nema crnih mrlja — bilo koja SVG putanja na canvasu je samo linija */
    .crtez-canvas svg path { fill: none !important; }

    /* Aktivna putanja: koristi .staza.plava/.crvena/.crna iz sajt-CSS-a, samo pojača */
    .crtez-canvas svg.draw-layer path.aktivna {
        opacity: 1;
        stroke-width: 3.5;
        filter: drop-shadow(0 0 6px currentColor);
    }

    /* === Paneli desno === */
    .admin-panel {
        background: var(--surface);
        border: 1px solid var(--border-card);
        border-radius: var(--r-md);
        padding: 24px;
    }
    .admin-panel + .admin-panel { margin-top: 20px; }
    .admin-panel h2 {
        font-family: "Outfit", sans-serif;
        font-size: 10px; letter-spacing: 3px; text-transform: uppercase;
        color: var(--ice); font-weight: 500; margin: 0 0 18px;
    }
    .admin-panel label {
        display: block; font-size: 10px; letter-spacing: 2px;
        text-transform: uppercase; color: var(--text-secondary); margin: 16px 0 6px;
        font-weight: 400;
    }
    .admin-panel input[type=text],
    .admin-panel input[type=number],
    .admin-panel select {
        width: 100%; padding: 12px 14px; font-size: 14px;
        background: var(--abyss); color: var(--text-primary);
        border: 1px solid var(--border-subtle);
        border-radius: var(--r-sm); font-family: "Outfit", sans-serif;
        font-weight: 300;
        transition: border-color 0.25s var(--ease-out), background 0.25s var(--ease-out);
    }
    .admin-panel input:focus,
    .admin-panel select:focus {
        outline: none;
        border-color: var(--border-hover);
        background: var(--surface-hover);
    }

    .btn-row { display: flex; gap: 10px; margin-top: 22px; }
    .btn-admin {
        padding: 13px 22px; border-radius: var(--r-pill); border: 1px solid transparent;
        cursor: pointer; font-size: 11px; font-weight: 500; letter-spacing: 2px;
        text-transform: uppercase; font-family: "Outfit", sans-serif;
        transition: all 0.3s var(--ease-out);
    }
    .btn-primary {
        background: var(--ice);
        color: var(--void);
        box-shadow: var(--glow-ice);
    }
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 0 50px rgba(var(--ice-rgb), 0.5);
    }
    .btn-primary:disabled { opacity: 0.3; cursor: not-allowed; box-shadow: none; }
    .btn-ghost {
        background: transparent; color: var(--text-primary);
        border-color: var(--border-card);
    }
    .btn-ghost:hover { background: var(--surface-hover); border-color: var(--border-hover); }
    .btn-danger {
        background: rgba(250, 26, 26, 0.1); color: #ff6b6b;
        border-color: rgba(250, 26, 26, 0.35);
        padding: 5px 11px; font-size: 14px; letter-spacing: 0;
    }
    .btn-danger:hover { background: rgba(250, 26, 26, 0.25); }

    .preview-d {
        width: 100%; min-height: 70px; padding: 12px; resize: vertical;
        background: var(--void); color: var(--ice);
        font-family: "Courier New", monospace; font-size: 11px;
        border: 1px solid var(--border-subtle); border-radius: var(--r-sm);
        word-break: break-all; opacity: 0.85;
    }

    .alert {
        padding: 14px 18px; border-radius: var(--r-sm); margin-bottom: 20px;
        font-size: 13px; font-weight: 400; letter-spacing: 0.3px;
    }
    .alert-ok {
        background: rgba(0, 229, 180, 0.08);
        color: #4ade80;
        border: 1px solid rgba(0, 229, 180, 0.3);
    }
    .alert-err {
        background: rgba(250, 26, 26, 0.08);
        color: #ff8a8a;
        border: 1px solid rgba(250, 26, 26, 0.3);
    }
    .hint-box {
        background: var(--ice-soft);
        border: 1px solid rgba(var(--ice-rgb), 0.2);
        color: var(--text-primary); padding: 14px 16px; border-radius: var(--r-sm);
        font-size: 13px; line-height: 1.6; font-weight: 300;
    }
    .hint-box strong { color: var(--ice); font-weight: 500; }

    .lista-staza { max-height: 360px; overflow: auto; padding-right: 4px; }
    .red-staze {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; border-radius: var(--r-sm); margin-bottom: 8px;
        background: var(--abyss);
        border: 1px solid var(--border-subtle);
        font-size: 13px; color: var(--text-primary); font-weight: 300;
        transition: border-color 0.2s, background 0.2s;
    }
    .red-staze:hover { border-color: var(--border-hover); background: var(--surface-hover); }
    .red-staze .pill {
        display: inline-block; width: 9px; height: 9px;
        border-radius: 50%; margin-right: 8px; vertical-align: middle;
    }
    .pill-plava  { background: #2382ff; box-shadow: 0 0 6px #2382ff; }
    .pill-crvena { background: #fa1a1a; box-shadow: 0 0 6px #fa1a1a; }
    .pill-crna   { background: #000;     box-shadow: 0 0 0 1px var(--text-muted) inset; }
    .red-staze small { color: var(--text-secondary); }

    .toolbar-top {
        display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 22px;
        align-items: flex-end;
    }
    .toolbar-top > div { flex: 1; min-width: 240px; }

    .legend-pills { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
    .legend-pill {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 5px 12px; border-radius: var(--r-pill); font-size: 10px;
        background: var(--surface);
        border: 1px solid var(--border-subtle);
        letter-spacing: 1.5px; text-transform: uppercase;
        color: var(--text-secondary); font-weight: 400;
    }
    .legend-pill .dot { width: 7px; height: 7px; border-radius: 50%; }
    </style>
</head>
<body>

<div class="fixed-bg"></div>

<?php include __DIR__ . '/../partials/nav.php'; ?>

<main class="admin-shell">

    <div class="admin-bar">
        <h1>Crtanje <em>ski staza</em></h1>
        <a href="index.php">← početna</a>
        <?php if ($selectedId): ?>
            <a href="../destinacija.php?id=<?= $selectedId ?>" target="_blank">otvori destinaciju ↗</a>
        <?php endif; ?>
        <a href="export-staza.php">export u SQL ↗</a>
    </div>

    <?php if ($ok): ?>
      <div class="alert alert-ok">Staza je uspešno sačuvana.</div>
    <?php endif; ?>
    <?php if ($obrisano): ?>
      <div class="alert alert-ok">Staza obrisana.</div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert alert-err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="admin-grid">

        <!-- LEVO: izbor + canvas -->
        <section>
            <div class="toolbar-top">
                <div>
                    <label for="destSelect">Destinacija</label>
                    <form method="GET" id="formDest">
                        <select name="dest" id="destSelect" onchange="document.getElementById('formDest').submit()">
                            <?php foreach ($destinacije as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= $d['id'] == $selectedId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['naziv']) ?><?= $d['mapa_url'] ? '' : '  (nema mapu!)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="legend-pills">
                        <span class="legend-pill"><span class="dot pill-plava"></span> plava (lako)</span>
                        <span class="legend-pill"><span class="dot pill-crvena"></span> crvena (srednje)</span>
                        <span class="legend-pill"><span class="dot pill-crna"></span> crna (teško)</span>
                    </div>
                </div>
                <div>
                    <div class="hint-box">
                        <strong>Kako se crta:</strong> klikni i drži levi taster miša preko mape, prevuci i pusti.
                        Postojeće staze su iscrtane providno radi orijentacije. Ikone na mapi (bolnica, WC, info)
                        ne brini — kod ih ne čita, samo ti svojom rukom obeležavaš staze.
                    </div>
                </div>
            </div>

            <?php if (!$selected || empty($selected['mapa_url'])): ?>
                <div class="alert alert-err">
                    Ova destinacija nema sliku mape (tip <code>mapa_staza</code> u <code>destinacije_slike</code>).
                </div>
            <?php else: ?>
                <div class="crtez-canvas" calls="canvas-mapa">
                    <img id="mapaImg" src="<?= htmlspecialchars('../' . $selected['mapa_url']) ?>" alt="mapa">

                    <!-- Pozadina: vec sacuvane staze (postojeca .staza klasa — fill:none, providno) -->
                    <svg class="bg-layer" viewBox="0 0 640 346" preserveAspectRatio="none" fill="none">
                        <?php foreach ($postojece as $p): ?>
                            <path class="staza <?= htmlspecialchars($p['tip_klasa']) ?>"
                                  fill="none"
                                  d="<?= htmlspecialchars($p['svg_d_putanja']) ?>"></path>
                        <?php endforeach; ?>
                    </svg>

                    <!-- Sloj za crtanje -->
                    <svg class="draw-layer" id="svgCrtez" viewBox="0 0 640 346" preserveAspectRatio="none" fill="none">
                        <path id="aktivnaPutanja" class="staza aktivna plava" fill="none" d=""></path>
                    </svg>
                </div>
            <?php endif; ?>
        </section>

        <!-- DESNO: forma + lista -->
        <aside>
            <div calls="nes">

                <div class="admin-panel">
                    <h2>Sačuvaj novu stazu</h2>
                    <form method="POST" action="sacuvaj-stazu.php" id="formSnimi">
                        <input type="hidden" name="destination_id" value="<?= (int)$selectedId ?>">

                        <label>Naziv staze</label>
                        <input type="text" name="trail_name" placeholder="npr. Crna kraljica" required maxlength="100">

                        <label>Težina</label>
                        <select name="tip_klasa" id="tipKlasa" required>
                            <option value="plava">Plava (lako)</option>
                            <option value="crvena">Crvena (srednje)</option>
                            <option value="crna">Crna (teško)</option>
                        </select>

                        <label>Dužina (km)</label>
                        <input type="number" name="duzina_km" step="0.1" min="0" value="0" required>

                        <input type="hidden" name="svg_path" id="svgPathInput">

                        <div class="btn-row">
                            <button type="submit" class="btn-admin btn-primary" id="btnSnimi" disabled>Sačuvaj</button>
                            <button type="button" class="btn-admin btn-ghost" id="btnObrisi">Očisti</button>
                        </div>

                        <label style="margin-top:18px;">Generisan SVG path</label>
                        <textarea class="preview-d" id="prikazPath" readonly placeholder="Tek nacrtaj nešto preko mape..."></textarea>
                    </form>
                </div>

                <div class="admin-panel">
                    <h2>Sačuvane staze (<?= count($postojece) ?>)</h2>
                    <?php if (empty($postojece)): ?>
                        <p style="color:#7a8aa3;font-size:13px;margin:0;">Još nema sačuvanih staza za ovu destinaciju.</p>
                    <?php else: ?>
                        <div class="lista-staza">
                            <?php foreach ($postojece as $p): ?>
                                <div class="red-staze">
                                    <span>
                                        <span class="pill pill-<?= htmlspecialchars($p['tip_klasa']) ?>"></span>
                                        <?= htmlspecialchars($p['naziv'] ?: '(bez imena)') ?>
                                        <small> · <?= number_format((float)$p['duzina_km'], 1, ',', '') ?> km</small>
                                    </span>
                                    <form method="POST" action="sacuvaj-stazu.php"
                                          onsubmit="return confirm('Obrisati ovu stazu?');"
                                          style="margin:0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <input type="hidden" name="destination_id" value="<?= (int)$selectedId ?>">
                                        <button type="submit" class="btn-admin btn-danger">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </aside>

    </div>
</main>

<script>
(function () {
    const svg = document.getElementById('svgCrtez');
    if (!svg) return;

    const path     = document.getElementById('aktivnaPutanja');
    const input    = document.getElementById('svgPathInput');
    const prikaz   = document.getElementById('prikazPath');
    const btnSnimi = document.getElementById('btnSnimi');
    const btnObrisi= document.getElementById('btnObrisi');
    const tipSel   = document.getElementById('tipKlasa');

    let crtam = false;
    let tacke = [];

    /* Sinhronizuj klasu aktivne path-i sa izabranom tezinom (boja po sajt-CSS-u) */
    function sinhKlasu() {
        path.setAttribute('class', 'staza aktivna ' + tipSel.value);
    }
    tipSel.addEventListener('change', sinhKlasu);
    sinhKlasu();

    function tackaIzDogadjaja(e) {
        const pt = svg.createSVGPoint();
        pt.x = e.clientX;
        pt.y = e.clientY;
        return pt.matrixTransform(svg.getScreenCTM().inverse());
    }

    function generisiD() {
        if (tacke.length === 0) return '';
        let d = `M ${tacke[0].x.toFixed(1)} ${tacke[0].y.toFixed(1)}`;
        for (let i = 1; i < tacke.length - 1; i++) {
            const sx = (tacke[i].x + tacke[i+1].x) / 2;
            const sy = (tacke[i].y + tacke[i+1].y) / 2;
            d += ` Q ${tacke[i].x.toFixed(1)} ${tacke[i].y.toFixed(1)} ${sx.toFixed(1)} ${sy.toFixed(1)}`;
        }
        if (tacke.length > 1) {
            const last = tacke[tacke.length - 1];
            d += ` L ${last.x.toFixed(1)} ${last.y.toFixed(1)}`;
        }
        return d;
    }

    function azuriraj() {
        const d = generisiD();
        path.setAttribute('d', d);
        input.value  = d;
        prikaz.value = d;
        btnSnimi.disabled = (d === '' || tacke.length < 2);
    }

    svg.addEventListener('mousedown', (e) => {
        e.preventDefault();
        crtam = true;
        tacke = [tackaIzDogadjaja(e)];
        azuriraj();
    });

    svg.addEventListener('mousemove', (e) => {
        if (!crtam) return;
        const p = tackaIzDogadjaja(e);
        const last = tacke[tacke.length - 1];
        if (Math.hypot(p.x - last.x, p.y - last.y) > 3) {
            tacke.push(p);
            azuriraj();
        }
    });

    window.addEventListener('mouseup', () => {
        if (!crtam) return;
        crtam = false;
        azuriraj();
    });

    btnObrisi.addEventListener('click', () => {
        tacke = [];
        azuriraj();
    });
})();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
