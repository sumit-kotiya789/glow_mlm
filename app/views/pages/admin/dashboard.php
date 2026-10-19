<?php

/**
 * @var list<array> $stats
 * @var list<array{0: string, 1: string, 2: int, 3: string, 4: string}> $queues
 * @var string $lastRun
 * @var string $next
 * @var string $unit day|hour
 * @var string $payoutTime
 * @var list<array> $activity
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Admin Dashboard', 'subtitle' => 'Platform overview.', 'icon' => 'home']) ?>

<section class="stat-grid" aria-label="Platform totals">
    <?php foreach ($stats as $card) {
        partial('stat-card', $card);
    } ?>
</section>

<section class="queue-grid" aria-label="Waiting for you">
    <?php foreach ($queues as [$href, $label, $count, $amount, $ico]) : ?>
        <a class="queue-card<?= $count ? ' has-items' : '' ?>" href="<?= e(url($href)) ?>">
            <span class="queue-card__icon"><?= icon($ico, 24) ?></span>
            <span class="queue-card__count"><?= e($count) ?></span>
            <span class="queue-card__label"><?= e($label) ?><?= $amount !== '' ? ' · ' . e($amount) : '' ?></span>
        </a>
    <?php endforeach ?>
</section>

<div class="dash-split">
    <section class="card card--pad payout-card" aria-labelledby="payout-title">
        <h2 class="card__title" id="payout-title">Payout</h2>
        <dl class="calc-result">
            <div><dt>Mode</dt><dd class="<?= $unit === 'hour' ? 'text-gold' : '' ?>">
                <?= $unit === 'hour' ? 'Hourly (testing)' : 'Daily at ' . e($payoutTime) . ' IST' ?></dd></div>
            <div><dt>Last paid</dt><dd><?= e($lastRun) ?></dd></div>
            <div><dt>Next <?= e($unit) ?></dt><dd><?= e($next) ?></dd></div>
        </dl>
        <div class="btn-stack">
            <form method="post" action="<?= e(url('/admin/payout/run')) ?>" data-confirm="Pay every <?= e($unit) ?> that is due up to now?">
                <?= Csrf::field() ?>
                <button class="btn btn--gold btn--block" type="submit">Run Due Payouts</button>
            </form>
            <p class="field__label">Testing: pay future <?= e($unit) ?>s now</p>
            <div class="sim-row">
                <?php foreach ([1, 5, 25] as $n) : ?>
                    <form method="post" action="<?= e(url('/admin/payout/simulate')) ?>"
                          data-confirm="Pay the next <?= e($n) ?> <?= e($unit) ?><?= $n > 1 ? 's' : '' ?> now, starting <?= e($next) ?>?">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="count" value="<?= e($n) ?>">
                        <button class="btn btn--ghost btn--block" type="submit">+<?= e($n) ?> <?= e($unit) ?><?= $n > 1 ? 's' : '' ?></button>
                    </form>
                <?php endforeach ?>
            </div>
        </div>
        <p class="form__note"><?= icon('alert', 16) ?>
            <span>Switch Day / Hour in <a class="link-inline" href="<?= e(url('/admin/settings')) ?>">Settings</a>.
                Cron: <code>php cron/payout.php</code>. A period is never paid twice.</span></p>
    </section>

    <section class="card card--pad" aria-labelledby="activity-title">
        <div class="card__head">
            <h2 class="card__title" id="activity-title">Recent Activity</h2>
            <a class="link-gold" href="<?= e(url('/admin/ledger')) ?>">Ledger</a>
        </div>
        <?php partial('data-table', [
            'columns' => ['time' => 'Time', 'event' => 'Event', 'who' => 'User', 'amount' => 'Amount'],
            'rows' => $activity,
            'empty' => 'No activity yet',
        ]) ?>
    </section>
</div>
