<?php

/**
 * @var array $user
 * @var float $min
 * @var bool $canSend
 * @var float $sendable
 * @var list<array> $recent
 */

use App\Csrf;
use App\Models\Transfer;

$oldSource = old('source', 'income');
$wallets = [];
foreach (Transfer::SOURCES as $value => $label) {
    $wallets[$value] = [$label, (float) $user[$value . '_balance']];
}
?>
<?php partial('page-head', ['heading' => 'Transfer', 'subtitle' => 'Send to another member, or move Compound ROI / FD ROI / Income into your Deposit wallet. No fee.', 'icon' => 'transfer']) ?>

<section class="stat-grid stat-grid--4" aria-label="Balances">
    <?php partial('stat-card', ['label' => 'Deposit Wallet', 'value' => money($user['deposit_balance']), 'tone' => 'cyan']) ?>
    <?php partial('stat-card', ['label' => 'Compound ROI Wallet', 'value' => money($user['roi_balance']), 'tone' => 'green']) ?>
    <?php partial('stat-card', ['label' => 'FD ROI Wallet', 'value' => money($user['fd_balance']), 'tone' => 'cyan']) ?>
    <?php partial('stat-card', ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'amber']) ?>
</section>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="p2p-title">
        <h2 class="card__title" id="p2p-title">P2P Transfer</h2>
        <p class="field__hint">From your Deposit wallet to another member's Deposit wallet.</p>
        <?php if (!$canSend) : ?>
            <p class="form__note"><?= icon('alert', 16) ?> P2P unlocks after your first approved deposit.
                <a class="link-inline" href="<?= e(url('/deposit')) ?>">Deposit now</a></p>
        <?php endif ?>
        <form method="post" action="<?= e(url('/transfer/p2p')) ?>" class="form" novalidate data-validate
            data-confirm="Send ${p2p_amount} to {receiver_name} ({user_code})? Check the name before confirming.">
            <?= Csrf::field() ?>
            <input type="hidden" name="receiver_name" data-lookup-name>
            <div class="field">
                <label class="field__label" for="f-code">Receiver User ID</label>
                <input class="input input--mono" id="f-code" name="user_code" type="text" required maxlength="12"
                    autocomplete="off" spellcheck="false" autocapitalize="characters" placeholder="GL0000001"
                    value="<?= e(old('user_code')) ?>" aria-describedby="err-user_code hint-code"
                    <?= invalid_attr('user_code') ?> data-required="Enter the receiver's User ID."
                    data-lookup="<?= e(url('/transfer/lookup')) ?>" data-lookup-label="Receiver">
                <p class="field__hint" id="hint-code" data-lookup-out aria-live="polite">Enter the ID to see the receiver's name.</p>
                <?= field_error('user_code') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-p2p-amount">Amount (USDT)</label>
                <input class="input" id="f-p2p-amount" name="p2p_amount" type="number" min="<?= e($min) ?>"
                    max="<?= e($sendable) ?>" step="0.01" inputmode="decimal" required
                    placeholder="<?= e(money($min)) ?> – <?= e(money($sendable)) ?>"
                    value="<?= e(old('p2p_amount')) ?>" aria-describedby="err-p2p_amount hint-p2p-amount"
                    <?= invalid_attr('p2p_amount') ?>
                    data-invalid="Enter <?= e(money($min)) ?> to <?= e(money($sendable)) ?>.">
                <p class="field__hint" id="hint-p2p-amount">You can send <?= e(money($sendable)) ?> (signup bonus can't be sent) · minimum <?= e(money($min)) ?> · no fee · instant.</p>
                <?= field_error('p2p_amount') ?>
            </div>
            <?php partial('txn-field', ['id' => 'f-txn-p2p', 'errKey' => 'p2p_txn_password']) ?>
            <button class="btn btn--gold btn--block btn--lg" type="submit"<?= $canSend ? '' : ' disabled' ?>>Send</button>
        </form>
    </section>

    <section class="card card--pad" aria-labelledby="move-title">
        <h2 class="card__title" id="move-title">Move to Deposit Wallet</h2>
        <p class="field__hint">Your own Compound ROI, FD ROI or Income wallet into your own Deposit wallet, for investing.</p>
        <form method="post" action="<?= e(url('/transfer/wallet')) ?>" class="form" novalidate data-validate
            data-confirm="Move ${move_amount} to your Deposit wallet?">
            <?= Csrf::field() ?>
            <fieldset class="choice-set">
                <legend class="field__label">From</legend>
                <div class="choice-grid choice-grid--3">
                    <?php foreach ($wallets as $value => [$label, $balance]) : ?>
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
                <label class="field__label" for="f-move-amount">Amount (USDT)</label>
                <input class="input" id="f-move-amount" name="move_amount" type="number" min="<?= e($min) ?>" step="0.01"
                    inputmode="decimal" required placeholder="<?= e(money($min)) ?> or more"
                    value="<?= e(old('move_amount')) ?>" aria-describedby="err-move_amount hint-move-amount"
                    <?= invalid_attr('move_amount') ?> data-invalid="Enter at least <?= e(money($min)) ?>.">
                <p class="field__hint" id="hint-move-amount">Minimum <?= e(money($min)) ?> · no fee · instant.</p>
                <?= field_error('move_amount') ?>
            </div>
            <?php partial('txn-field', ['id' => 'f-txn-move', 'errKey' => 'move_txn_password']) ?>
            <button class="btn btn--gold btn--block btn--lg" type="submit">Move to Deposit</button>
        </form>
    </section>
</div>

<section class="card card--pad" aria-labelledby="recent-title">
    <div class="card__head">
        <h2 class="card__title" id="recent-title">Recent Transfers</h2>
        <a class="link-gold" href="<?= e(url('/transfer-history')) ?>">View all</a>
    </div>
    <?php partial('data-table', [
        'columns' => ['date' => 'Date', 'type' => 'Type', 'note' => 'Detail', 'amount' => 'Amount'],
        'rows' => $recent,
        'empty' => 'No transfers yet',
    ]) ?>
</section>
