<?php
/**
 * destinacija.php — Snowbase
 * Bogata DB-driven stranica destinacije: hero mapa, vreme, live status,
 * smestaj, prevoz, logistika kalkulator, ski pas, oprema & skola, recenzije.
 *
 * Svi podaci dolaze iz baze. URL: destinacija.php?id=X
 */
require_once 'db.php';

/* ============================================================
   1. OSNOVNI PODACI O DESTINACIJI
   ============================================================ */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
    $stmt = $pdo->prepare("
        SELECT d.*,
               s.ukupno_staza_km, s.plave_staze_km, s.crvene_staze_km, s.crne_staze_km, s.broj_zicara,
               gp.naziv AS prelaz_naziv
        FROM   destinacije d
        LEFT JOIN ski_info         s  ON s.destinacija_id = d.id
        LEFT JOIN granicni_prelazi gp ON gp.id = d.granicni_prelaz_id
        WHERE  d.id = ?
    ");
    $stmt->execute([$id]);
    $dest = $stmt->fetch();
    if (!$dest) { http_response_code(404); die("Destinacija nije pronađena."); }
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/* ============================================================
   2. POMOĆNA: safe fetch (vraca prazan niz ako tabela ne postoji)
   ============================================================ */
function fetchByDest(PDO $pdo, string $sql, int $id): array {
    try {
        $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetchAll();
    } catch (PDOException $e) { return []; }
}
function fetchOneByDest(PDO $pdo, string $sql, int $id) {
    try {
        $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetch();
    } catch (PDOException $e) { return null; }
}

/* ============================================================
   3. SVE POVEZANE TABELE
   ============================================================ */
$hoteli         = fetchByDest($pdo, "SELECT * FROM smestaj WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$vreme          = fetchOneByDest($pdo, "SELECT * FROM vreme_trenutno WHERE destinacija_id = ?", $id);
$prognoza       = fetchByDest($pdo, "SELECT * FROM vreme_prognoza WHERE destinacija_id = ? ORDER BY redosled, id LIMIT 7", $id);
$staze_status   = fetchOneByDest($pdo, "SELECT * FROM staze_status WHERE destinacija_id = ?", $id);
$ski_pas_cene   = fetchByDest($pdo, "SELECT kategorija, cena_1dan AS dan1, cena_2dana AS dan2, cena_3dana AS dan3, cena_5dana AS dan5, cena_6dana AS dan6, cena_7dana AS dan7 FROM ski_pas_cene WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$transport      = fetchByDest($pdo, "SELECT * FROM transport_opcije WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$oprema_paketi  = fetchByDest($pdo, "SELECT * FROM oprema_paketi WHERE destinacija_id = ? ORDER BY redosled, id", $id);
$skola_paketi   = fetchByDest($pdo, "SELECT * FROM skola_paketi WHERE destinacija_id = ? ORDER BY redosled, id", $id);

/* JSON dekodiranje za transport stavke i oprema includes */
foreach ($transport as &$t) {
    $t['stavke'] = $t['stavke_json'] ? (json_decode($t['stavke_json'], true) ?: []) : [];
}
unset($t);
foreach ($oprema_paketi as &$o) {
    $o['includes'] = $o['includes_json'] ? (json_decode($o['includes_json'], true) ?: []) : [];
}
unset($o);

/* Slike — grupisanje */
$sve_slike = fetchByDest($pdo, "SELECT tip, url, alt FROM destinacije_slike WHERE destinacija_id = ? ORDER BY tip, redosled, id", $id);
$slike = ['hero' => [], 'mapa_staza' => [], 'gallery' => []];
foreach ($sve_slike as $s) { $slike[$s['tip']][] = $s; }
$mapa_staza_url = $slike['mapa_staza'][0]['url'] ?? null;

/* SVG putanje — hardkodovane u kodu (trenutno samo za Les Orres, id=1) */
$staze_putanje_po_destinaciji = [
    1 => [
        [
            'tip_klasa'    => 'plava',
            'naziv'        => 'Plava staza',
            'duzina_km'    => 0,
            'svg_d_putanja'=> 'M370 102C364.167 104.167 351 110.4 345 118C339 125.6 318.167 126.5 308.5 126C293.5 128 278 134 272 141.5C269.833 143.333 270.3 146 291.5 150C296.5 150.943 298.9 154.3 300.5 157.5C302.333 155.333 305.8 154.4 311 164C322.167 166.667 342.9 173.3 336.5 178.5C328.5 185 335.5 185.5 330.5 190C325.5 194.5 309.5 194 300.5 196M298 211.5C290.5 212.833 275.6 217.2 276 224M171.5 198.5C184 199.5 210.5 202.8 216.5 208C226 209.5 255 210 260 211.5C265 213 273.085 216.585 276 219.5C276.556 221.5 276.329 222 276 224L204 265L233 258L245.5 252L263.5 247.5C267.667 247.5 279.5 246.9 293.5 244.5C311 241.5 308.5 236 313 236C316.6 236 326.167 230.667 330.5 228C369.3 216.8 384 211.5 396.5 211.5V206C390.167 203.667 375.5 197.4 367.5 191C359.5 184.6 352.167 187.667 349.5 190M185.5 229.5C181.5 224.5 175 225.5 171.5 225C168 224.5 159 224 141 216C126.6 209.6 114.333 208 110 208C99.3333 209.5 76.9 213.9 72.5 219.5C67 226.5 103.5 231 103 242C102.318 257 110 251.5 98 270C97.1573 271.299 96.9569 272.683 96.166 274C94.5218 276.738 90.3253 279.19 72.5 280C70.3333 282.5 63.4 287.7 53 288.5C40 289.5 33.5 294.5 30 296M228.5 139.5C250.833 137.833 288.05 140.507 254.5 157.5C232 168 218.5 161 196.5 168C182.5 172.455 154.5 168 151 174.5C156.333 177.167 169.9 183.1 181.5 185.5C196 188.5 196.5 190 176 195C158.5 199.268 181.5 198.662 188 200M98 308C111.5 297 112.5 299 116 299C119.5 299 135.5 292 156 276C172.4 263.2 180.5 263.333 182.5 265M156 276C153.167 276.167 146 274 137.5 268.5C122.5 263.5 106.832 268 96.166 274M362 226L413.5 236C415.5 237.833 417.2 242.1 408 244.5C404.5 247.5 411 249 393.5 250.5C379.5 251.7 362 252 355 252C351.333 251 342.5 250 336.5 254C330.5 258 254.5 254 235.5 256.8M415.5 242C419.089 242.232 424.591 243.685 426.009 247.5M339 293.5C336.167 291.333 331.7 286.6 336.5 285C341.3 283.4 342.167 278.333 342 276H345C359.5 272.5 357.5 270 363.5 265C368.3 261 378.167 261 382.5 261.5C383 262.667 388 265 404 265C424 265 419 265 425 254C426.466 251.312 426.632 249.178 426.009 247.5M426.009 247.5C431.672 244.333 445.2 238 454 238C462.8 238 460.667 246.333 458.5 250.5C458.667 255.333 457.5 265 451.5 265C445.5 265 433.667 262.667 428.5 261.5M486.5 112.5C482.833 115.833 474 123.2 468 126C460.5 129.5 461 133.5 458.5 134C456 134.5 449.5 134.5 444 139.5C439.6 143.5 433.5 145.833 431 146.5L430.5 148.5M216.5 237.5C220.167 239.167 223.892 240.195 211.5 243.5C204 245.5 223.5 249 205.5 252C205 252.333 204.3 253.5 205.5 255.5C207 258 207 258.5 204 259C201 259.5 198 263 198 264M469.5 160H460.5C459.167 161 456.7 163.3 457.5 164.5C458.3 165.7 455.5 165.667 454 165.5',
        ],
        [
            'tip_klasa'    => 'crvena',
            'naziv'        => 'Crvena staza',
            'duzina_km'    => 0,
            'svg_d_putanja'=> 'M306.5 123.5C295.3 123.9 292.167 118.667 292 116C295.5 110.5 294.5 111 289.5 103.5C297.1 100.7 303.5 97 305.5 90.5C330.3 92.5 330 90.5 330 84C340.5 74.5 341 74.5 333.5 68.5C335.9 60.1 327.833 58 324 58C319.5 58 317.403 63.6563 312 66.5C302.5 71.5 299.5 74 290.5 76C289.5 79 281.6 84.2 276 85C274 86.6667 268 92.5 268.5 101C265.5 104.5 262 104.7 256 107.5C254.5 109.333 251 114.2 249 119C246 121.333 238.9 127.6 234.5 134C233.667 135.333 231.8 138 231 138C230 138 205 134.5 180 140C175.455 141 170 144.5 167.5 147.5C164.5 154.5 163.1 157.8 149.5 159C134.5 163 126.5 167 122.5 169.5C114.5 169.833 99.3 173.3 102.5 184.5C104.833 188.167 108.7 195.5 105.5 195.5C101.5 195.5 99 201.5 100 201.5C101 201.5 108 200.5 108 207 M381.5 57.5C381.5 57.5 377.5 60.5 376 60.5C374.5 60.5 374 61 372 62.5C370 64 370 66 370 66C369.333 66.6667 367.5 69 363 69C359.167 71.5 360 69 357.5 70.5C356.167 70.5 353 71.8 351 77C348.833 80.8333 344.6 87.6 341 90C338.5 91.6667 331.9 97.1 325.5 105.5C325 106.333 324.1 108.5 324.5 110.5C325 113 316 114.5 312.5 121.5C311.833 122.667 310.1 125.4 308.5 127C306.5 129 311 129.5 303 139.5C302.333 142.5 300.5 149.1 298.5 151.5C296 154.5 306.5 151 296 164.5C296 165.5 290 177.5 290.5 184.5C290.667 188 289.6 195 284 195C277 195 275.5 192 272 204C268.5 216 260.287 214.832 253 222C245.027 229.843 237.5 238.5 233 242.5C228.5 246.5 226 246.5 202.5 267.5 M452 55.5C451.833 57 450.9 61.5 452.5 65.5M452.33 165.5C451.474 166.84 450.46 168.695 450.094 170.5C449.955 171.184 449.909 171.861 450 172.5C445.5 175.833 438.8 181 446 181C450 182.5 445.7 182.5 448.5 186.5C451.3 190.5 458.5 191 454.5 198C450.833 200.667 444 207.4 446 213C448.5 220 460 220.5 454.5 230.5C455.5 231.833 457.2 235.1 456 237.5C456.667 237 460 235.6 468 234C488.5 233.5 489 226 498 226C505.2 226 511.667 224 514 223C541.5 209.5 569.5 214 574.5 213C579.5 212 584 207 588.5 207C593 207 596 207.5 615.5 202C631.1 197.6 630 189.833 627.5 186.5C625.5 183.5 614 165.333 611.5 164.5C604 162 605 156.5 605.5 154.5C605.9 152.9 598.667 148.5 595 146.5C594 147 589.8 147.5 581 145.5C572.2 143.5 560.667 133.333 556 128.5C556 123 556 123.5 552.5 121.5C542.5 122.5 543 117 538.5 117C527.3 117.8 528 118 522.5 111C522.5 104.5 518.333 98.1667 516 96.5C516 86.1 512.5 84 510.5 84C498.5 85 493.5 78 491 76C487.815 73.4517 484 73.3333 483 72.5C483.8 64.9 473.667 65 468.5 66C470.167 64.6667 473.6 61.6 474 60C474.5 58 462.5 56.6778 461 56.6778C459.8 56.6778 454.5 55.8926 452 55.5C444.377 56.589 426.803 56.9921 413.5 56.6778C402.372 56.4149 393.183 56 392.5 56C390.066 57.1063 387.826 57.5496 386 57.6686M452.5 65.5C450.5 64.5 445 62.5 438.5 63M413.5 56.6778C413 59.3556 432 61.5 438.5 63L452.5 65.5M452.5 65.5C456.167 69.8333 463.4 78.7 467 79.5C469.667 81.5 476.4 85.2 482 84M452.33 165.5C456.16 165 462 166 465 167.5C468 169 478 170.5 483 167.5C488 164.5 497.5 159.5 500 159C502.5 158.5 506 154.5 518.5 151.5C531 148.5 533.5 143.5 536.5 139.805C555.5 130.61 532 131 525.5 131C519 131 516 125 515 124C514 123 504.5 120.5 497.5 116C490.5 111.5 488.5 112 468.5 106.5C454.5 96.9 474.333 88.8333 486 86C482.8 86 482 84.6667 482 84C471.667 84.6667 450.2 86.5 447 88.5C443 91 438 92.5 433 93.5C428 94.5 422 96.5 421 98.5C420 100.5 421.5 104.5 422.5 105.5C423.5 106.5 419.5 112 424 116C428.5 120 429.5 120.5 421 127.5C421 131 422 132 427.5 135C427.5 139 430.5 139 430.5 143.5M486 86C480 86.8333 466.8 89.5 462 93.5C456 98.5 460.59 99.5 455 99.5C449 99.5 447.5 100 443 103C441 105.5 442 106.5 441 111C437.5 114.5 435.5 116 435.5 124C434.833 126.333 431.933 130 429.5 130C428.5 132 428.5 136.972 429.5 139.805M359 159C365 149.8 368.5 150 377 150C381 146.5 374.5 143.5 377 139.805C388.5 133.61 384 128.5 384.5 121C384.9 115 381 110.167 379 108.5C357 113.5 361 104.839 370.5 103C375.667 102 387 101.7 391 108.5C396 117 394 116.5 397 119C400 121.5 404 125 406 127.5C408 130 405.5 134.5 414 138C416 147 424 145.5 430.5 149C435.5 151.333 443.7 154.5 448.5 154.5C452 156 457.8 160.1 453 164.5C452.802 164.784 452.572 165.121 452.33 165.5M359 159C362.333 161 367.9 166.5 363.5 172.5C359.333 172.5 348 174.2 336 181C334 191 334.3 190.5 305.5 206.5C303.167 206.667 298.6 207.7 299 210.5C299.5 214 305 212.5 300 216.5C295 217.333 284.3 220.3 281.5 225.5C279.833 227.167 276 230.7 274 231.5C246 242.7 246.5 242 203 268M299 208.5C307 202.1 305.5 201.591 302 200C296.5 197.5 292.5 181 296 170.5C298.238 167.983 303.709 166.769 309 166.247C313.168 165.836 317.224 165.853 319.5 166C329.5 160 350 158.833 359 159M386 57.6686C384.525 57.7646 383.319 57.649 382.5 57.5L386 57.6686ZM386 57.6686C388 58.779 391 62 384.5 67.5C378.5 73.5 375.5 76.5 375.5 80.5C373.5 84 374.5 87.8 366.5 91M306 123.5C308.5 122.5 308 124.5 311 124.5C313.4 124.5 326 121.167 332 119.5C334.167 120 339 120.7 341 119.5C343 118.3 342.167 114 341.5 112C347 108.5 350 100 350.5 99.5C355.3 96.7 362.167 92.6667 365.5 91M332 119.5C330.667 122.167 326.57 126.005 326 127.5C322 138 312.5 134.11 310 139.805C309 145 310 150 307 154.5C306 159 311.4 161.245 309 166.247M248.5 170.5C240.5 179 233.5 180.5 230.5 184C230.5 190 226.5 193.5 220.5 197.5L216 202.5C216 205.167 215 209.5 210.5 212C205.5 213.5 201.8 212.8 197 218M196 218C192 225.5 183 230 200.5 231.5C206 235.5 207.5 243.5 205 246C201.5 249.5 201 248.5 201.5 254C200 256 197 260.6 197 263M176.5 203.5C182 202.833 194.5 202.4 200.5 206C208 210.5 195.5 215 190 219.5C184.5 224 185 231.5 190 234C195 236.5 199 238.5 196 239C193 239.5 192.8 241 192.5 244C191.8 251 188 255.5 187 257.5M154.5 204.5C152.5 209.167 147 217.5 137 219.5C132.5 220.4 129.833 227 132.5 229.5C132.5 241 132.5 243.5 122 252C117.667 254.333 108.7 259.5 107.5 261.5C106 264 98 267 99 275C95.5 278 91.3333 281.833 87.5 282.5V293.5C85 295.833 80 300.8 80 302C80 303.5 79 309 81 310M450.094 170.5C449.062 170.167 446.4 170.1 444 172.5C441 175.5 441 178 438.5 178C436 178 434.5 180 434.5 182C434.5 184 436.5 186.5 433.5 188C430.5 189.5 429 190.5 428 193.5C427 196.5 420.5 200 417 201C413.5 202 410 199.5 406.5 202C403 204.5 407 203.5 397 204.5',
        ],
        [
            'tip_klasa'    => 'crna',
            'naziv'        => 'Crna staza',
            'duzina_km'    => 0,
            'svg_d_putanja'=> 'M485 86.5C487.5 88 492.4 91.4 492 93C491.5 95 483.5 103.5 487.5 112.5C491.5 121.5 496.5 128.5 498.5 128.5C500.5 128.5 497.5 135.5 497 137C496.5 138.5 494 144.5 497 146.5C500 148.5 491.5 155 489.5 155C487.9 155 485.833 157.333 485 158.5C484.167 158.5 482.3 158.8 481.5 160C480.5 161.5 481.5 165.5 478 168C474.5 170.5 478.905 173 475 173C469 173 463 174.5 469 182C470.333 183.167 471.511 187.872 463 190C457 191.5 457 190 460.5 195.5C460 197 457 201.1 449 205.5C449.333 207 450.4 210 452 210C454 210 447.5 213.5 449 214C450.5 214.5 457 219 457 219C457 219 478 224 458.5 226C457.333 228 456.6 232.3 463 233.5C469.4 234.7 460.667 236.667 455.5 237.5M378 149C377.667 150.333 380.5 153.4 394.5 155C396.167 158 397.5 165.8 389.5 173C387.667 174.167 384.2 177.3 385 180.5C386 184.5 385 200 391.5 201M227 140C236.333 141 250.656 148.252 228.5 153C200.5 159 193.5 155 192 161.5C192.833 164.333 190.4 170.6 184 173C176 176 175 176 171.5 180.5C165.5 183 165 185.5 162.5 187.5C160 189.5 159 187 156.5 190C154 193 151.771 195.747 143 197C132.5 198.5 123.5 200.5 109 207',
        ],
    ],
];
$staze_putanje = $staze_putanje_po_destinaciji[$id] ?? [];

/* Recenzije po destinaciji */
$recenzije_raw = fetchByDest($pdo, "SELECT ime, ocena, datum_prikaza AS datum, tekst, tagovi FROM recenzije WHERE destinacija_id = ? ORDER BY redosled, id LIMIT 6", $id);
$recenzije = array_map(function($r) {
    $r['tagovi'] = $r['tagovi'] ? (json_decode($r['tagovi'], true) ?: []) : [];
    return $r;
}, $recenzije_raw);

/* ============================================================
   4. STAZE PUTANJE — label mapa za tipove (lako prosirivo)
   ============================================================ */
$labele_grupa = [
    'plava'  => ['naziv' => 'Plave staze',  'css' => 'plava'],
    'crvena' => ['naziv' => 'Crvene staze', 'css' => 'crvena'],
    'crna'   => ['naziv' => 'Crne staze',   'css' => 'crna'],
];

/* Grupisanje SVG putanja po tipu — zbira duzina */
$staze_po_tipu = [];
foreach ($staze_putanje as $sp) {
    $tip = $sp['tip_klasa'];
    if (!isset($staze_po_tipu[$tip])) {
        $staze_po_tipu[$tip] = ['duzina' => 0.0];
    }
    $staze_po_tipu[$tip]['duzina'] += (float)$sp['duzina_km'];
}

/* ============================================================
   5. PARAMETRI ZA HEAD + NAV
   ============================================================ */
$page_title = htmlspecialchars($dest['naziv']) . ' | Snowbase';

include 'partials/head.php';
?>
<body>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<div class="main-content">

    <!-- ================================================================
         HERO — Fixed, fades on scroll
         ================================================================ -->
    <section class="hero-section" id="hero">
        <div class="prava-mapa-container">
            <svg viewBox="0 0 640 346" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg">
                <?php if ($mapa_staza_url): ?>
                    <image href="<?php echo htmlspecialchars($mapa_staza_url); ?>" width="640" height="346" preserveAspectRatio="xMidYMid slice"/>
                <?php endif; ?>
                <g>
                    <?php foreach ($staze_putanje as $sp): ?>
                        <path class="staza <?php echo htmlspecialchars($sp['tip_klasa']); ?>"
                              d="<?php echo htmlspecialchars($sp['svg_d_putanja']); ?>" />
                    <?php endforeach; ?>
                </g>
            </svg>
        </div>

        <div class="info-side">
            <span class="hero-label">
                <?php
                $parts = array_filter([$dest['zemlja'] ?? null, $dest['region'] ?? null]);
                echo htmlspecialchars($parts ? implode(' · ', $parts) : 'Premium Alpine');
                ?>
            </span>
            <h1 class="hero-title"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($dest['opis'] ?? ''); ?></p>

            <!-- Brze statistike: temp, sneg, otvorene staze, distanca -->
            <div class="hero-quick-stats">
                <?php if ($vreme): ?>
                <div class="qs-item">
                    <span class="qs-label">Trenutno</span>
                    <strong class="qs-val"><?php echo (int)$vreme['temp_c']; ?>°C</strong>
                    <small class="qs-sub"><?php echo htmlspecialchars($vreme['uslovi']); ?></small>
                </div>
                <div class="qs-item">
                    <span class="qs-label">Sneg na vrhu</span>
                    <strong class="qs-val"><?php echo (int)$vreme['sneg_vrh_cm']; ?> <em>cm</em></strong>
                    <small class="qs-sub">dno <?php echo (int)$vreme['sneg_dno_cm']; ?> cm</small>
                </div>
                <?php endif; ?>
                <?php if ($staze_status):
                    $ot_total = (int)$staze_status['plave_otvorene'] + (int)$staze_status['crvene_otvorene'] + (int)$staze_status['crne_otvorene'];
                    $uk_total = (int)$staze_status['plave_ukupno']   + (int)$staze_status['crvene_ukupno']   + (int)$staze_status['crne_ukupno'];
                ?>
                <div class="qs-item">
                    <span class="qs-label">Otvorene staze</span>
                    <strong class="qs-val"><?php echo $ot_total; ?>/<?php echo $uk_total; ?></strong>
                    <small class="qs-sub">žičara <?php echo (int)$staze_status['zicara_aktivnih']; ?>/<?php echo (int)$staze_status['zicara_ukupno']; ?></small>
                </div>
                <?php endif; ?>
                <div class="qs-item">
                    <span class="qs-label">Od Beograda</span>
                    <strong class="qs-val"><?php echo (int)$dest['distanca_od_bg_km']; ?> <em>km</em></strong>
                    <small class="qs-sub"><?php echo (int)$dest['ukupno_staza_km']; ?> km staza ukupno</small>
                </div>
            </div>

            <!-- Staze po boji — interaktivni hover sa SVG putanjama -->
            <div class="interactive-list">
                <?php
                $hero_lista = [
                    'plava'  => ['label' => 'Plave staze',  'km' => $dest['plave_staze_km']  ?? 0],
                    'crvena' => ['label' => 'Crvene staze', 'km' => $dest['crvene_staze_km'] ?? 0],
                    'crna'   => ['label' => 'Crne staze',   'km' => $dest['crne_staze_km']   ?? 0],
                ];
                foreach ($hero_lista as $tip => $row):
                ?>
                <div class="slope-item" data-tip="<?php echo $tip; ?>">
                    <div><span class="slope-name"><?php echo $row['label']; ?></span></div>
                    <strong class="slope-km <?php echo $tip; ?>">
                        <?php echo number_format((float)$row['km'], 1, ',', ''); ?> km
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>

            <a href="#rcalc" class="hero-cta-calc">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="7" y1="1" x2="7" y2="13"/><polyline points="3,9 7,13 11,9"/>
                </svg>
                Izračunaj cenu putovanja
            </a>
        </div>
    </section>

    <!-- ================================================================
         SCROLL CONTENT
         ================================================================ -->
    <section class="scroll-content">
        <div class="container-wide">

            <!-- I. SKI & INFO CENTAR (vreme + live staze status) -->
            <?php if ($vreme || $staze_status): ?>
            <div class="reveal section-block" id="ski-info">
                <span class="section-eyebrow">Realtime</span>
                <h2 class="section-title">Ski <span>&amp; Info</span> Centar</h2>

                <div class="ski-info-grid">

                    <?php if ($vreme): ?>
                    <div class="weather-panel reveal">
                        <div class="weather-top">
                            <div class="weather-temp-display">
                                <span class="weather-temp-num"><?php echo (int)$vreme['temp_c']; ?></span>
                                <span class="weather-temp-unit">°C</span>
                            </div>
                            <div class="weather-conditions">
                                <span class="weather-cond-label">Stanje</span>
                                <span class="weather-cond-val"><?php echo htmlspecialchars($vreme['uslovi']); ?></span>
                            </div>
                        </div>

                        <div class="snow-levels">
                            <div class="snow-level-item">
                                <label>Sneg — dno</label>
                                <div><span class="snow-level-val"><?php echo (int)$vreme['sneg_dno_cm']; ?></span><span class="snow-level-unit">cm</span></div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vreme['sneg_dno_cm'] / 2); ?>%"></div>
                                </div>
                            </div>
                            <div class="snow-level-item">
                                <label>Sneg — vrh</label>
                                <div><span class="snow-level-val"><?php echo (int)$vreme['sneg_vrh_cm']; ?></span><span class="snow-level-unit">cm</span></div>
                                <div class="snow-bar-wrap">
                                    <div class="snow-bar-fill" style="width:<?php echo min(100, $vreme['sneg_vrh_cm'] / 3); ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="forecast-strip">
                            <?php foreach ($prognoza as $dan): ?>
                            <div class="forecast-day">
                                <span class="forecast-day-name"><?php echo htmlspecialchars($dan['dan_skraceno']); ?></span>
                                <span class="forecast-temp">
                                    <?php echo (int)$dan['temp_max']; ?>° <span>/ <?php echo (int)$dan['temp_min']; ?>°</span>
                                </span>
                                <span class="forecast-stanje"><?php echo htmlspecialchars($dan['stanje']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($staze_status): ?>
                    <div class="live-status-panel reveal">
                        <span class="live-badge">Live Status</span>
                        <?php
                        $status_config = [
                            'plave'  => ['label' => 'Plave staze',  'css' => 'plava',  'ot' => $staze_status['plave_otvorene'],  'uk' => $staze_status['plave_ukupno']],
                            'crvene' => ['label' => 'Crvene staze', 'css' => 'crvena', 'ot' => $staze_status['crvene_otvorene'], 'uk' => $staze_status['crvene_ukupno']],
                            'crne'   => ['label' => 'Crne staze',   'css' => 'crna',   'ot' => $staze_status['crne_otvorene'],   'uk' => $staze_status['crne_ukupno']],
                        ];
                        foreach ($status_config as $row):
                            $pct = $row['uk'] > 0 ? round(($row['ot'] / $row['uk']) * 100) : 0;
                        ?>
                        <div class="staze-bar-row">
                            <div class="staze-bar-header">
                                <span class="staze-type-label">
                                    <span class="staze-dot <?php echo $row['css']; ?>"></span>
                                    <?php echo $row['label']; ?>
                                </span>
                                <span class="staze-count">
                                    <strong><?php echo (int)$row['ot']; ?></strong> / <?php echo (int)$row['uk']; ?> otvoreno
                                </span>
                            </div>
                            <div class="staze-progress-track">
                                <div class="staze-progress-fill <?php echo $row['css']; ?>" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="zicara-row">
                            <span class="zicara-label">Žičare i gondole</span>
                            <span class="zicara-status">
                                <strong><?php echo (int)$staze_status['zicara_aktivnih']; ?></strong> / <?php echo (int)$staze_status['zicara_ukupno']; ?> aktivnih
                            </span>
                        </div>

                        <?php if (!empty($vreme['vidljivost'])): ?>
                        <div class="vidljivost-box">
                            <p class="vidljivost-label">Vidljivost</p>
                            <span class="vidljivost-val"><?php echo htmlspecialchars($vreme['vidljivost']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- II. SMEŠTAJ -->
            <?php if (!empty($hoteli)): ?>
            <div class="reveal section-block" id="smestaj">
                <span class="section-eyebrow">Partnerski objekti</span>
                <h2 class="section-title">Raspoloživ <span>Smeštaj</span></h2>

                <div class="hotel-grid">
                    <?php foreach ($hoteli as $h): ?>
                    <div class="hotel-card reveal">
                        <div class="hotel-img-wrap">
                            <img src="<?php echo htmlspecialchars($h['slika_url'] ?? 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=800&auto=format&fit=crop'); ?>"
                                 class="hotel-img" alt="<?php echo htmlspecialchars($h['naziv']); ?>"
                                 width="800" height="500" loading="lazy" decoding="async">
                        </div>
                        <div class="hotel-body">
                            <div class="hotel-stars">
                                <?php for ($s = 0; $s < (int)$h['zvezdice']; $s++): ?><span>★</span><?php endfor; ?>
                            </div>
                            <h3 class="hotel-name"><?php echo htmlspecialchars($h['naziv']); ?></h3>
                            <p class="hotel-meta">
                                Kategorija <?php echo (int)$h['zvezdice']; ?>★ · Kapacitet <?php echo (int)$h['kapacitet_osoba']; ?> osoba
                            </p>
                            <div class="hotel-price">
                                €<?php echo number_format((float)$h['cena_po_noci_eur'], 0); ?>
                                <small>/ noć / osoba</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- III. OPCIJE PREVOZA -->
            <?php if (!empty($transport)):
                $TIP_NASLOV = ['bus' => 'Bus', 'avion' => 'Avion + Transfer', 'auto' => 'Auto'];
            ?>
            <div class="reveal section-block" id="prevoz">
                <span class="section-eyebrow">Iz Beograda do staze</span>
                <h2 class="section-title">Opcije <span>Prevoza</span></h2>

                <div class="transport-grid">
                    <?php foreach ($transport as $t):
                        $naslov = $TIP_NASLOV[$t['tip']] ?? $t['naziv'];
                    ?>
                    <div class="transport-card <?php echo htmlspecialchars($t['tip']); ?> reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <h3 class="transport-title"><?php echo htmlspecialchars($naslov); ?></h3>
                            <ul class="transport-info-list">
                                <?php foreach ($t['stavke'] as $stavka): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($stavka['label'] ?? ''); ?></span>
                                    <strong><?php echo htmlspecialchars($stavka['vrednost'] ?? ''); ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- IV. RICH KALKULATOR (zaključana destinacija) -->
            <?php $calc_lock_dest_id = $id; include 'partials/calculator.php'; ?>

            <!-- V. SKI PAS — transparentni cenovnik -->
            <?php if (!empty($ski_pas_cene)): ?>
            <div class="reveal section-block section-block-top" id="ski-pas">
                <span class="section-eyebrow">Transparentne cene</span>
                <h2 class="section-title">Cene <span>Ski Pasa</span></h2>
                <p class="pas-intro">Cene po kategoriji i trajanju. Ukupan iznos za vašu grupu pogledajte u kalkulatoru iznad.</p>

                <div class="pas-table-wrap reveal">
                    <table class="pas-table">
                        <thead>
                            <tr>
                                <th>Kategorija</th>
                                <th>1 dan</th>
                                <th>2 dana</th>
                                <th>3 dana</th>
                                <th>5 dana</th>
                                <th class="best">6 dana</th>
                                <th>7 dana</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ski_pas_cene as $red): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($red['kategorija']); ?></td>
                                <td>€<?php echo (int)$red['dan1']; ?></td>
                                <td>€<?php echo (int)$red['dan2']; ?></td>
                                <td>€<?php echo (int)$red['dan3']; ?></td>
                                <td>€<?php echo (int)$red['dan5']; ?></td>
                                <td class="best-col">€<?php echo (int)$red['dan6']; ?></td>
                                <td>€<?php echo (int)$red['dan7']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- VI. OPREMA & ŠKOLA -->
            <?php if (!empty($oprema_paketi) || !empty($skola_paketi)): ?>
            <div class="reveal section-block" id="oprema">
                <span class="section-eyebrow">Agencijski paketi</span>
                <h2 class="section-title">Oprema <span>&amp; Škola</span></h2>

                <div class="equipment-section-grid">

                    <?php if (!empty($oprema_paketi)): ?>
                    <div>
                        <p class="equipment-intro">
                            Rentiranje kompletne opreme direktno kroz agenciju — bez čekanja u redu na skijalištima.
                        </p>
                        <div class="equipment-cards">
                            <?php foreach ($oprema_paketi as $op): ?>
                            <div class="equipment-card <?php echo $op['preporuceno'] ? 'premium' : 'ekonomicni'; ?> reveal">
                                <?php if (!empty($op['badge'])): ?>
                                    <span class="equipment-badge"><?php echo htmlspecialchars($op['badge']); ?></span>
                                <?php endif; ?>
                                <h3 class="equipment-name"><?php echo htmlspecialchars($op['naziv']); ?></h3>
                                <?php if (!empty($op['opis'])): ?>
                                    <p class="equipment-desc"><?php echo htmlspecialchars($op['opis']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($op['includes'])): ?>
                                <ul class="equipment-includes">
                                    <?php foreach ($op['includes'] as $line): ?>
                                        <li><?php echo htmlspecialchars($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                <div class="equipment-price-row">
                                    <div>
                                        <div class="equipment-price">€<?php echo (int)$op['cena_eur']; ?></div>
                                        <div class="equipment-period">po danu / po osobi</div>
                                    </div>
                                    <?php if (!empty($op['napomena'])): ?>
                                        <div class="equipment-note<?php echo $op['preporuceno'] ? ' recommend' : ''; ?>">
                                            <?php echo htmlspecialchars($op['napomena']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skola_paketi)): ?>
                    <div class="ski-school-panel reveal">
                        <span class="section-eyebrow eyebrow-tight">Škola skijanja</span>
                        <h3 class="ski-school-title">Čas sa <span>instruktorom</span></h3>
                        <p class="ski-school-intro">Licencirani instruktori skijanja i snowboarda. Rezervacija min. 48h unapred.</p>

                        <div class="school-packages">
                            <?php foreach ($skola_paketi as $sk): ?>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name"><?php echo htmlspecialchars($sk['naziv']); ?></div>
                                    <?php if (!empty($sk['opis'])): ?>
                                        <div class="school-package-desc"><?php echo htmlspecialchars($sk['opis']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="school-package-price">
                                    <strong>€<?php echo (int)$sk['cena_eur']; ?></strong>
                                    <span>/ <?php echo htmlspecialchars($sk['jedinica']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="school-note">
                            Za grupe 6+ osoba odobravamo 15% popusta na sve pakete opreme i školu skijanja.
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- VII. RECENZIJE -->
            <?php if (!empty($recenzije)): ?>
            <div class="reveal section-block" id="utisci">
                <span class="section-eyebrow">Verifikovani putnici</span>
                <h2 class="section-title">Iskustva <span>Putnika</span></h2>

                <div class="reviews-grid">
                    <?php foreach ($recenzije as $rec): ?>
                    <div class="review-card reveal">
                        <div class="review-stars">
                            <?php for ($s = 0; $s < (int)$rec['ocena']; $s++): ?><span class="star">★</span><?php endfor; ?>
                            <?php for ($s = (int)$rec['ocena']; $s < 5; $s++): ?><span class="star dim">★</span><?php endfor; ?>
                        </div>
                        <?php if (!empty($rec['tagovi'])): ?>
                        <div class="review-tags">
                            <?php foreach ($rec['tagovi'] as $tag): ?>
                                <span class="review-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <p class="review-text"><?php echo htmlspecialchars($rec['tekst']); ?></p>
                        <div class="review-footer">
                            <div>
                                <div class="review-author"><?php echo htmlspecialchars($rec['ime']); ?></div>
                                <div class="review-date"><?php echo htmlspecialchars($rec['datum']); ?></div>
                            </div>
                            <div class="review-check">✓</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
/* ---- Slope hover → aktivira sve <path> sa tom klasom ---- */
document.querySelectorAll('.slope-item').forEach(item => {
    const klasa = item.dataset.tip;
    const paths = document.querySelectorAll('.staza.' + klasa);
    item.addEventListener('mouseenter', () => paths.forEach(p => p.classList.add('active')));
    item.addEventListener('mouseleave', () => paths.forEach(p => p.classList.remove('active')));
});

/* ---- Hero fade na scroll + nav scrolled state (rAF throttled) ---- */
const hero = document.getElementById('hero');
const nav  = document.getElementById('main-nav');
let scrollTicking = false;
window.addEventListener('scroll', () => {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
        const y = window.scrollY;
        nav?.classList.toggle('scrolled',  y > 40);
        hero?.classList.toggle('scrolled', y > 80);
        scrollTicking = false;
    });
}, { passive: true });

/* ---- Reveal animacije (IntersectionObserver) ---- */
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

</script>

<?php include 'partials/footer.php'; ?>
