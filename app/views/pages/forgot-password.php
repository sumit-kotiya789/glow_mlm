<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__band" aria-hidden="true"></span>
    <?php partial('logo-box') ?>
    <h1 class="auth-card__title" id="auth-title">Forgot Password</h1>
    <p class="hint-box"><?= icon('alert', 18) ?>
        <span>Password reset is disabled. Contact admin to reset password.</span></p>
    <a class="btn btn--blue btn--block btn--lg" href="<?= e(url('/login')) ?>">Back to Login</a>
</section>
