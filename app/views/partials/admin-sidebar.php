<?php

use App\Csrf;
use App\Database;

$pending = [
    '/admin/deposits' => (int) Database::value("SELECT COUNT(*) FROM deposits WHERE status = 'Pending'"),
    '/admin/withdrawals' => (int) Database::value("SELECT COUNT(*) FROM withdrawals WHERE status = 'Pending'"),
    '/admin/tickets' => (int) Database::value("SELECT COUNT(*) FROM tickets WHERE status = 'Open'"),
];
$sections = [
    'Overview' => [
        ['/admin', 'Dashboard', 'home'],
    ],
    'Finance' => [
        ['/admin/deposits', 'Deposits', 'dollar'],
        ['/admin/withdrawals', 'Withdrawals', 'card'],
        ['/admin/ledger', 'Ledger', 'history'],
    ],
    'Members' => [
        ['/admin/users', 'Users', 'users'],
        ['/admin/investments', 'Investments', 'box'],
    ],
    'Rewards & Support' => [
        ['/admin/lucky-draws', 'Lucky Draws', 'gift'],
        ['/admin/tickets', 'Tickets', 'ticket'],
    ],
    'System' => [
        ['/admin/settings', 'Settings', 'zap'],
    ],
];
$here = current_path();
$active = static fn (string $path): bool => $path === '/admin' ? $here === '/admin' : str_starts_with($here, $path);
?>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<nav class="sidebar sidebar--docked" id="sidebar" aria-label="Admin menu" inert>
    <?php foreach ($sections as $heading => $links) : ?>
        <p class="sidebar__heading"><?= e($heading) ?></p>
        <ul class="sidebar__list">
            <?php foreach ($links as [$path, $label, $ico]) : ?>
                <li>
                    <a class="nav-link<?= $active($path) ? ' is-active' : '' ?>" href="<?= e(url($path)) ?>"
                        <?= $active($path) ? 'aria-current="page"' : '' ?>>
                        <?= icon($ico, 22) ?><span><?= e($label) ?></span>
                        <?php if (!empty($pending[$path])) : ?>
                            <span class="nav-badge" aria-label="<?= e($pending[$path]) ?> pending"><?= e($pending[$path]) ?></span>
                        <?php endif ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('/admin/logout')) ?>" class="sidebar__logout">
        <?= Csrf::field() ?>
        <button class="nav-link nav-link--danger" type="submit"><?= icon('logout', 22) ?><span>Logout</span></button>
    </form>
</nav>
