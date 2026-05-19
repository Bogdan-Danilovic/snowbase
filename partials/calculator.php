<?php
/**
 * partials/calculator.php — Snowbase Rich Kalkulator
 *
 * Prikazuje pun kalkulator (destinacija + trajanje + kategorije osoba +
 * hotel + prevoz), sa live rezultatom i breakdown-om.
 *
 * Opcioni ulazi:
 *   $calc_lock_dest_id  — int, ako je postavljen kalkulator je pretpopunjen i
 *                         destinacija je locked (koristi se na destinacija.php).
 *   $pdo                — PDO konekcija (mora postojati — db.php uključen).
 *
 * Bezbedno: ne pravi se ništa ako $pdo ne postoji ili nema destinacija.
 */
if (!isset($pdo) || !($pdo instanceof PDO)) { return; }

/* ============================================================
   1. UČITAJ SVE PODATKE POTREBNE ZA KALKULATOR
   ============================================================ */
try {
    $stmt = $pdo->query("SELECT id, slug, naziv, distanca_od_bg_km, prosecna_putarina_eur FROM destinacije ORDER BY id");
    $calc_dests = $stmt->fetchAll();

    $hoteli_stmt = $pdo->query("SELECT id, destinacija_id, naziv, tip_smestaja, zvezdice, cena_po_noci_eur FROM smestaj ORDER BY destinacija_id, redosled");
    $hoteli_raw = $hoteli_stmt->fetchAll();

    $pas_stmt = $pdo->query("SELECT destinacija_id, kategorija, cena_1dan, cena_2dana, cena_3dana, cena_5dana, cena_6dana, cena_7dana FROM ski_pas_cene ORDER BY destinacija_id, redosled");
    $pas_raw = $pas_stmt->fetchAll();

    $tr_stmt = $pdo->query("SELECT destinacija_id, tip, cena_po_osobi FROM transport_opcije ORDER BY destinacija_id, redosled");
    $tr_raw = $tr_stmt->fetchAll();
} catch (PDOException $e) {
    return;
}

if (empty($calc_dests)) return;

/* ============================================================
   2. SREDI DATA-MODEL KAO JSON ZA JAVASCRIPT
   ============================================================ */
$calc_data = [];
foreach ($calc_dests as $d) {
    $calc_data[$d['id']] = [
        'naziv'     => $d['naziv'],
        'slug'      => $d['slug'],
        'distanca'  => (int)$d['distanca_od_bg_km'],
        'putarina'  => (float)$d['prosecna_putarina_eur'],
        'hoteli'    => [],
        'pas'       => [],
        'transport' => [],
    ];
}
foreach ($hoteli_raw as $h) {
    if (!isset($calc_data[$h['destinacija_id']])) continue;
    $calc_data[$h['destinacija_id']]['hoteli'][] = [
        'id'       => (int)$h['id'],
        'naziv'    => $h['naziv'],
        'tip'      => $h['tip_smestaja'],
        'zvezdice' => (int)$h['zvezdice'],
        'cena'     => (float)$h['cena_po_noci_eur'],
    ];
}
foreach ($pas_raw as $p) {
    if (!isset($calc_data[$p['destinacija_id']])) continue;
    $calc_data[$p['destinacija_id']]['pas'][$p['kategorija']] = [
        'd1' => (float)$p['cena_1dan'],
        'd2' => (float)$p['cena_2dana'],
        'd3' => (float)$p['cena_3dana'],
        'd5' => (float)$p['cena_5dana'],
        'd6' => (float)$p['cena_6dana'],
        'd7' => (float)$p['cena_7dana'],
    ];
}
foreach ($tr_raw as $t) {
    if (!isset($calc_data[$t['destinacija_id']])) continue;
    $calc_data[$t['destinacija_id']]['transport'][$t['tip']] = (float)$t['cena_po_osobi'];
}

