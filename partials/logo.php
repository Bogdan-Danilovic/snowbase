<?php
/**
 * partials/logo.php
 * Snowbase brand mark — 'S' monogram uklopljen u planinski vrh.
 * Padina vrha crta gornju polovinu slova S; donja polovina je odraz u snegu.
 *
 * Opciono: postavi $logo_link = false za "samo prikaz" (bez <a>).
 */
$logo_link = $logo_link ?? 'index.php';
?>
<<?php echo $logo_link ? 'a href="' . htmlspecialchars($logo_link) . '"' : 'span'; ?> class="brand">
    <svg class="brand-icon" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
            <linearGradient id="brandPeakGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%"  stop-color="var(--ice)" stop-opacity="1"/>
                <stop offset="100%" stop-color="var(--ice)" stop-opacity="0.55"/>
            </linearGradient>
            <linearGradient id="brandMirrorGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%"   stop-color="var(--ice)" stop-opacity="0.28"/>
                <stop offset="100%" stop-color="var(--ice)" stop-opacity="0"/>
            </linearGradient>
        </defs>

        <!-- planinski vrh koji ujedno crta GORNJU polovinu slova S -->
        <!-- spoljna kontura vrha: leva padina sa S-zavoj u sredini, desna padina -->
        <path d="M4 28
                 L13 12
                 C13 12, 14 14, 17 14
                 C20 14, 21 12, 21 12
                 C21 12, 22 14, 25 14
                 L32 28 Z"
              fill="url(#brandPeakGrad)"
              stroke="var(--ice)"
              stroke-width="0.6"
              stroke-linejoin="round"
              opacity="0.95"/>

        <!-- snežna kapa (najsvetlija tačka) -->
        <path d="M11.5 16 L13 12 L14.8 14.4 L16 13.2 L17.2 14.4 L18 13.4 L21 12 L22.5 16 Z"
              fill="#ffffff"
              opacity="0.92"/>

        <!-- odraz / DONJA polovina slova S (snežna senka koja zatvara monogram) -->
        <path d="M4 28
                 C8 30, 12 30, 18 28
                 C24 26, 28 30, 32 28
                 L32 30
                 C28 32, 24 31, 18 30
                 C12 29, 8 32, 4 30 Z"
              fill="url(#brandMirrorGrad)"/>

        <!-- horizont linija -->
        <line x1="2" y1="28" x2="34" y2="28"
              stroke="var(--ice)" stroke-width="0.6"
              opacity="0.35"/>
    </svg>
    <span class="brand-text">
        Snow<span class="brand-text-accent">base</span>
    </span>
</<?php echo $logo_link ? 'a' : 'span'; ?>>
