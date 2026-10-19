<?php

use App\Csrf;

/** @var list<array{0: string, 1: string}> $items @var string $wallet @var bool $hasTxn */
?>
<h1 class="profile-eyebrow">User Detail</h1>
<section class="profile-card" aria-labelledby="profile-title">
    <h2 class="profile-card__title" id="profile-title">My Profile Info</h2>
    <dl class="profile-list">
        <?php foreach ($items as [$label, $value]) : ?>
            <div class="profile-list__item">
                <dt><?= e($label) ?></dt>
                <dd><?= e($value) ?></dd>
            </div>
        <?php endforeach ?>
        <div class="profile-list__item">
            <dt>Wallet (BEP20)</dt>
            <dd class="wallet-text"><?= e($wallet) ?>
                <button class="copy-mini" type="button" data-copy="<?= e($wallet) ?>" data-copy-msg="Wallet copied"
                        aria-label="Copy wallet address"><?= icon('copy', 16) ?></button></dd>
        </div>
    </dl>
</section>

<section class="card card--pad" id="txn" aria-labelledby="txn-title">
    <h2 class="card__title" id="txn-title"><?= $hasTxn ? 'Change Transaction Password' : 'Set Transaction Password' ?></h2>
    <p class="field__hint">Needed for every withdrawal, P2P transfer and wallet transfer. Keep it different from your login password.</p>
    <form method="post" action="<?= e(url('/profile/txn-password')) ?>" class="form" novalidate data-validate>
        <?= Csrf::field() ?>
        <?php if ($hasTxn) : ?>
            <div class="field">
                <label class="field__label" for="f-cur-txn">Current transaction password</label>
                <input class="input" id="f-cur-txn" name="current_txn_password" type="password" required maxlength="64"
                    autocomplete="off" aria-describedby="err-current_txn_password" <?= invalid_attr('current_txn_password') ?>
                    data-required="Enter your current transaction password.">
                <?= field_error('current_txn_password') ?>
            </div>
        <?php else : ?>
            <div class="field">
                <label class="field__label" for="f-login-pw">Login password (to confirm it is you)</label>
                <input class="input" id="f-login-pw" name="login_password" type="password" required
                    autocomplete="current-password" aria-describedby="err-login_password" <?= invalid_attr('login_password') ?>
                    data-required="Enter your login password.">
                <?= field_error('login_password') ?>
            </div>
        <?php endif ?>
        <div class="field">
            <label class="field__label" for="f-new-txn">New transaction password</label>
            <input class="input" id="f-new-txn" name="new_txn_password" type="password" required minlength="6" maxlength="64"
                autocomplete="new-password" aria-describedby="err-new_txn_password" <?= invalid_attr('new_txn_password') ?>
                data-required="Enter a new transaction password." data-invalid="At least 6 characters.">
            <?= field_error('new_txn_password') ?>
        </div>
        <div class="field">
            <label class="field__label" for="f-confirm-txn">Confirm new transaction password</label>
            <input class="input" id="f-confirm-txn" name="confirm_txn_password" type="password" required maxlength="64"
                autocomplete="new-password" aria-describedby="err-confirm_txn_password" <?= invalid_attr('confirm_txn_password') ?>
                data-required="Repeat the new password.">
            <?= field_error('confirm_txn_password') ?>
        </div>
        <button class="btn btn--gold btn--block btn--lg" type="submit"><?= $hasTxn ? 'Change Password' : 'Set Password' ?></button>
    </form>
</section>
