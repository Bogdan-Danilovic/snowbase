<?php
/**
 * partials/nav.php
 * Zajednicka navigacija.
 *
 * Stranica pre `include` mora postaviti niz $nav_links u formatu:
 *   $nav_links = [
 *       ['href' => 'index.php',     'label' => 'Katalog',  'active' => true],
 *       ['href' => '#route-finder', 'label' => 'Planiraj rutu'],
 *       ...
 *   ];
 */
$nav_links = $nav_links ?? [];
?>
<nav id="main-nav">
    <a href="index.php" class="logo">Peak<span>&amp;</span>Palm</a>
    <div class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <a href="<?php echo htmlspecialchars($link['href']); ?>"<?php echo !empty($link['active']) ? ' class="active"' : ''; ?>>
                <?php echo htmlspecialchars($link['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
