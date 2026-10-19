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
    ['/direct-team', 'Team', 'users'],
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

<section class="salary-card" aria-labelledby="salary-title">
        <span class="salary-card__watermark" aria-hidden="true"><?= icon('gift', 150) ?></span>
        <h2 class="salary-card__title" id="salary-title">Monthly Salary</h2>
        <p class="salary-card__sub">Earn <?= e(money($salary['amount'])) ?> per month for <?= e($salary['months']) ?> months!
            (<?= e(money($salary['amount'] * $salary['months'])) ?> Total)</p>

        <div class="salary-row">
            <span>Self investment <?= e(money($salary['self_need'])) ?>+
                <small class="text-muted">(<?= e(money($salary['self'])) ?>)</small></span>
            <span class="<?= $selfOk ? 'text-green' : 'text-muted' ?>" aria-label="<?= $selfOk ? 'Completed' : 'Not completed' ?>">
                <?= icon($selfOk ? 'check-circle' : 'circle', 26) ?>
            </span>
        </div>
        <?php foreach ($conditions as [$label, $have, $need]) :
            $shown = min($have, $need); ?>
            <div class="salary-level">
                <div class="salary-level__row">
                    <span><?= e($label) ?> (<?= e($need) ?>)</span>
                    <span class="text-gold salary-level__count"><?= e($shown) ?> / <?= e($need) ?></span>
                </div>
                <progress class="progress" max="<?= e($need) ?>" value="<?= e($shown) ?>" aria-label="<?= e($label) ?>"><?= e($shown) ?></progress>
            </div>
        <?php endforeach ?>

        <?php if ($salary['qualified']) : ?>
            <p class="countdown-pill countdown-pill--ok">
                <?= icon('check-circle', 20) ?>
                <span>Qualified · <?= e($salary['months_paid']) ?> of <?= e($salary['months']) ?> paid · next <?= e(fmt_date($salary['next_pay'])) ?></span>
            </p>
        <?php elseif ($salary['window_open']) : ?>
            <p class="countdown-pill">
                <?= icon('clock', 20) ?>
                <span>Time Left: <span data-countdown="<?= e(date(DATE_ATOM, strtotime($salary['deadline']))) ?>">calculating…</span></span>
            </p>
        <?php else : ?>
            <p class="countdown-pill countdown-pill--off"><?= icon('alert', 20) ?><span>Qualification window closed</span></p>
        <?php endif ?>
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
