<?php
/**
 * partials/logo.php
 * Snowbase brand mark — stilizovana tri planinska vrha + wordmark.
 * Koristi se u nav-u (i kasnije po potrebi u footer-u/email-u).
 *
 * Opciono: postavi $logo_link = false za "samo prikaz" (bez <a>).
 */
$logo_link = $logo_link ?? 'index.php';
?>
<<?php echo $logo_link ? 'a href="' . htmlspecialchars($logo_link) . '"' : 'span'; ?> class="brand">
    <svg class="brand-icon" viewBox="0 0 32 28" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <!-- tri stilizovana planinska vrha (sredisnji vrh je najvisi i u boji teme) -->
        <path d="M2 24 L9 12 L13 17 L16 13 L19 17 L23 12 L30 24 Z"
              fill="none" stroke="currentColor" stroke-width="1.6"
              stroke-linejoin="round" stroke-linecap="round"
              opacity="0.65"/>
        <path d="M9 12 L16 2 L23 12"
              fill="none" stroke="var(--ice)" stroke-width="1.8"
              stroke-linejoin="round" stroke-linecap="round"/>
        <!-- snezna kapa na centralnom vrhu -->
        <path d="M13.5 5.5 L16 2 L18.5 5.5 L17 5 L16 6 L15 5 Z"
              fill="var(--ice)" opacity="0.9"/>
        <!-- horizont linija -->
        <line x1="2" y1="24" x2="30" y2="24"
              stroke="currentColor" stroke-width="1" opacity="0.3"/>
    </svg>
    <span class="brand-text">
        Snow<span class="brand-text-accent">base</span>
    </span>
</<?php echo $logo_link ? 'a' : 'span'; ?>>
