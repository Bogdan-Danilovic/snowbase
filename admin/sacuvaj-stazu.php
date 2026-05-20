<?php
/**
 * admin/sacuvaj-stazu.php
 * POST handler za snimanje i brisanje ski staza.
 * Upisuje u postojecu tabelu `staze_putanje`.
 */
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metoda nije dozvoljena.');
}

$destinationId = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT);
$action        = $_POST['action'] ?? 'create';

function redirect_back(int $destId, array $params = []): void {
    $params['dest'] = $destId;
    header('Location: crtanje-staza.php?' . http_build_query($params));
    exit;
}

if (!$destinationId) {
    redirect_back(0, ['err' => 'Nedostaje destination_id.']);
}

/* --------- BRISANJE --------- */
if ($action === 'delete') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) redirect_back($destinationId, ['err' => 'Nedostaje id staze.']);

    $stmt = $pdo->prepare("DELETE FROM staze_putanje WHERE id = ? AND destinacija_id = ?");
    $stmt->execute([$id, $destinationId]);
    redirect_back($destinationId, ['del' => 1]);
}

/* --------- KREIRANJE --------- */
$trailName = trim($_POST['trail_name']    ?? '');
$tipKlasa  = $_POST['tip_klasa']           ?? '';
$duzinaKm  = (float)($_POST['duzina_km']   ?? 0);
$svgPath   = trim($_POST['svg_path']       ?? '');

$dozvoljeneKlase = ['plava', 'crvena', 'crna'];

if ($trailName === '' || $svgPath === '' || !in_array($tipKlasa, $dozvoljeneKlase, true)) {
    redirect_back($destinationId, ['err' => 'Nedostaju ili nisu validni podaci (naziv, težina, path).']);
}

/* Sigurnost: dozvoli samo SVG path komande i brojeve, sprecava XSS u d-atributu */
if (!preg_match('/^[MLQCTSAZmlqctsaz0-9\s\.\,\-]+$/', $svgPath)) {
    redirect_back($destinationId, ['err' => 'Nevalidan SVG path.']);
}

/* Limit duzine kao zastita */
if (strlen($svgPath) > 20000) {
    redirect_back($destinationId, ['err' => 'SVG path je predugacak.']);
}

try {
    /* Sledeci redosled za ovu destinaciju */
    $rs = $pdo->prepare("SELECT COALESCE(MAX(redosled), 0) + 1 FROM staze_putanje WHERE destinacija_id = ?");
    $rs->execute([$destinationId]);
    $sledeciRedosled = (int)$rs->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO staze_putanje (destinacija_id, tip_klasa, naziv, svg_d_putanja, duzina_km, redosled)
        VALUES (:did, :tip, :naz, :path, :duz, :red)
    ");
    $stmt->execute([
        ':did'  => $destinationId,
        ':tip'  => $tipKlasa,
        ':naz'  => $trailName,
        ':path' => $svgPath,
        ':duz'  => $duzinaKm,
        ':red'  => $sledeciRedosled,
    ]);
} catch (PDOException $e) {
    redirect_back($destinationId, ['err' => 'Greška u bazi: ' . $e->getMessage()]);
}

redirect_back($destinationId, ['ok' => 1]);
