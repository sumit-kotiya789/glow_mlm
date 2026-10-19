<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__band" aria-hidden="true"></span>
    <?php partial('logo-box') ?>
    <h1 class="auth-card__title" id="auth-title">Forgot Password</h1>
    <p class="hint-box"><?= icon('alert', 18) ?>
        <span>Password reset is disabled in demo mode. No email is ever sent. Use <strong>GL0000001</strong> / <strong>demo1234</strong>.</span></p>
    <a class="btn btn--blue btn--block btn--lg" href="<?= e(url('/login')) ?>">Back to Login</a>
</section>
