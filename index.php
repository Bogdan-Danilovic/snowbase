<?php
require_once 'db.php';

try {
    $stmt = $pdo->query("
        SELECT d.*, s.ukupno_staza_km, s.broj_zicara 
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
    ");
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Greska: " . $e->getMessage());
}

/*
 * Ticker items — u produkciji: povuci iz API-ja za sneg / stanje na putevima
 * Npr. avalanche.report API, TomTom Traffic API, OpenWeatherMap itd.
 */
$ticker_items = [
    "❄️ Les Orres: 120 cm snega na vrhu · Prajder: odličan",
    "🚗 Batrovci (SRB/HRV): Zadržavanje ~30 min",
    "⛷️ Chamonix-Mont-Blanc: Sve žičare u pogonu · Vidljivost odlična",
    "⚠️ Simplon prevoj (CH): Obavezni lanci ili zimske gume",
    "❄️ Val Thorens: 210 cm snega · Sezona traje do kraja aprila",
    "🚗 Horgoš (SRB/HUN): Bez zadržavanja",
    "🌤️ Innsbruck: −6°C · Sunčano · Sve staze otvorene",
    "⚠️ Brenner autoput (AT): Zimska oprema obavezna iznad 700 m",
    "❄️ Cortina d'Ampezzo: 85 cm sveže snežne podloge",
    "🚗 Šid (SRB/HRV): Zadržavanje ~15 min",
    "⛷️ Sella Ronda (IT): 40 km runde · Perfektni uslovi",
    "❄️ Zermatt: 300 cm snega na Matterhornskom platou",
];

/*
 * Recenzije — u produkciji: SELECT * FROM recenzije ORDER BY datum DESC LIMIT 4
 */
$recenzije = [
    [
        'ime'     => 'Marija T.',
        'tekst'   => 'Neverovatno iskustvo! Staze su savršeno pripremljene, sneg prašinast celu nedelju. Organizacija Peak & Palm bila je besprekorna od prvog do poslednjeg dana.',
        'ocena'   => 5,
        'datum'   => 'Januar 2025.',
        'dest'    => 'Les Orres, Francuska',
        'avatar'  => 'MT',
    ],
    [
        'ime'     => 'Stefan K.',
        'tekst'   => 'Treće godišnje putovanje sa ovom agencijom. Smeštaj tačno prema opisu, transfer sa aerodroma brz i bez čekanja. Jednom kad probate, ne idete drugde.',
        'ocena'   => 5,
        'datum'   => 'Februar 2025.',
        'dest'    => 'Innsbruck, Austrija',
        'avatar'  => 'SK',
    ],
    [
        'ime'     => 'Ana & Bojan',
        'tekst'   => 'Odlično za porodice s decom. Ski škola za početnike bila je strpljiva i profesionalna. Noćni život iznad svih očekivanja — pravo iznenađenje!',
        'ocena'   => 5,
        'datum'   => 'Decembar 2024.',
        'dest'    => 'Cortina d\'Ampezzo, Italija',
        'avatar'  => 'AB',
    ],
    [
        'ime'     => 'Nikola P.',
        'tekst'   => 'Sve je bilo savršeno isplanirano. Od polaska iz Beograda do povratka — nula stresa. Kalkulator na sajtu je bio tačan do poslednjeg evra. Hvala ekipi!',
        'ocena'   => 5,
        'datum'   => 'Januar 2025.',
        'dest'    => 'Zermatt, Švajcarska',
        'avatar'  => 'NP',
    ],
];

/*
 * Destinacije za padajući meni u Route Finderu
 * (direktno iz $destinacije promenljive)
 */
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Destinacija | Peak and Palm</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="style 3.0.css" rel="stylesheet">

    <style>
    /* ======================================================================
       SEKCIJA 1 — VIDEO HERO
       ====================================================================== */

    .video-hero {
        position: relative;
        width: 100%;
        height: 100vh;
        min-height: 600px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    /* Video wrapper — iframe tehnika za YouTube background */
    .vhero-video-wrap {
        position: absolute;
        inset: 0;
        z-index: 0;
        overflow: hidden;
    }

    /*
     * UPUSTVO ZA VIDEO:
     * Opcija A — YouTube: zameni VIDEO_ID u iframe src ispod
     *   Primer: src="https://www.youtube.com/embed/dQw4w9WgXcQ?..."
     *   Dobri ski/drone videji: pretražite "ski resort drone 4k" na YT
     *
     * Opcija B — Lokalni fajl: zameni <iframe> sa:
     *   <video autoplay muted loop playsinline>
     *     <source src="videos/ski-hero.mp4" type="video/mp4">
     *   </video>
     *
     * Opcija C (default, bez videa): Koristi se fallback CSS animacija ispod.
     */

    .vhero-video-wrap iframe,
    .vhero-video-wrap video {
        position: absolute;
        top: 50%;
        left: 50%;
        /* Ensure 16:9 covers the full viewport */
        width: max(100vw, 177.78vh); /* 177.78 = 100 * 16/9 */
        height: max(56.25vw, 100vh); /* 56.25 = 100 * 9/16 */
        transform: translate(-50%, -50%);
        border: none;
        pointer-events: none;
        opacity: 0.45;
    }

    /* Fallback animacija kada nema videa */
    .vhero-fallback {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 70% 60% at 20% 30%, rgba(0, 80, 130, 0.30) 0%, transparent 55%),
            radial-gradient(ellipse 60% 50% at 80% 70%, rgba(0, 40, 90, 0.22) 0%, transparent 55%),
            radial-gradient(ellipse 80% 40% at 50% 100%, rgba(0, 120, 160, 0.15) 0%, transparent 50%),
            var(--void);
        animation: fallback-drift 18s ease-in-out infinite alternate;
    }

    @keyframes fallback-drift {
        0%   { filter: brightness(1)   hue-rotate(0deg);   }
        50%  { filter: brightness(1.08) hue-rotate(8deg);  }
        100% { filter: brightness(0.92) hue-rotate(-5deg); }
    }

    /* Višeslojni overlay */
    .vhero-overlay {
        position: absolute;
        inset: 0;
        z-index: 1;
        background:
            linear-gradient(to bottom,
                rgba(4, 6, 13, 0.40) 0%,
                rgba(4, 6, 13, 0.15) 40%,
                rgba(4, 6, 13, 0.55) 75%,
                rgba(4, 6, 13, 0.95) 100%
            );
    }

    /* Sadržaj heroja */
    .vhero-content {
        position: relative;
        z-index: 2;
        max-width: 820px;
        padding: 0 32px;
        animation: fade-up 1.1s var(--ease-out) 0.2s both;
    }

    .vhero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--ice);
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        margin-bottom: 22px;
        opacity: 0.85;
    }

    .vhero-eyebrow::before,
    .vhero-eyebrow::after {
        content: '';
        width: 28px;
        height: 1px;
        background: var(--ice);
        opacity: 0.5;
    }

    .vhero-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(3rem, 7vw, 5.5rem);
        font-weight: 300;
        line-height: 1.08;
        letter-spacing: -0.01em;
        color: var(--text-primary);
        margin-bottom: 22px;
    }

    .vhero-title em {
        font-style: italic;
        color: var(--ice);
    }

    .vhero-subtitle {
        font-size: 1.02rem;
        font-weight: 300;
        color: var(--text-secondary);
        max-width: 520px;
        margin: 0 auto 38px;
        line-height: 1.75;
    }

    .vhero-cta-group {
        display: flex;
        gap: 14px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .vhero-cta-primary {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 14px 32px;
        background: var(--ice);
        color: #03080f;
        border-radius: var(--r-sm);
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        text-decoration: none;
        transition: all 0.32s var(--ease-out);
    }

    .vhero-cta-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 229, 255, 0.38);
        filter: brightness(1.05);
    }

    .vhero-cta-secondary {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 14px 32px;
        background: transparent;
        color: var(--text-secondary);
        border: 1px solid var(--border-card);
        border-radius: var(--r-sm);
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        text-decoration: none;
        transition: all 0.32s var(--ease-out);
    }

    .vhero-cta-secondary:hover {
        color: var(--text-primary);
        border-color: rgba(255,255,255,0.25);
    }

    /* Scroll indikator */
    .vhero-scroll {
        position: absolute;
        bottom: 36px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .scroll-line {
        width: 1px;
        height: 40px;
        background: linear-gradient(to bottom, var(--ice), transparent);
        animation: scroll-pulse 2.2s ease-in-out infinite;
    }

    @keyframes scroll-pulse {
        0%, 100% { opacity: 0.3; transform: scaleY(1);   }
        50%       { opacity: 0.8; transform: scaleY(0.6); transform-origin: top; }
    }

    /* ======================================================================
       SEKCIJA 2 — LIVE TICKER
       ====================================================================== */

    .ticker-section {
        position: relative;
        z-index: 10;
        background: rgba(0, 229, 255, 0.035);
        border-top:    1px solid rgba(0, 229, 255, 0.12);
        border-bottom: 1px solid rgba(0, 229, 255, 0.12);
        overflow: hidden;
        padding: 0;
    }

    .ticker-inner {
        display: flex;
        align-items: stretch;
    }

    .ticker-label {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--ice);
        color: #03080f;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        white-space: nowrap;
        z-index: 2;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
        padding-right: 28px;
    }

    .ticker-label-dot {
        width: 6px;
        height: 6px;
        background: #03080f;
        border-radius: 50%;
        animation: ticker-blink 1.2s ease-in-out infinite;
    }

    @keyframes ticker-blink {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.2; }
    }

    .ticker-track {
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        padding: 10px 0;
        mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
    }

    .ticker-tape {
        display: flex;
        gap: 0;
        white-space: nowrap;
        animation: ticker-scroll 55s linear infinite;
        will-change: transform;
    }

    .ticker-tape:hover { animation-play-state: paused; }

    @keyframes ticker-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .ticker-item {
        display: inline-flex;
        align-items: center;
        gap: 28px;
        font-size: 0.78rem;
        font-weight: 400;
        color: var(--text-secondary);
        padding: 0 32px;
        letter-spacing: 0.02em;
    }

    .ticker-item::after {
        content: '·';
        color: rgba(0, 229, 255, 0.3);
        font-size: 1.2rem;
    }

    /* ======================================================================
       SEKCIJA 3 — ROUTE FINDER
       ====================================================================== */

    .route-finder-section {
        position: relative;
        z-index: 10;
        padding: 72px 60px 0;
    }

    .route-finder-wrap {
        max-width: 960px;
        margin: 0 auto;
        background: rgba(12, 18, 32, 0.75);
        backdrop-filter: blur(28px);
        -webkit-backdrop-filter: blur(28px);
        border: 1px solid rgba(0, 229, 255, 0.14);
        border-radius: var(--r-xl);
        padding: 42px 48px;
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.04) inset,
            0 32px 80px rgba(0, 0, 0, 0.55),
            0 0 60px rgba(0, 229, 255, 0.04);
        position: relative;
        overflow: hidden;
    }

    .route-finder-wrap::before {
        content: '';
        position: absolute;
        top: -1px; left: 20%; right: 20%;
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(0,229,255,0.4), transparent);
    }

    .rf-header {
        text-align: center;
        margin-bottom: 36px;
    }

    .rf-eyebrow {
        display: block;
        color: var(--ice);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 10px;
        opacity: 0.8;
    }

    .rf-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .rf-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 16px;
        align-items: end;
    }

    .rf-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .rf-label {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--text-muted);
    }

    .rf-select,
    .rf-input {
        width: 100%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid var(--border-card);
        border-radius: var(--r-sm);
        color: var(--text-primary);
        font-family: 'Outfit', sans-serif;
        font-size: 0.92rem;
        font-weight: 300;
        padding: 13px 16px;
        outline: none;
        transition: border-color 0.3s, box-shadow 0.3s;
        appearance: none;
        -webkit-appearance: none;
    }

    .rf-select-wrap {
        position: relative;
    }

    .rf-select-wrap::after {
        content: '';
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid var(--text-muted);
        pointer-events: none;
    }

    .rf-select:focus,
    .rf-input:focus {
        border-color: rgba(0, 229, 255, 0.40);
        box-shadow: 0 0 0 3px rgba(0, 229, 255, 0.06);
    }

    .rf-select option {
        background: #0c1220;
        color: var(--text-primary);
    }

    .rf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 13px 28px;
        background: var(--ice);
        color: #03080f;
        border: none;
        border-radius: var(--r-sm);
        font-family: 'Outfit', sans-serif;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.32s var(--ease-out);
        white-space: nowrap;
        box-shadow: 0 0 0 rgba(0, 229, 255, 0);
        position: relative;
        overflow: hidden;
    }

    .rf-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .rf-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 229, 255, 0.40),
                    0 0 0 1px rgba(0, 229, 255, 0.3);
    }

    .rf-btn:hover::before { opacity: 1; }

    .rf-btn:active { transform: translateY(0); }

    /* ======================================================================
       SEKCIJA 4 — PARTNERS
       ====================================================================== */

    .partners-section {
        padding: 80px 60px 60px;
        text-align: center;
        position: relative;
        z-index: 5;
    }

    .partners-label {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 36px;
    }

    .partners-label::before,
    .partners-label::after {
        content: '';
        width: 50px;
        height: 1px;
        background: var(--border-subtle);
    }

    .partners-track {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        flex-wrap: wrap;
        max-width: 900px;
        margin: 0 auto;
    }

    .partner-item {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px 32px;
        border-right: 1px solid var(--border-subtle);
        transition: all 0.4s var(--ease-out);
        cursor: default;
        position: relative;
    }

    .partner-item:last-child { border-right: none; }

    .partner-logo {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.35rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: rgba(122, 138, 158, 0.35);
        transition: all 0.4s var(--ease-out);
        user-select: none;
        position: relative;
    }

    .partner-logo .logo-tag {
        font-size: 0.55rem;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        vertical-align: super;
        margin-left: 2px;
        opacity: 0.6;
    }

    .partner-item:hover .partner-logo {
        color: rgba(0, 229, 255, 0.75);
        text-shadow: 0 0 20px rgba(0, 229, 255, 0.30);
    }

    .partner-item::after {
        content: attr(data-category);
        position: absolute;
        bottom: -22px;
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        font-size: 0.6rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--ice);
        opacity: 0;
        white-space: nowrap;
        transition: all 0.3s;
    }

    .partner-item:hover::after {
        opacity: 0.7;
        transform: translateX(-50%) translateY(0);
    }

    /* ======================================================================
       SEKCIJA 5 — EUROPE MAP
       ====================================================================== */

    .europe-section {
        padding: 80px 60px;
        position: relative;
        z-index: 5;
    }

    .europe-header {
        text-align: center;
        margin-bottom: 52px;
    }

    .section-eyebrow {
        display: block;
        color: var(--ice);
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        margin-bottom: 12px;
        opacity: 0.8;
    }

    .section-heading {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 300;
        color: var(--text-primary);
        line-height: 1.15;
    }

    .section-heading span { color: var(--ice); }

    .europe-map-container {
        position: relative;
        max-width: 860px;
        margin: 0 auto;
        border-radius: var(--r-lg);
        overflow: visible;
    }

    .europe-map-container svg {
        width: 100%;
        height: auto;
        display: block;
    }

    /* Tooltip za mapu */
    .map-tooltip {
        position: absolute;
        background: rgba(12, 18, 32, 0.96);
        border: 1px solid rgba(0, 229, 255, 0.25);
        border-radius: var(--r-md);
        padding: 16px 20px;
        min-width: 200px;
        pointer-events: none;
        z-index: 100;
        opacity: 0;
        transform: translateY(8px) translateX(-50%);
        transition: opacity 0.22s, transform 0.22s;
        backdrop-filter: blur(16px);
        box-shadow: 0 16px 50px rgba(0,0,0,0.6);
    }

    .map-tooltip.visible {
        opacity: 1;
        transform: translateY(0) translateX(-50%);
    }

    .tt-dest     { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .tt-country  { font-size: 0.72rem; color: var(--ice); letter-spacing: 0.08em; margin-bottom: 10px; }
    .tt-km       { font-size: 0.75rem; color: var(--text-secondary); }
    .tt-km strong { color: var(--text-primary); }
    .tt-ski      { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }

    /* Map dot animacija */
    @keyframes map-ping {
        0%   { transform: scale(1);   opacity: 0.8; }
        70%  { transform: scale(2.4); opacity: 0; }
        100% { transform: scale(2.4); opacity: 0; }
    }

    @keyframes dash-flow {
        from { stroke-dashoffset: 600; }
        to   { stroke-dashoffset: 0;   }
    }

    /* ======================================================================
       SEKCIJA 6 — TESTIMONIALS CAROUSEL
       ====================================================================== */

    .testimonials-section {
        padding: 80px 60px;
        position: relative;
        z-index: 5;
        overflow: hidden;
    }

    .testimonials-header {
        text-align: center;
        margin-bottom: 52px;
    }

    .reviews-carousel {
        position: relative;
        max-width: 860px;
        margin: 0 auto;
        overflow: hidden;
    }

    .reviews-track {
        display: flex;
        transition: transform 0.6s var(--ease-out);
        will-change: transform;
    }

    .review-slide {
        min-width: 100%;
        padding: 0 12px;
        box-sizing: border-box;
    }

    .review-card-main {
        background: var(--surface);
        border: 1px solid var(--border-card);
        border-radius: var(--r-xl);
        padding: 44px 48px;
        position: relative;
        overflow: hidden;
        transition: border-color 0.4s;
    }

    .review-card-main.active-slide {
        border-color: rgba(0, 229, 255, 0.20);
    }

    .review-card-main::before {
        content: '\201C';
        position: absolute;
        top: 20px;
        right: 36px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 9rem;
        line-height: 1;
        color: rgba(0, 229, 255, 0.06);
        pointer-events: none;
    }

    .review-stars {
        display: flex;
        gap: 4px;
        margin-bottom: 22px;
    }

    .star { color: var(--gold); font-size: 1rem; }

    .review-text-main {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.45rem;
        font-weight: 300;
        font-style: italic;
        line-height: 1.6;
        color: var(--text-primary);
        margin-bottom: 32px;
    }

    .review-author {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .review-avatar-main {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--ice-soft), rgba(0,229,255,0.15));
        border: 1px solid rgba(0, 229, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--ice);
        letter-spacing: 0.04em;
        flex-shrink: 0;
    }

    .review-meta-name { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }
    .review-meta-dest { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
    .review-meta-date { font-size: 0.72rem; color: var(--ice); margin-top: 2px; opacity: 0.75; }

    /* Dots navigacija */
    .carousel-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 32px;
    }

    .carousel-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--border-card);
        border: none;
        cursor: pointer;
        padding: 0;
        transition: all 0.3s var(--ease-out);
    }

    .carousel-dot.active {
        background: var(--ice);
        box-shadow: 0 0 10px rgba(0, 229, 255, 0.5);
        transform: scale(1.3);
    }

    .carousel-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(12, 18, 32, 0.85);
        border: 1px solid var(--border-card);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        z-index: 10;
        backdrop-filter: blur(12px);
    }

    .carousel-arrow:hover {
        border-color: rgba(0, 229, 255, 0.35);
        background: rgba(0, 229, 255, 0.06);
    }

    .carousel-arrow svg { stroke: var(--text-secondary); transition: stroke 0.3s; }
    .carousel-arrow:hover svg { stroke: var(--ice); }

    .carousel-arrow.prev { left: -22px; }
    .carousel-arrow.next { right: -22px; }

    /* ======================================================================
       KATALOG SEKCIJA (override iz index-a)
       ====================================================================== */

    .catalog-section {
        padding: 72px 60px 100px;
        position: relative;
        z-index: 5;
    }

    .catalog-header-new {
        margin-bottom: 52px;
    }

    /* Divider linija */
    .section-divider {
        width: 48px;
        height: 2px;
        background: linear-gradient(to right, var(--ice), transparent);
        margin-top: 18px;
        border-radius: 2px;
    }

    /* ======================================================================
       RESPONSIVE
       ====================================================================== */

    @media (max-width: 860px) {
        .rf-grid {
            grid-template-columns: 1fr 1fr;
        }
        .rf-btn { grid-column: 1 / -1; }

        .route-finder-section,
        .europe-section,
        .testimonials-section,
        .catalog-section,
        .partners-section { padding-inline: 28px; }

        .review-card-main { padding: 32px 28px; }
        .carousel-arrow { display: none; }

        .partners-track { gap: 0; }
        .partner-item   { padding: 14px 18px; }
    }

    @media (max-width: 560px) {
        .rf-grid { grid-template-columns: 1fr; }
        .vhero-title { font-size: 2.6rem; }
        .review-text-main { font-size: 1.2rem; }
        .route-finder-wrap { padding: 28px 22px; }
        .ticker-label { font-size: 0.6rem; padding: 10px 14px 10px 14px; }
    }
    </style>
