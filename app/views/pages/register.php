<?php

use App\Csrf;

/** @var list<string> $countries */

$fields = [
    ['sponsor_code', 'Sponsor ID *', 'text', [
        'pattern' => '[Gg][Ll][0-9]{7}', 'maxlength' => 9, 'autocapitalize' => 'characters', 'autocomplete' => 'off',
        'data-invalid' => 'Sponsor ID looks like GL0000001.',
        'data-lookup' => url('/register/sponsor'), 'data-lookup-label' => 'Sponsor', 'data-lookup-popup' => '',
    ]],
    ['name', 'Full Name *', 'text', ['minlength' => 2, 'maxlength' => 80, 'autocomplete' => 'name',
        'data-invalid' => 'Enter your full name (2-80 characters).']],
    ['country', 'Select Country *', 'select', []],
    ['email', 'Email Id *', 'email', ['maxlength' => 120, 'autocomplete' => 'email',
        'data-invalid' => 'Enter a valid email address.']],
    ['mobile', 'Mobile No. *', 'tel', ['pattern' => '\+?[0-9\s\-]{7,20}', 'autocomplete' => 'tel', 'inputmode' => 'tel',
        'data-invalid' => 'Enter 7-15 digits, optional leading +.']],
    ['wallet', 'USDT (BEP-20) Wallet Address *', 'text', [
        'pattern' => '0x[a-fA-F0-9]{40}', 'maxlength' => 42, 'autocomplete' => 'off', 'spellcheck' => 'false',
        'data-invalid' => 'Wallet must be 0x followed by 40 hex characters.',
    ]],
    ['password', 'Set Login Password *', 'password', ['minlength' => 8, 'autocomplete' => 'new-password',
        'data-invalid' => 'Password must be at least 8 characters.']],
    ['txn_password', 'Set Transaction Password * (withdraw / transfer)', 'password', ['minlength' => 6, 'maxlength' => 64, 'autocomplete' => 'new-password',
        'data-invalid' => 'Transaction password must be at least 6 characters.']],
];
$sponsorDefault = isset($_GET['ref']) && is_string($_GET['ref']) ? strtoupper($_GET['ref']) : 'GL0000001';
?>
<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__band" aria-hidden="true"></span>
    <?php partial('logo-box') ?>
    <h1 class="auth-card__title" id="auth-title">Create An Account</h1>

    <form method="post" action="<?= e(url('/register')) ?>" novalidate data-validate>
        <?= Csrf::field() ?>
        <?php foreach ($fields as [$name, $label, $type, $attrs]) :
            $value = old($name, $name === 'sponsor_code' ? $sponsorDefault : '');
            $attrHtml = '';
            foreach ($attrs as $k => $v) {
                $attrHtml .= ' ' . e($k) . '="' . e($v) . '"';
            } ?>
            <div class="float-field">
                <?php if ($type === 'select') : ?>
                    <select id="<?= e($name) ?>" name="<?= e($name) ?>" required aria-describedby="err-<?= e($name) ?>"<?= invalid_attr($name) ?>
                            data-required="Select your country.">
                        <option value=""></option>
                        <?php foreach ($countries as $country) : ?>
                            <option<?= $country === $value ? ' selected' : '' ?>><?= e($country) ?></option>
                        <?php endforeach ?>
                    </select>
                <?php else : ?>
                    <input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" placeholder=" " required
                           value="<?= $type === 'password' ? '' : e($value) ?>"
                           aria-describedby="err-<?= e($name) ?>"<?= invalid_attr($name) ?><?= $attrHtml ?>>
                <?php endif ?>
                <label for="<?= e($name) ?>"><?= e($label) ?></label>
                <?php if ($name === 'sponsor_code') : ?>
                    <p class="field__hint float-field__hint" data-lookup-out aria-live="polite">Enter the ID to see your sponsor's name.</p>
                <?php endif ?>
                <?= field_error($name) ?>
            </div>
        <?php endforeach ?>

        <label class="check check--terms">
            <input type="checkbox" name="terms" value="1" required aria-describedby="err-terms"
                   data-required="Please accept the terms of service."<?= old('terms') ? ' checked' : '' ?>>
            <span>I agree to the terms of service</span>
        </label>
        <?= field_error('terms') ?>

        <button class="btn btn--blue btn--block btn--lg" type="submit">Register Now</button>
        <p class="auth-card__foot">Already have an account? <a class="link-blue" href="<?= e(url('/login')) ?>">Login here</a></p>
    </form>
</section>
