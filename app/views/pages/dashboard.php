<?php

use App\Models\Investment;

/**
 * @var array $user
 * @var list<array{label: string, value: string, tone: string}> $stats
 * @var array<string, mixed> $salary
 * @var string $referral
 * @var list<array> $terms
 * @var list<array> $plans
 * @var int $activeCount
 * @var list<array> $trades
 */

$quick = [
    ['/deposit', 'Deposit', 'dollar'],
    ['/package-details', 'Invest', 'zap'],
    ['/withdrawal', 'Withdraw', 'card'],
    ['/my-packages', 'My Plans', 'box'],
    ['/level-team', 'Team', 'users'],
    ['/wallet', 'Wallet', 'wallet'],
    ['/support', 'Support', 'headset'],
    ['/calculator', 'Calculator', 'calculator'],
];
$conditions = [
    ['Direct active IDs', $salary['directs'], $salary['directs_need']],
    ['Level-2 active IDs', $salary['level2'], $salary['level2_need']],
];
$selfOk = $salary['self'] >= $salary['self_need'];
?>
<h1 class="sr-only">Dashboard</h1>

<section class="hero" aria-label="Account">
    <div class="hero__id">
        <span class="hero__avatar"><?= icon('user', 26) ?></span>
        <div class="hero__text">
            <p class="eyebrow">Welcome back</p>
            <p class="hero__name truncate"><?= e($user['name']) ?></p>
            <p class="hero__meta">
                <span class="hero__code"><?= e($user['user_code']) ?></span>
                <button class="copy-mini" type="button" data-copy="<?= e($user['user_code']) ?>"
                        data-copy-msg="User ID copied" aria-label="Copy user ID"><?= icon('copy', 16) ?></button>
                <span class="hero__dot" aria-hidden="true"></span>
                <span>Joined <?= e(fmt_date($user['created_at'])) ?></span>
            </p>
        </div>
    </div>
    <div class="hero__ref">
        <label class="eyebrow" for="ref-link">Referral link</label>
        <div class="copy-field">
            <input class="copy-field__input" id="ref-link" type="text" readonly value="<?= e($referral) ?>">
            <button class="copy-field__btn" type="button" data-copy="<?= e($referral) ?>"
                    data-copy-msg="Referral link copied" aria-label="Copy referral link"><?= icon('copy', 20) ?></button>
        </div>
    </div>
</section>

<section class="stat-grid" aria-label="Wallets and earnings">
    <?php foreach ($stats as $card) {
        partial('stat-card', $card);
    } ?>
</section>

<nav class="quick-grid" aria-label="Quick actions">
    <?php foreach ($quick as [$href, $label, $ico]) : ?>
        <a class="quick-grid__item" href="<?= e(url($href)) ?>">
            <span class="quick-grid__icon"><?= icon($ico, 22) ?></span>
            <span class="quick-grid__label"><?= e($label) ?></span>
        </a>
    <?php endforeach ?>
</nav>

