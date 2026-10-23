<?php

use App\Csrf;

/** Neon user-panel top bar. @var string $title */
?>
<header class="topbar">
    <a class="brand" href="<?= e(url('/dashboard')) ?>" aria-label="GLOW dashboard">
        <img class="brand__logo" src="<?= e(asset('img/logo.png')) ?>" alt="GLOW">
    </a>
    <p class="topbar__title"><?= e($title ?? 'Dashboard') ?></p>
    <span class="demo-badge">WEALTH IN PROGRESS</span>
    <div class="topbar__actions">
        <form method="post" action="<?= e(url('/logout')) ?>">
            <?= Csrf::field() ?>
            <button class="icon-btn" type="submit" aria-label="Log out"><?= icon('logout', 22) ?></button>
        </form>
    </div>
</header>
