<?php

/**
 * @var string $address
 * @var float $min
 * @var list<array> $recent
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Deposit', 'subtitle' => 'Send USDT (BEP-20), then submit the transaction hash for admin approval.', 'icon' => 'dollar']) ?>

<div class="dash-split">
    <section class="card deposit-card" aria-labelledby="deposit-title">
        <h2 class="deposit-card__head" id="deposit-title">Instant Deposit Fund</h2>
        <form method="post" action="<?= e(url('/deposit')) ?>" class="form deposit-card__body" novalidate data-validate>
            <?= Csrf::field() ?>
            <div class="field">
                <label class="field__label" for="f-amount">Fund Amount (USDT)</label>
                <input class="input" id="f-amount" name="amount" type="number" min="<?= e($min) ?>" step="0.01" inputmode="decimal"
                       required placeholder="Enter USDT Amount" value="<?= e(old('amount')) ?>"
                       aria-describedby="err-amount"<?= invalid_attr('amount') ?> data-invalid="Enter at least <?= e(money($min)) ?>.">
                <?= field_error('amount') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-hash">Transaction Hash</label>
                <input class="input input--mono" id="f-hash" name="tx_hash" type="text" required pattern="0x[a-fA-F0-9]{64}"
                       maxlength="66" autocomplete="off" spellcheck="false" placeholder="Paste Transaction Hash"
                       value="<?= e(old('tx_hash')) ?>" aria-describedby="err-tx_hash hint-hash"<?= invalid_attr('tx_hash') ?>
                       data-invalid="Transaction hash is 0x followed by 64 hex characters.">
                <p class="field__hint text-gold" id="hint-hash">Example : 0x123456789abcdef....</p>
                <?= field_error('tx_hash') ?>
            </div>
            <div class="btn-row">
                <button class="btn btn--gold btn--lg" type="submit">Submit</button>
                <button class="btn btn--ghost btn--lg" type="reset">Reset</button>
            </div>
        </form>
    </section>

    <section class="card address-card" aria-labelledby="address-title">
        <h2 class="address-card__title" id="address-title">USDT (BEP-20) Wallet Address</h2>
        <div class="qr-box" data-qr="<?= e($address) ?>" role="img" aria-label="QR code of the deposit address"></div>
        <p class="address-card__address"><?= e($address) ?></p>
        <button class="btn btn--gold" type="button" data-copy="<?= e($address) ?>" data-copy-msg="Address copied">Copy Address</button>
    </section>
</div>

<section class="card card--pad" aria-labelledby="recent-title">
    <div class="card__head">
        <h2 class="card__title" id="recent-title">Recent Deposits</h2>
        <a class="link-gold" href="<?= e(url('/deposit-history')) ?>">View all</a>
    </div>
    <?php partial('data-table', [
        'columns' => ['date' => 'Date', 'hash' => 'Tx Hash', 'bsc' => 'Verify in BSC', 'amount' => 'Amount', 'status' => 'Status', 'note' => 'Note'],
        'rows' => $recent,
        'empty' => 'No deposits yet',
    ]) ?>
</section>
