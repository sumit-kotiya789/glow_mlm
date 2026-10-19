<?php

use App\Csrf;

/** @var bool $admin admin panel header */
$admin = $admin ?? false;
?>
<header class="topbar">
    <a class="brand" href="<?= e(url($admin ? '/admin' : '/dashboard')) ?>" aria-label="GLOW <?= $admin ? 'admin' : 'dashboard' ?>">
        <span class="brand__word">GL<span class="brand__o">O</span>W</span><?php if ($admin) : ?><span class="brand__tag">Admin</span><?php endif ?>
    </a>
    <span class="demo-badge">WEALTH IN PROGRESS</span>
    <div class="topbar__actions">
        <form method="post" action="<?= e(url($admin ? '/admin/logout' : '/logout')) ?>">
            <?= Csrf::field() ?>
            <button class="icon-btn icon-btn--blue" type="submit" aria-label="Log out"><?= icon('logout', 26) ?></button>
        </form>
        <button class="icon-btn icon-btn--gold" type="button" id="menu-toggle"
                aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><?= icon('menu', 28) ?></button>
    </div>
</header>
