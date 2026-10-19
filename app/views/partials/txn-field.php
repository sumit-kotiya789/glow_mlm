<?php

/**
 * Transaction password input for withdrawal / P2P / transfer forms.
 *
 * @var string $id optional input id
 * @var string $errKey optional error key (default txn_password)
 */

$id = $id ?? 'f-txn';
$errKey = $errKey ?? 'txn_password';
?>
<div class="field">
    <label class="field__label" for="<?= e($id) ?>">Transaction password</label>
    <input class="input" id="<?= e($id) ?>" name="txn_password" type="password" required maxlength="64"
        autocomplete="off" placeholder="Enter transaction password" aria-describedby="err-<?= e($errKey) ?>"
        <?= invalid_attr($errKey) ?> data-required="Enter your transaction password.">
    <?php if (!has_txn_password()) : ?>
        <p class="field__hint text-gold"><?= icon('alert', 16) ?> You have not set one yet.
            <a class="link-inline" href="<?= e(url('/profile#txn')) ?>">Set it in Profile</a> first.</p>
    <?php endif ?>
    <?= field_error($errKey) ?>
</div>
