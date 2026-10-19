<?php

use App\Csrf;

$sections = [
    'Main Menu' => [
        ['/dashboard', 'Dashboard', 'home'],
        ['/live-feed', 'System Live Feed', 'activity'],
        ['/live-trading', 'Live Trading', 'trending'],
        ['/profile', 'My Profile', 'user-circle'],
        ['/package-details', 'Package Details', 'zap'],
        ['/my-packages', 'My Packages', 'box'],
        ['/compound-packages', 'Compound Packages', 'layers'],
        ['/investment', 'Investment History', 'history'],
    ],
    'Wallet' => [
        ['/wallet', 'My Wallet', 'wallet'],
        ['/deposit', 'Deposit', 'dollar'],
        ['/deposit-history', 'Deposit History', 'history'],
        ['/withdrawal', 'Withdrawal', 'card'],
        ['/withdrawal-history', 'Withdrawal History', 'download'],
        ['/transfer', 'Transfer', 'transfer'],
        ['/transfer-history', 'Transfer History', 'history'],
        ['/income', 'Income History', 'wallet'],
    ],
    'Network & History' => [
        ['/direct-team', 'Direct Team', 'users'],
        ['/level-team', 'Level Team', 'network'],
        ['/team-business', 'Team Business', 'chart'],
        ['/salary-achievers', 'Salary Achievers', 'trophy'],
        ['/lucky-winners', 'Lucky Winners', 'gift'],
    ],
    'Account' => [
        ['/calculator', 'Calculator', 'calculator'],
        ['/support', 'Support', 'headset'],
    ],
];
$here = current_path();
?>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<nav class="sidebar" id="sidebar" aria-label="Main menu" inert>
    <div class="sidebar__grab" aria-hidden="true"></div>
    <a class="sidebar__brand" href="<?= e(url('/dashboard')) ?>" aria-label="GLOW dashboard">
        <span class="brand__mark" aria-hidden="true"><?= icon('zap', 18) ?></span>
        <span class="brand__word">GL<span class="brand__o">O</span>W</span>
        <span class="sidebar__tag">Member</span>
    </a>
    <?php foreach ($sections as $heading => $links) : ?>
        <p class="sidebar__heading"><?= e($heading) ?></p>
        <ul class="sidebar__list">
            <?php foreach ($links as [$path, $label, $ico]) : ?>
                <li>
                    <a class="nav-link<?= $here === $path ? ' is-active' : '' ?>" href="<?= e(url($path)) ?>"
                        <?= $here === $path ? 'aria-current="page"' : '' ?>>
                        <?= icon($ico, 20) ?><span><?= e($label) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('/logout')) ?>" class="sidebar__logout">
        <?= Csrf::field() ?>
        <button class="nav-link nav-link--danger" type="submit"><?= icon('logout', 20) ?><span>Logout</span></button>
    </form>
</nav>