</head>
<body>

<div class="fixed-bg"></div>

<!-- ============================================================
     NAVIGACIJA
     ============================================================ -->
<nav id="main-nav">
    <a href="index.php" class="logo">Peak<span>&</span>Palm</a>
    <div class="nav-links">
        <a href="index.php" class="active">Katalog</a>
        <a href="#route-finder">Planiraj rutu</a>
        <a href="#mapa">Destinacije</a>
        <a href="#katalog">Skijalista</a>
    </div>
</nav>

<!-- ============================================================
     1. VIDEO HERO
     ============================================================ -->
<section class="video-hero" id="hero">

    <div class="vhero-video-wrap">
        <!--
            ZAMENI VIDEO:
            Opcija A — YouTube iframe (preporučeno za demo):
              <iframe src="https://www.youtube.com/embed/TVAJ_VIDEO_ID?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playlist=TVAJ_VIDEO_ID&rel=0" allow="autoplay" frameborder="0"></iframe>
            Opcija B — Lokalni fajl:
              <video autoplay muted loop playsinline><source src="videos/hero-ski.mp4" type="video/mp4"></video>
            Za sada se prikazuje CSS fallback animacija ispod.
        -->
        <div class="vhero-fallback"></div>
    </div>

    <div class="vhero-overlay"></div>

    <div class="vhero-content">
        <div class="vhero-eyebrow">
            Premium Alpine Travel
        </div>
        <h1 class="vhero-title">
            Gde se završava<br>
            asfalt, tu počinje <em>avantura</em>
        </h1>
        <p class="vhero-subtitle">
            Direktno iz Beograda do najlepših ski centara Alpa.
            Organizacija, logistika i komfor — sve na jednom mestu.
        </p>
        <div class="vhero-cta-group">
            <a href="#katalog" class="vhero-cta-primary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="7" cy="7" r="6"/><polyline points="7,4 7,7 9,9"/>
                </svg>
                Istraži katalog
            </a>
            <a href="#route-finder" class="vhero-cta-secondary">
                Planiranje rute →
            </a>
        </div>
    </div>

    <div class="vhero-scroll">
        <span>Skroluj</span>
        <div class="scroll-line"></div>
    </div>