<?php
$selfPct = $salary['self_need'] > 0 ? min(100, round($salary['self'] / $salary['self_need'] * 100)) : 100;
$gates = [
    ['Self investment', $selfPct, 'money', '$' . number_format((float) $salary['self'], fmod((float) $salary['self'], 1) == 0.0 ? 0 : 2), 'Need ' . money($salary['self_need']) . '+', $selfOk],
];
foreach ($conditions as [$label, $have, $need]) {
    $shown = min($have, $need);
    $gates[] = [$label, $need > 0 ? round($shown / $need * 100) : 100, 'count', $shown, 'of ' . $need, $shown >= $need];
}
$doneCount = count(array_filter($gates, static fn ($g) => $g[5]));
?>
<section class="salary-card" aria-labelledby="salary-title" data-reveal>
    <span class="salary-card__watermark" aria-hidden="true"><?= icon('gift', 150) ?></span>
    <header class="salary-head" data-anim style="--d:0">
        <span class="salary-head__badge" aria-hidden="true"><?= icon('gift', 26) ?></span>
        <div>
            <h2 class="salary-card__title" id="salary-title">Monthly Salary</h2>
            <p class="salary-card__sub">Earn <?= e(money($salary['amount'])) ?> per month for <?= e($salary['months']) ?> months!
                <span class="salary-card__total">(<?= e(money($salary['amount'] * $salary['months'])) ?> Total)</span></p>
        </div>
    </header>

    <ul class="gates" aria-label="Qualification steps">
        <?php foreach ($gates as $i => [$label, $pct, $kind, $value, $sub, $done]) : ?>
            <li class="gate<?= $done ? ' is-done' : '' ?>" data-anim style="--p:<?= e($pct) ?>;--d:<?= e($i + 1) ?>">
                <div class="gate__ring" role="img" aria-label="<?= e($label) ?>: <?= e($pct) ?> percent<?= $done ? ', completed' : '' ?>">
                    <svg viewBox="0 0 80 80" aria-hidden="true">
                        <circle class="ring__track" cx="40" cy="40" r="34" pathLength="100"/>
                        <circle class="ring__bar" cx="40" cy="40" r="34" pathLength="100"/>
                    </svg>
                    <span class="gate__value">
                        <?php if ($kind === 'count') : ?><b data-count="<?= e($value) ?>"><?= e($value) ?></b><?php else : ?><b><?= e($value) ?></b><?php endif ?>
                    </span>
                    <?php if ($done) : ?><span class="gate__tick"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span><?php endif ?>
                </div>
                <p class="gate__label"><?= e($label) ?></p>
                <p class="gate__sub"><?= e($sub) ?></p>
            </li>
        <?php endforeach ?>
    </ul>

    <div class="salary-meter" data-anim style="--d:4">
        <div class="salary-meter__row"><span>Steps complete</span><span class="text-gold"><?= e($doneCount) ?> / <?= e(count($gates)) ?></span></div>
        <div class="salary-meter__bar" aria-hidden="true">
            <?php for ($k = 0; $k < count($gates); $k++) : ?><i class="<?= $k < $doneCount ? 'is-on' : '' ?>" style="--k:<?= e($k) ?>"></i><?php endfor ?>
        </div>
    </div>

    <?php if ($salary['qualified']) : ?>
        <p class="countdown-pill countdown-pill--ok" data-anim style="--d:5">
            <?= icon('check-circle', 20) ?>
            <span>Qualified · <?= e($salary['months_paid']) ?> of <?= e($salary['months']) ?> paid · next <?= e(fmt_date($salary['next_pay'])) ?></span>
        </p>
    <?php elseif ($salary['window_open']) : ?>
        <p class="countdown-pill" data-anim style="--d:5">
            <span class="pulse-dot" aria-hidden="true"></span>
            <span>Time Left: <span data-countdown="<?= e(date(DATE_ATOM, strtotime($salary['deadline']))) ?>">calculating…</span></span>
        </p>
    <?php else : ?>
        <p class="countdown-pill countdown-pill--off" data-anim style="--d:5"><?= icon('alert', 20) ?><span>Qualification window closed</span></p>
    <?php endif ?>
    <noscript><style>.salary-card [data-anim], .salary-card .ring__bar { animation: none !important; opacity: 1 !important; }</style></noscript>
</section>

<section aria-labelledby="plans-title">
    <h2 class="section-title" id="plans-title">Investment Plans</h2>
    <div class="term-grid">
        <?php foreach ($terms as $term) {
            partial('term-card', ['term' => $term]);
        } ?>
    </div>
</section>

<div class="dash-split dash-split--wide">
    <section class="card my-packages" aria-labelledby="mine-title">
        <h2 class="my-packages__title" id="mine-title">My Packages</h2>
        <p class="eyebrow">GLOW · <?= e($activeCount) ?> active</p>
        <?php if (!$plans) : ?>
            <p class="empty-dashed">No Active Packages Found</p>
        <?php else : ?>
            <ul class="owned-list">
                <?php foreach ($plans as $p) : ?>
                    <li class="owned-item">
                        <div>
                            <p class="owned-item__name"><?= e(Investment::label($p)) ?></p>
                            <p class="owned-item__meta"><?= e(Investment::progressLabel($p)) ?>
                                <?= (int) $p['days_paid'] === 0 ? '· first payout ' . e(Investment::firstPayout($p)) : '' ?></p>
                        </div>
                        <div class="owned-item__nums">
                            <p class="text-gold"><?= e(money($p['principal'])) ?></p>
                            <p class="owned-item__meta">
                                <?= $p['plan'] === 'compound'
                                    ? e(money($p['pending'])) . ' pending'
                                    : e(money($p['accrued'])) . ' locked' ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
        <a class="link-gold" href="<?= e(url('/my-packages')) ?>">Manage plans →</a>
    </section>

    <?php partial('trade-table', ['trades' => $trades]) ?>
</div>
