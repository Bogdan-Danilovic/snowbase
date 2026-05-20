<?php
/**
 * partials/footer.php
 * Premium Snowbase footer sa brendom, navigacijom, i copyright-om.
 */
?>
<footer class="site-footer">

    <div class="footer-mountains" aria-hidden="true">
        <svg viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <linearGradient id="footerMtnGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#00ccff"/>
                    <stop offset="50%" style="stop-color:#7c6fff"/>
                    <stop offset="100%" style="stop-color:#00ccff"/>
                </linearGradient>
            </defs>
            <path d="M0 120 L200 40 L380 80 L560 20 L740 65 L900 35 L1080 70 L1260 30 L1440 55 L1440 120 Z"
                  fill="url(#footerMtnGrad)"/>
        </svg>
    </div>

    <div class="footer-inner">

        <div class="footer-top">

            <div class="footer-brand-wrap">
                <?php
                $logo_link = 'index.php';
                include __DIR__ . '/logo.php';
                ?>
                <p class="footer-tagline">
                    Premium ski katalog za one koji traže više od skijališta —
                    od Beograda do Alpskih vrhova s kompletnom logistikom.
                </p>
                <div class="footer-badges">
                    <span class="footer-badge footer-badge-ice">Alpine Travel</span>
                    <span class="footer-badge footer-badge-aurora">Premium</span>
                    <span class="footer-badge">Beograd → Alpi</span>
                </div>
            </div>

            <div class="footer-nav-grid">

                <div class="footer-col">
                    <h4 class="footer-col-title">Destinacije</h4>
                    <a href="index.php#katalog">Ski Katalog</a>
                    <a href="index.php#mapa">Mapa Evrope</a>
                    <a href="index.php#rcalc">Kalkulator</a>
                    <a href="index.php#utisci">Utisci putnika</a>
                </div>

                <div class="footer-col">
                    <h4 class="footer-col-title">Premium Partneri</h4>
                    <a href="https://www.elanskis.com/" target="_blank" rel="noopener" class="aurora-link">Elan Skis</a>
                    <a href="https://www.fischersports.com/" target="_blank" rel="noopener" class="aurora-link">Fischer Sports</a>
                    <a href="https://www.salomon.com/" target="_blank" rel="noopener" class="aurora-link">Salomon</a>
                    <a href="https://www.bogner.com/" target="_blank" rel="noopener" class="aurora-link">Bogner</a>
                </div>

                <div class="footer-col">
                    <h4 class="footer-col-title">Informacije</h4>
                    <a href="index.php#partneri">Partnerstvo</a>
                    <span>Beograd, Srbija</span>
                    <span>info@snowbase.rs</span>
                </div>

            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">
                © 2026 <strong>Snowbase</strong>. Sva prava zadržana.
            </p>
            <div class="footer-legal">
                <a href="#">Pravila privatnosti</a>
                <a href="#">Uslovi korišćenja</a>
            </div>
            <p class="footer-made">
                Napravljeno s <span>&#10052;</span> za planine
            </p>
        </div>

    </div>
</footer>
</body>
</html>
