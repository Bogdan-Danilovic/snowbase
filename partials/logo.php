<?php
/**
 * partials/logo.php
 * Snowbase brand mark — planinski peakovi (outline) + 'SNOW · BASE' wordmark.
 *
 * Opciono: postavi $logo_link = false za "samo prikaz" (bez <a>).
 */
$logo_link = $logo_link ?? 'index.php';
?>
<<?php echo $logo_link ? 'a href="' . htmlspecialchars($logo_link) . '"' : 'span'; ?> class="brand">
    <svg class="brand-icon" viewBox="0 0 40 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <!-- M-planina: levo peak srednji, sredinski dip, desno peak najvisi (sa tackom) -->
        <path d="M3 27 L13 15 L18 20 L26 6 L34 27"
              fill="none"
              stroke="var(--ice)"
              stroke-width="2.6"
              stroke-linejoin="round"
              stroke-linecap="round"/>
        <!-- Tacka na vrhu viseg peaka -->
        <circle cx="26" cy="6" r="1.7" fill="var(--ice)"/>
    </svg>
    <span class="brand-text">
        SNOW<span class="brand-text-dot">·</span>BASE
    </span>
</<?php echo $logo_link ? 'a' : 'span'; ?>>
