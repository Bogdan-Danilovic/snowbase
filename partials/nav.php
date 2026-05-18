<?php
/**
 * partials/nav.php
 * Snowbase navigacija: anchor linkovi levo, brand u sredini, linkovi desno.
 *
 * Stranica može opciono podesiti $nav_links_left i $nav_links_right
 * (nizovi {href, label}). Inace se koriste razumni default-ovi.
 */
$nav_links_left  = $nav_links_left  ?? [
    ['href' => 'index.php#katalog',  'label' => 'Katalog'],
    ['href' => 'index.php#mapa',     'label' => 'Mapa'],
];
$nav_links_right = $nav_links_right ?? [
    ['href' => 'index.php#partneri', 'label' => 'Partneri'],
    ['href' => 'index.php#utisci',   'label' => 'Utisci'],
];
?>
<nav id="main-nav">
    <div class="nav-side nav-side-left">
        <?php foreach ($nav_links_left as $link): ?>
            <a href="<?php echo htmlspecialchars($link['href']); ?>"<?php echo !empty($link['active']) ? ' class="active"' : ''; ?>>
                <?php echo htmlspecialchars($link['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/logo.php'; ?>

    <div class="nav-side nav-side-right">
        <?php foreach ($nav_links_right as $link): ?>
            <a href="<?php echo htmlspecialchars($link['href']); ?>"<?php echo !empty($link['active']) ? ' class="active"' : ''; ?>>
                <?php echo htmlspecialchars($link['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
