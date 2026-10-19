<?php

/**
 * @var array $user
 * @var float $limit
 * @var float $left
 * @var float $min
 * @var float $max
 * @var float $fee
 * @var list<array> $recent
 */

use App\Csrf;

$oldSource = old('source', 'income');
$wallets = [
    'roi' => ['ROI Wallet', (float) $user['roi_balance']],
    'income' => ['Income Wallet', (float) $user['income_balance']],
];
?>
<?php partial('page-head', ['heading' => 'Withdrawal', 'subtitle' => 'Requests are sent manually by the admin to your BEP-20 wallet.', 'icon' => 'card']) ?>

<section class="stat-grid" aria-label="Balances">
    <?php partial('stat-card', ['label' => 'ROI Wallet', 'value' => money($user['roi_balance']), 'tone' => 'green']) ?>
    <?php partial('stat-card', ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'amber']) ?>
    <?php partial('stat-card', ['label' => 'Left today (limit ' . money($limit) . ')', 'value' => money($left), 'tone' => 'cyan']) ?>
</section>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="w-title">
        <h2 class="card__title" id="w-title">Request Withdrawal</h2>
        <form method="post" action="<?= e(url('/withdrawal')) ?>" class="form" novalidate data-validate
            data-fee-pct="<?= e($fee) ?>" data-confirm="Request a withdrawal of ${amount}?">
            <?= Csrf::field() ?>
            <div class="field">
                <span class="field__label">Payout wallet (BEP-20)</span>
                <div class="copy-field copy-field--sm">
                    <span class="copy-field__input"><?= e($user['wallet']) ?></span>
                    <button class="copy-field__btn" type="button" data-copy="<?= e($user['wallet']) ?>"
                        data-copy-msg="Wallet copied" aria-label="Copy wallet"><?= icon('copy', 20) ?></button>
                </div>
            </div>

            <fieldset class="choice-set">
                <legend class="field__label">From</legend>
                <div class="choice-grid">
                    <?php foreach ($wallets as $value => [$label, $balance]): ?>
                        <label class="choice">
                            <input type="radio" name="source" value="<?= e($value) ?>" <?= $oldSource === $value ? ' checked' : '' ?> required>
                            <span class="choice__title"><?= e($label) ?></span>
                            <span class="choice__desc"><?= e(money($balance)) ?> available</span>
                        </label>
                    <?php endforeach ?>
                </div>
                <?= field_error('source') ?>
            </fieldset>

            <div class="field">
                <label class="field__label" for="f-amount">Amount (USDT)</label>
                <input class="input" id="f-amount" name="amount" type="number" min="<?= e($min) ?>"
                    max="<?= e(min($max, $left)) ?>" step="0.01" inputmode="decimal" required
                    placeholder="<?= e(money($min)) ?> – <?= e(money($max)) ?>" value="<?= e(old('amount')) ?>"
                    aria-describedby="err-amount hint-amount" <?= invalid_attr('amount') ?>
                    data-invalid="Enter <?= e(money($min)) ?> to <?= e(money(min($max, $left))) ?>.">
                <p class="field__hint" id="hint-amount">
                    <?= e(money($min)) ?> – <?= e(money($max)) ?> per request · fee <?= e(pct($fee)) ?> ·
                    daily limit = your investment (last matured plans if none is running). Plan principal: withdraw from <a class="link-inline"
                        href="<?= e(url('/my-packages')) ?>">My Packages</a>.
                </p>
                <?= field_error('amount') ?>
            </div>
            <?php partial('txn-field', ['id' => 'f-txn-w', 'errKey' => 'txn_password']) ?>
            <dl class="fee-box" data-fee-box aria-live="polite">
                <div>
                    <dt>Amount</dt>
                    <dd data-fee-amount>$0.00</dd>
                </div>
                <div>
                    <dt>Fee (
                        <?= e(pct($fee)) ?>)
                    </dt>
                    <dd data-fee-fee>$0.00</dd>
                </div>
                <div>
                    <dt>You get</dt>
                    <dd class="is-gold" data-fee-net>$0.00</dd>
                </div>
            </dl>

            <button class="btn btn--gold btn--block btn--lg" type="submit">Request Withdrawal</button>
        </form>
    </section>

    <section class="card card--pad" aria-labelledby="recent-title">
        <div class="card__head">
            <h2 class="card__title" id="recent-title">Recent Withdrawals</h2>
            <a class="link-gold" href="<?= e(url('/withdrawal-history')) ?>">View all</a>
        </div>
        <?php partial('data-table', [
            'columns' => ['date' => 'Date', 'source' => 'From', 'amount' => 'Amount', 'net' => 'You Get', 'status' => 'Status', 'bsc' => 'Verify in BSC'],
            'rows' => $recent,
            'empty' => 'No withdrawals yet',
        ]) ?>
    </section>
</div>