/* Locked destination (na destinacija.php) */
$calc_locked = isset($calc_lock_dest_id) ? (int)$calc_lock_dest_id : 0;
$calc_initial = $calc_locked ?: (int)($calc_dests[0]['id'] ?? 0);
?>
<section class="rcalc-section" id="rcalc">
    <div class="rcalc-wrap reveal">
        <div class="rcalc-header">
            <span class="rcalc-eyebrow">Snowbase Kalkulator</span>
            <h2 class="rcalc-title">Sastavite <span>vaše putovanje</span></h2>
            <p class="rcalc-subtitle">
                Izaberite destinaciju, trajanje, broj putnika, hotel i tip prevoza —
                vidite ukupan iznos u realnom vremenu.
            </p>
        </div>

        <div class="rcalc-grid">
            <!-- =================== KOLONA 1 — IZBORI =================== -->
            <div class="rcalc-form">

                <!-- 1. DESTINACIJA -->
                <div class="rcalc-block">
                    <span class="rcalc-step-num">1</span>
                    <label class="rcalc-block-label">Destinacija</label>
                    <?php if ($calc_locked): ?>
                        <div class="rcalc-locked-dest">
                            <strong><?php echo htmlspecialchars($calc_data[$calc_locked]['naziv'] ?? ''); ?></strong>
                            <small>destinacija je već izabrana</small>
                        </div>
                        <input type="hidden" id="rc-dest" value="<?php echo $calc_locked; ?>">
                    <?php else: ?>
                        <div class="rcalc-select-wrap">
                            <select id="rc-dest" class="rcalc-select">
                                <?php foreach ($calc_dests as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"<?php echo $d['id'] == $calc_initial ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['naziv']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. TRAJANJE -->
                <div class="rcalc-block">
                    <span class="rcalc-step-num">2</span>
                    <label class="rcalc-block-label">Trajanje boravka</label>
                    <div class="rcalc-segments" id="rc-duration">
                        <button type="button" class="rcalc-seg active" data-dani="2" data-label="Vikend">
                            <span class="seg-top">Vikend</span><span class="seg-sub">2 dana</span>
                        </button>
                        <button type="button" class="rcalc-seg" data-dani="5" data-label="5 dana">
                            <span class="seg-top">5 dana</span><span class="seg-sub">radni</span>
                        </button>
                        <button type="button" class="rcalc-seg" data-dani="7" data-label="Nedelja">
                            <span class="seg-top">Nedelja</span><span class="seg-sub">7 dana</span>
                        </button>
                        <button type="button" class="rcalc-seg" data-dani="10" data-label="Produženi">
                            <span class="seg-top">Produženi</span><span class="seg-sub">10 dana</span>
                        </button>
                    </div>
                </div>

                <!-- 3. PUTNICI -->
                <div class="rcalc-block">
                    <span class="rcalc-step-num">3</span>
                    <label class="rcalc-block-label">Broj putnika po kategoriji</label>
                    <div class="rcalc-people-grid">
                        <div class="rcalc-person">
                            <span class="rcalc-person-label">Odrasli</span>
                            <input type="number" class="rcalc-person-input" data-kat="Odrasli" value="2" min="0" max="20">
                        </div>
                        <div class="rcalc-person">
                            <span class="rcalc-person-label">Studenti</span>
                            <input type="number" class="rcalc-person-input" data-kat="Studenti" value="0" min="0" max="20">
                        </div>
                        <div class="rcalc-person">
                            <span class="rcalc-person-label">Deca <small>(do 12g)</small></span>
                            <input type="number" class="rcalc-person-input" data-kat="Deca" value="0" min="0" max="20">
                        </div>
                        <div class="rcalc-person">
                            <span class="rcalc-person-label">Senior <small>(65+)</small></span>
                            <input type="number" class="rcalc-person-input" data-kat="Senior" value="0" min="0" max="20">
                        </div>
                    </div>
                </div>

                <!-- 4. HOTEL -->
                <div class="rcalc-block">
                    <span class="rcalc-step-num">4</span>
                    <label class="rcalc-block-label">Smeštaj</label>
                    <div class="rcalc-select-wrap">
                        <select id="rc-hotel" class="rcalc-select">
                            <!-- popunjava se JS-om kad se promeni destinacija -->
                        </select>
                    </div>
                </div>

                <!-- 5. PREVOZ -->
                <div class="rcalc-block">
                    <span class="rcalc-step-num">5</span>
                    <label class="rcalc-block-label">Tip prevoza</label>
                    <div class="rcalc-transport" id="rc-transport">
                        <label class="rcalc-tr-card">
                            <input type="radio" name="rc-tr" value="auto" checked>
                            <div class="rcalc-tr-card-inner">
                                <div class="rcalc-tr-meta">
                                    <strong>Auto</strong>
                                    <small>Gorivo + putarina</small>
                                </div>
                                <span class="rcalc-tr-price" id="rc-tr-auto-price">€0</span>
                            </div>
                        </label>
                        <label class="rcalc-tr-card">
                            <input type="radio" name="rc-tr" value="bus">
                            <div class="rcalc-tr-card-inner">
                                <div class="rcalc-tr-meta">
                                    <strong>Bus</strong>
                                    <small>Agencijski autobus</small>
                                </div>
                                <span class="rcalc-tr-price" id="rc-tr-bus-price">€0</span>
                            </div>
                        </label>
                        <label class="rcalc-tr-card">
                            <input type="radio" name="rc-tr" value="avion">
                            <div class="rcalc-tr-card-inner">
                                <div class="rcalc-tr-meta">
                                    <strong>Avion + Transfer</strong>
                                    <small>Najbrža opcija</small>
                                </div>
                                <span class="rcalc-tr-price" id="rc-tr-avion-price">€0</span>
                            </div>
                        </label>
                    </div>

                    <!-- Auto sub-options (potrosnja + cena goriva) -->
                    <div class="rcalc-auto-extras" id="rc-auto-extras">
                        <div class="rcalc-mini-input">
                            <label>Potrošnja (L/100km)</label>
                            <input type="number" id="rc-potrosnja" value="7.0" step="0.1" min="3" max="20">
                        </div>
                        <div class="rcalc-mini-input">
                            <label>Cena goriva (€/L)</label>
                            <input type="number" id="rc-cena-goriva" value="1.65" step="0.05" min="0.5" max="5">
                        </div>
                    </div>
                </div>

            </div>

            <!-- =================== KOLONA 2 — REZULTAT =================== -->
            <aside class="rcalc-result">
                <div class="rcalc-result-card">
                    <span class="rcalc-result-eyebrow">Procena ukupno</span>
                    <div class="rcalc-result-total" id="rc-total">€0</div>
                    <small class="rcalc-result-meta" id="rc-meta">izaberite parametre</small>

                    <div class="rcalc-result-divider"></div>

                    <div class="rcalc-result-row">
                        <span class="rr-label">Smeštaj</span>
                        <span class="rr-value" id="rc-r-hotel">€0</span>
                    </div>
                    <small class="rcalc-result-hint" id="rc-h-hotel">—</small>

                    <div class="rcalc-result-row">
                        <span class="rr-label">Ski pas</span>
                        <span class="rr-value" id="rc-r-pas">€0</span>
                    </div>
                    <small class="rcalc-result-hint" id="rc-h-pas">—</small>

                    <div class="rcalc-result-row">
                        <span class="rr-label">Prevoz</span>
                        <span class="rr-value" id="rc-r-tr">€0</span>
                    </div>
                    <small class="rcalc-result-hint" id="rc-h-tr">—</small>

                    <a href="#" id="rc-cta" class="rcalc-result-cta">Pogledaj destinaciju →</a>
                    <small class="rcalc-disclaimer">Procena se računa lokalno na osnovu cenovnika iz baze. Final cena se potvrđuje pri rezervaciji.</small>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
(function(){
    const CALC = <?php echo json_encode($calc_data, JSON_UNESCAPED_UNICODE); ?>;
    const LOCKED = <?php echo $calc_locked ? 'true' : 'false'; ?>;

    /* DOM refs */
    const $dest    = document.getElementById('rc-dest');
    const $hotel   = document.getElementById('rc-hotel');
    const $durBtns = document.querySelectorAll('#rc-duration .rcalc-seg');
    const $people  = document.querySelectorAll('.rcalc-person-input');
    const $trRads  = document.querySelectorAll('input[name="rc-tr"]');
    const $autoBox = document.getElementById('rc-auto-extras');
    const $potr    = document.getElementById('rc-potrosnja');
    const $cenaG   = document.getElementById('rc-cena-goriva');

    const $rTotal  = document.getElementById('rc-total');
    const $rMeta   = document.getElementById('rc-meta');
    const $rHotel  = document.getElementById('rc-r-hotel');
    const $rPas    = document.getElementById('rc-r-pas');
    const $rTr     = document.getElementById('rc-r-tr');
    const $hHotel  = document.getElementById('rc-h-hotel');
    const $hPas    = document.getElementById('rc-h-pas');
    const $hTr     = document.getElementById('rc-h-tr');
    const $cta     = document.getElementById('rc-cta');

    /* per-card transport prices */
    const $trAutoPrice  = document.getElementById('rc-tr-auto-price');
    const $trBusPrice   = document.getElementById('rc-tr-bus-price');
    const $trAvionPrice = document.getElementById('rc-tr-avion-price');

    /* state */
    let curr = {
        destId: parseInt($dest.value, 10),
        dani: 2,
        daniLabel: 'Vikend',
        hotelIdx: 0,
        tr: 'auto',
    };

    /* Helpers */
    function eur(n) { return '€' + Math.round(n).toLocaleString('sr-RS'); }
    function fillHotels() {
        const dest = CALC[curr.destId];
        if (!dest) return;
        $hotel.innerHTML = '';
        dest.hoteli.forEach((h, i) => {
            const opt = document.createElement('option');
            opt.value = i;
            const stars = '★'.repeat(h.zvezdice);
            const tip = h.tip.charAt(0).toUpperCase() + h.tip.slice(1);
            opt.textContent = `${h.naziv} · ${stars} ${tip} · €${h.cena}/noć`;
            $hotel.appendChild(opt);
        });
        curr.hotelIdx = 0;
    }
    function pasPriceFor(katObj, dani) {
        if (!katObj) return 0;
        const key = 'd' + dani;
        if (katObj[key] !== undefined) return katObj[key];
        /* fallback: nije u tabeli — derivacija */
        if (dani > 7) return katObj.d7 + (dani - 7) * katObj.d1;
        return katObj.d1 * dani;
    }
    function calc() {
        const dest = CALC[curr.destId];
        if (!dest) return;

        /* Smeštaj */
        const hotel = dest.hoteli[curr.hotelIdx];
        const peopleByKat = {};
        let totalPeople = 0;
        $people.forEach(p => {
            const n = Math.max(0, parseInt(p.value, 10) || 0);
            peopleByKat[p.dataset.kat] = n;
            totalPeople += n;
        });
        if (totalPeople === 0) totalPeople = 1;

        const hotelTotal = hotel ? hotel.cena * curr.dani * totalPeople : 0;

        /* Ski pas — po kategoriji */
        let pasTotal = 0;
        const pasBreakdown = [];
        Object.keys(peopleByKat).forEach(kat => {
            const n = peopleByKat[kat];
            if (n === 0) return;
            const katObj = dest.pas[kat];
            const price = pasPriceFor(katObj, curr.dani) * n;
            pasTotal += price;
            pasBreakdown.push(`${n}× ${kat}`);
        });

        /* Prevoz — racunaj cenu za sve 3 opcije (live update kartica) */
        const potrosnja = parseFloat($potr.value) || 7;
        const cenaG = parseFloat($cenaG.value) || 1.65;
        const autoGorivo = (dest.distanca * 2 / 100) * potrosnja * cenaG;
        const autoPutar  = dest.putarina * 2;
        const autoCena   = autoGorivo + autoPutar;
        const busCenaPo   = dest.transport.bus || 0;
        const busCena     = busCenaPo * totalPeople;
        const avionCenaPo = dest.transport.avion || 0;
        const avionCena   = avionCenaPo * totalPeople;

        $trAutoPrice.textContent  = eur(autoCena);
        $trBusPrice.textContent   = busCenaPo   === 0 ? '—' : eur(busCena);
        $trAvionPrice.textContent = avionCenaPo === 0 ? '—' : eur(avionCena);

        let trTotal = 0, trDesc = '';
        if (curr.tr === 'auto') {
            trTotal = autoCena;
            trDesc = `${dest.distanca * 2} km povratno · gorivo €${autoGorivo.toFixed(0)} · putarina €${autoPutar.toFixed(0)}`;
        } else if (curr.tr === 'bus') {
            trTotal = busCena;
            trDesc = busCenaPo === 0
                ? 'Nije primenljivo'
                : `€${busCenaPo}/osobi × ${totalPeople} ${totalPeople === 1 ? 'osoba' : 'osoba'}`;
        } else if (curr.tr === 'avion') {
            trTotal = avionCena;
            trDesc = avionCenaPo === 0
                ? 'Nije primenljivo (domaća destinacija)'
                : `€${avionCenaPo}/osobi × ${totalPeople} (avion + transfer)`;
        }

        const total = hotelTotal + pasTotal + trTotal;

        /* Render */
        $rTotal.textContent = eur(total);
        $rMeta.textContent  = `${curr.daniLabel} · ${totalPeople} ${totalPeople === 1 ? 'putnik' : 'putnika'} · ${dest.naziv}`;
        $rHotel.textContent = eur(hotelTotal);
        $rPas.textContent   = eur(pasTotal);
        $rTr.textContent    = eur(trTotal);
        $hHotel.textContent = hotel ? `${hotel.naziv} · €${hotel.cena}/noć × ${curr.dani} × ${totalPeople}` : '—';
        $hPas.textContent   = pasBreakdown.length ? `${pasBreakdown.join(' · ')} · ${curr.dani} dana` : 'nijedan putnik';
        $hTr.textContent    = trDesc;
        $cta.href           = 'destinacija.php?id=' + curr.destId;

        /* Sakrij auto extras kad nije auto */
        $autoBox.style.display = curr.tr === 'auto' ? 'grid' : 'none';
    }

    /* Bindovanja */
    $dest.addEventListener('change', () => {
        curr.destId = parseInt($dest.value, 10);
        fillHotels();
        calc();
    });
    $hotel.addEventListener('change', () => {
        curr.hotelIdx = parseInt($hotel.value, 10);
        calc();
    });
    $durBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            $durBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            curr.dani = parseInt(btn.dataset.dani, 10);
            curr.daniLabel = btn.dataset.label;
            calc();
        });
    });
    $people.forEach(p => p.addEventListener('input', calc));
    $trRads.forEach(r => r.addEventListener('change', () => {
        curr.tr = r.value;
        document.querySelectorAll('.rcalc-tr-card').forEach(c => {
            c.classList.toggle('selected', c.contains(r) && r.checked);
        });
        calc();
    }));
    $potr.addEventListener('input', calc);
    $cenaG.addEventListener('input', calc);

    /* Init */
    fillHotels();
    /* selected styling za default radio */
    document.querySelectorAll('.rcalc-tr-card').forEach(c => {
        const r = c.querySelector('input[type="radio"]');
        if (r && r.checked) c.classList.add('selected');
    });
    calc();
})();
</script>