</section>

<!-- ============================================================
     2. LIVE TICKER
     ============================================================ -->
<div class="ticker-section">
    <div class="ticker-inner">
        <div class="ticker-label">
            <div class="ticker-label-dot"></div>
            Live
        </div>
        <div class="ticker-track">
            <div class="ticker-tape" id="tickerTape">
                <?php
                // Dupliraj niz da se ticker vrti neprekidno u petlji
                $all_items = array_merge($ticker_items, $ticker_items);
                foreach ($all_items as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     3. QUICK ROUTE FINDER
     ============================================================ -->
<section class="route-finder-section" id="route-finder">
    <div class="route-finder-wrap reveal">
        <div class="rf-header">
            <span class="rf-eyebrow">Planiranje puta</span>
            <h2 class="rf-title">Brzi kalkulator rute i troškova</h2>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label class="rf-label" for="rf-dest">Destinacija</label>
                <div class="rf-select-wrap">
                    <select class="rf-select" id="rf-dest">
                        <option value="">— Izaberite skijalište —</option>
                        <?php foreach ($destinacije as $d): ?>
                            <option value="<?php echo $d['id']; ?>">
                                <?php echo htmlspecialchars($d['naziv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-osobe">Broj osoba</label>
                <input type="number" class="rf-input" id="rf-osobe"
                       min="1" max="9" value="2" placeholder="npr. 4">
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-dani">Trajanje (dana)</label>
                <input type="number" class="rf-input" id="rf-dani"
                       min="1" max="21" value="7" placeholder="npr. 7">
            </div>
            <div>
                <button class="rf-btn" onclick="routeFinderGo()">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="7" y1="1" x2="7" y2="13"/>
                        <polyline points="3,9 7,13 11,9"/>
                    </svg>
                    Izračunaj
                </button>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     4. PARTNERS
     ============================================================ -->
<section class="partners-section" id="partneri">
    <div class="partners-label">
        Premium Partneri &amp; Preporučena Oprema
    </div>
    <div class="partners-track">
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">ELAN<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">Fischer<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije &amp; Oprema">
            <span class="partner-logo">Atomic<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Garderoba">
            <span class="partner-logo">Salomon<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Garderoba">
            <span class="partner-logo">Bogner<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Skije">
            <span class="partner-logo">Völkl<span class="logo-tag">®</span></span>
        </div>
        <div class="partner-item" data-category="Prevoz">
            <span class="partner-logo">FlixBus<span class="logo-tag">®</span></span>
        </div>
    </div>
</section>

<!-- ============================================================
     5. INTERAKTIVNA MAPA EVROPE
     ============================================================ -->
<section class="europe-section" id="mapa">
    <div class="europe-header reveal">
        <span class="section-eyebrow">Logistika iz Beograda</span>
        <h2 class="section-heading">Naše <span>Destinacije</span> na mapi</h2>
    </div>

    <div class="europe-map-container reveal" id="europeMapContainer">

        <!-- Tooltip -->
        <div class="map-tooltip" id="mapTooltip"></div>

        <svg viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg"
             style="background: rgba(7,12,24,0.6); border-radius: 20px; border: 1px solid rgba(0,229,255,0.08);">

            <defs>
                <!-- Glow filter za linije -->
                <filter id="lineGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
                <!-- Glow za dots -->
                <filter id="dotGlow" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="4" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>

                <!-- Animovana tačka marker -->
                <radialGradient id="bgGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%"   stop-color="#0a1428" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#04060d" stop-opacity="1"/>
                </radialGradient>
            </defs>

            <!-- Pozadinska boja mape -->
            <rect width="800" height="500" fill="url(#bgGrad)" rx="20"/>

            <!-- ── Simplified country shapes ── -->
            <!-- Španija -->
            <path d="M 70 280 L 72 230 L 105 205 L 155 198 L 175 218 L 178 255 L 165 285 L 130 298 L 85 292 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <!-- Portugal -->
            <path d="M 55 250 L 70 230 L 72 280 L 58 282 Z"
                  fill="rgba(255,255,255,0.025)" stroke="rgba(255,255,255,0.06)" stroke-width="0.8"/>
            <!-- Francuska -->
            <path d="M 155 198 L 205 175 L 265 178 L 280 200 L 285 230 L 270 262 L 240 280 L 200 285 L 175 270 L 165 245 L 165 218 Z"
                  fill="rgba(255,255,255,0.032)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8"
                  id="map-france"/>
            <!-- Italija (boot) -->
            <path d="M 240 280 L 270 262 L 300 270 L 320 295 L 330 340 L 320 390 L 300 420 L 280 400 L 275 360 L 265 320 L 250 305 Z"
                  fill="rgba(255,255,255,0.032)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8"
                  id="map-italy"/>
            <!-- Švajcarska -->
            <path d="M 265 220 L 295 215 L 310 228 L 305 248 L 280 252 L 265 242 Z"
                  fill="rgba(255,255,255,0.04)" stroke="rgba(255,255,255,0.09)" stroke-width="0.8"
                  id="map-swiss"/>
            <!-- Nemačka -->
            <path d="M 265 178 L 320 162 L 380 158 L 400 175 L 392 210 L 370 228 L 330 235 L 300 228 L 280 210 L 278 192 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"
                  id="map-germany"/>
            <!-- Austrija -->
            <path d="M 312 218 L 380 210 L 410 218 L 420 232 L 405 248 L 370 252 L 340 250 L 318 240 Z"
                  fill="rgba(255,255,255,0.040)" stroke="rgba(255,255,255,0.09)" stroke-width="0.8"
                  id="map-austria"/>
            <!-- Slovenija -->
            <path d="M 370 252 L 400 248 L 415 260 L 408 272 L 382 268 Z"
                  fill="rgba(255,255,255,0.035)" stroke="rgba(255,255,255,0.08)" stroke-width="0.8"/>
            <!-- Hrvatska -->
            <path d="M 395 262 L 430 255 L 450 270 L 460 295 L 450 330 L 430 345 L 415 330 L 410 305 L 408 282 Z"
                  fill="rgba(255,255,255,0.028)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <!-- Srbija -->
            <path d="M 445 272 L 490 265 L 510 280 L 508 320 L 490 345 L 462 342 L 448 320 L 450 295 Z"
                  fill="rgba(0, 229, 255, 0.06)" stroke="rgba(0,229,255,0.18)" stroke-width="1"/>
            <!-- Mađarska -->
            <path d="M 410 220 L 460 212 L 490 225 L 498 248 L 480 262 L 445 265 L 418 255 L 410 238 Z"
                  fill="rgba(255,255,255,0.025)" stroke="rgba(255,255,255,0.07)" stroke-width="0.8"/>
            <!-- Češka/Slovačka (gornji deo) -->
            <path d="M 320 162 L 390 150 L 430 158 L 445 172 L 440 198 L 405 200 L 370 198 L 335 192 Z"
                  fill="rgba(255,255,255,0.022)" stroke="rgba(255,255,255,0.06)" stroke-width="0.8"/>
            <!-- Benelux/Britanija blur -->
            <path d="M 180 145 L 240 135 L 268 150 L 265 178 L 205 175 L 185 165 Z"
                  fill="rgba(255,255,255,0.020)" stroke="rgba(255,255,255,0.05)" stroke-width="0.8"/>

            <!-- Severnoafrička obala (dno) -->
            <path d="M 50 450 L 800 450 L 800 500 L 50 500 Z"
                  fill="rgba(255,255,255,0.010)" stroke="rgba(255,255,255,0.04)" stroke-width="0.5"/>

            <!-- Mediteransko more (label) -->
            <text x="300" y="460" text-anchor="middle"
                  font-family="'Cormorant Garamond', serif" font-size="11" font-style="italic"
                  fill="rgba(255,255,255,0.14)" letter-spacing="2">Sredozemno more</text>

            <!-- ── Animovane linije — Beograd → Destinacije ── -->
            <!-- Beograd (BG) = cx 480, cy 300 -->

            <!-- → Francuske Alpe (Chamonix/Val Thorens) — ~ (230, 245) -->
            <path d="M 480 300 Q 380 200 230 245"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4s linear infinite;">
            </path>

            <!-- → Italija (Cortina/Dolomiti) — ~ (310, 275) -->
            <path d="M 480 300 Q 420 260 310 275"
                  stroke="rgba(0,229,255,0.40)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3.5s linear infinite 0.6s;">
            </path>

            <!-- → Austrija (Innsbruck) — ~ (370, 228) -->
            <path d="M 480 300 Q 445 255 370 228"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3s linear infinite 1.2s;">
            </path>

            <!-- → Švajcarska (Zermatt) — ~ (285, 240) -->
            <path d="M 480 300 Q 400 230 285 240"
                  stroke="rgba(0,229,255,0.35)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4.5s linear infinite 0.3s;">
            </path>

            <!-- ── Destination pins ── -->

            <!-- Francuske Alpe -->
            <g class="map-pin" data-dest="Chamonix / Les Orres" data-country="Francuska"
               data-km="1580" data-ski="280 km staza"
               transform="translate(228, 243)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- Italija -->
            <g class="map-pin" data-dest="Cortina d'Ampezzo" data-country="Italija"
               data-km="1190" data-ski="140 km staza"
               transform="translate(310, 273)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 0.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- Austrija -->
            <g class="map-pin" data-dest="Innsbruck / Arlberg" data-country="Austrija"
               data-km="1025" data-ski="340 km staza"
               transform="translate(368, 226)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.0s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- Švajcarska -->
            <g class="map-pin" data-dest="Zermatt / Davos" data-country="Švajcarska"
               data-km="1350" data-ski="360 km staza"
               transform="translate(284, 238)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- BEOGRAD — polazna tačka -->
            <g transform="translate(479, 300)">
                <!-- Ping animacija -->
                <circle r="18" fill="rgba(0,229,255,0.05)"
                        style="animation: map-ping 2s ease-out infinite;"/>
                <circle r="10" fill="rgba(0,229,255,0.14)" stroke="rgba(0,229,255,0.6)" stroke-width="1.5"/>
                <circle r="5"  fill="#00e5ff" filter="url(#dotGlow)"/>
                <text y="-18" text-anchor="middle" font-family="'Outfit',sans-serif"
                      font-size="9.5" font-weight="600" letter-spacing="1"
                      fill="rgba(0,229,255,0.9)">BEOGRAD</text>
            </g>

            <!-- Labels za države -->
            <text x="225" y="235" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">FR</text>
            <text x="310" y="315" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">IT</text>
            <text x="368" y="238" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">AT</text>
            <text x="480" y="318" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(0,229,255,0.35)" letter-spacing="1">RS</text>

        </svg><!-- /SVG -->
    </div><!-- /europe-map-container -->
</section>

<!-- ============================================================
     6. TESTIMONIALS CAROUSEL
     ============================================================ -->
<section class="testimonials-section" id="utisci">
    <div class="testimonials-header reveal">
        <span class="section-eyebrow">Putnici o nama</span>
        <h2 class="section-heading">Pravi <span>Utisci</span></h2>
    </div>

    <div class="reviews-carousel reveal" id="reviewsCarousel">

        <button class="carousel-arrow prev" onclick="carouselMove(-1)" aria-label="Prethodni">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="10,3 5,8 10,13"/>
            </svg>
        </button>

        <div class="reviews-track" id="reviewsTrack">
            <?php foreach ($recenzije as $i => $rev): ?>
            <div class="review-slide">
                <div class="review-card-main <?php echo $i === 0 ? 'active-slide' : ''; ?>">
                    <div class="review-stars">
                        <?php for ($s = 0; $s < $rev['ocena']; $s++): ?>
                            <span class="star">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="review-text-main">
                        "<?php echo htmlspecialchars($rev['tekst']); ?>"
                    </p>
                    <div class="review-author">
                        <div class="review-avatar-main"><?php echo htmlspecialchars($rev['avatar']); ?></div>
                        <div>
                            <div class="review-meta-name"><?php echo htmlspecialchars($rev['ime']); ?></div>
                            <div class="review-meta-dest"><?php echo htmlspecialchars($rev['dest']); ?></div>
                            <div class="review-meta-date"><?php echo htmlspecialchars($rev['datum']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-arrow next" onclick="carouselMove(1)" aria-label="Sledeći">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6,3 11,8 6,13"/>
            </svg>
        </button>

        <div class="carousel-nav" id="carouselNav">
            <?php foreach ($recenzije as $i => $rev): ?>
                <button class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                        onclick="carouselGoTo(<?php echo $i; ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     KATALOG GRID
     ============================================================ -->
<section class="catalog-section" id="katalog">

    <div class="catalog-header-new reveal">
        <span class="section-eyebrow">Explore the Slopes</span>
        <h2 class="section-heading">Katalog <span>Ski Destinacija</span></h2>
        <p style="color:var(--text-secondary); max-width:520px; margin-top:14px; font-size:0.95rem; line-height:1.75;">
            Izaberite destinaciju, pregledajte interaktivnu mapu staza i izračunajte troškove logistike iz Beograda.
        </p>
        <div class="section-divider"></div>
    </div>

    <div class="dest-grid">
        <?php foreach ($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop"
                     class="dest-img" alt="<?php echo htmlspecialchars($d['naziv']); ?>">
            </div>
            <div class="dest-body">
                <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                <p class="dest-desc">
                    <?php
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
                <a href="destinacija.php?id=<?php echo $d['id']; ?>" class="btn-view">
                    Pogledaj Detaljnije
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</section>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
/* ================================================================
   NAV — scroll efekat
   ================================================================ */
window.addEventListener('scroll', () => {
    document.getElementById('main-nav').classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

/* ================================================================
   REVEAL ANIMACIJA (IntersectionObserver — bolji od scroll eventa)
   ================================================================ */
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

document.querySelectorAll('.reveal').forEach((el, i) => {
    // Staggered delay za grid kartice
    if (el.closest('.dest-grid')) {
        const idx = Array.from(el.closest('.dest-grid').children).indexOf(el);
        el.style.transitionDelay = (idx * 0.08) + 's';
    }
    revealObs.observe(el);
});

/* ================================================================
   ROUTE FINDER — redirect na destinacija.php sa kalkulatorom
   ================================================================ */
function routeFinderGo() {
    const destId = document.getElementById('rf-dest').value;
    const osobe  = document.getElementById('rf-osobe').value;
    const dani   = document.getElementById('rf-dani').value;

    if (!destId) {
        const sel = document.getElementById('rf-dest');
        sel.style.borderColor = 'rgba(255, 80, 80, 0.5)';
        sel.style.boxShadow   = '0 0 0 3px rgba(255, 80, 80, 0.08)';
        setTimeout(() => {
            sel.style.borderColor = '';
            sel.style.boxShadow   = '';
        }, 1800);
        return;
    }

    // Redirect na stranicu destinacije sa parametrima za kalkulator
    const url = `destinacija.php?id=${destId}&osobe=${osobe}&dani=${dani}#logistika`;
    window.location.href = url;
}

// Enter taster u route finderu
document.querySelectorAll('.rf-input, .rf-select').forEach(el => {
    el.addEventListener('keydown', e => { if (e.key === 'Enter') routeFinderGo(); });
});

/* ================================================================
   EUROPE MAP — tooltip
   ================================================================ */
const tooltip = document.getElementById('mapTooltip');
const mapContainer = document.getElementById('europeMapContainer');

document.querySelectorAll('.map-pin').forEach(pin => {
    pin.style.cursor = 'pointer';

    pin.addEventListener('mouseenter', function(e) {
        const dest    = this.dataset.dest;
        const country = this.dataset.country;
        const km      = this.dataset.km;
        const ski     = this.dataset.ski;

        tooltip.innerHTML = `
            <div class="tt-dest">${dest}</div>
            <div class="tt-country">${country}</div>
            <div class="tt-km"><strong>${km} km</strong> od Beograda</div>
            <div class="tt-ski">${ski}</div>
        `;

        // Pozicioniranje tooltipa relativno na SVG
        const containerRect = mapContainer.getBoundingClientRect();
        const pinRect = this.getBoundingClientRect();
        const pinCenterX = pinRect.left + pinRect.width / 2 - containerRect.left;
        const pinTopY    = pinRect.top  - containerRect.top;

        tooltip.style.left = pinCenterX + 'px';
        tooltip.style.top  = (pinTopY - tooltip.offsetHeight - 18) + 'px';
        tooltip.classList.add('visible');
    });

    pin.addEventListener('mouseleave', () => {
        tooltip.classList.remove('visible');
    });

    // Klik na pin — scroll do kataloga ili direktan link
    pin.addEventListener('click', () => {
        document.getElementById('katalog').scrollIntoView({ behavior: 'smooth' });
    });
});

/* ================================================================
   TESTIMONIALS CAROUSEL
   ================================================================ */
let carouselCurrent = 0;
const carouselTotal = <?php echo count($recenzije); ?>;
const carouselTrack = document.getElementById('reviewsTrack');

function carouselGoTo(index) {
    // Deaktiviraj stari
    const slides = document.querySelectorAll('.review-card-main');
    slides.forEach(s => s.classList.remove('active-slide'));

    carouselCurrent = (index + carouselTotal) % carouselTotal;
    carouselTrack.style.transform = `translateX(-${carouselCurrent * 100}%)`;

    // Aktiviraj novi
    slides[carouselCurrent].classList.add('active-slide');

    // Dots
    document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === carouselCurrent);
    });
}

function carouselMove(dir) {
    carouselGoTo(carouselCurrent + dir);
}

// Auto-rotate svakih 5 sekundi
let carouselTimer = setInterval(() => carouselMove(1), 5000);

// Pauziraj na hover
const carousel = document.getElementById('reviewsCarousel');
carousel.addEventListener('mouseenter', () => clearInterval(carouselTimer));
carousel.addEventListener('mouseleave', () => {
    carouselTimer = setInterval(() => carouselMove(1), 5000);
});

// Touch/swipe podrška
let touchStartX = 0;
carousel.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
carousel.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 40) carouselMove(diff > 0 ? 1 : -1);
});
</script>

</body>
</html>
