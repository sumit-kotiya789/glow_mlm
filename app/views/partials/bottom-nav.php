<?php

/** Mobile bottom bar (hidden on desktop, where the sidebar is docked). */
$here = current_path();
$items = [
    ['/dashboard', 'Home', 'home'],
    ['/wallet', 'Wallet', 'wallet'],
    ['/package-details', 'Invest', 'zap', true],
    ['/direct-team', 'Team', 'users'],
];
?>
<nav class="bottom-nav" aria-label="Quick navigation">
    <?php foreach ($items as [$path, $label, $ico, $hero]) : ?>
        <a class="bottom-nav__item<?= $here === $path ? ' is-active' : '' ?><?= !empty($hero) ? ' bottom-nav__item--hero' : '' ?>"
           href="<?= e(url($path)) ?>"<?= $here === $path ? ' aria-current="page"' : '' ?>>
            <span class="bottom-nav__icon"><?= icon($ico, 22) ?></span>
            <span class="bottom-nav__label"><?= e($label) ?></span>
        </a>
    <?php endforeach ?>
    <button class="bottom-nav__item" type="button" id="menu-toggle" aria-controls="sidebar" aria-expanded="false">
        <span class="bottom-nav__icon"><?= icon('menu', 22) ?></span>
        <span class="bottom-nav__label">More</span>
    </button>
</nav>
