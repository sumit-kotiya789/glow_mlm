<?php use App\Csrf; ?>
<section class="auth-card auth-card--login" aria-labelledby="auth-title">
    <span class="auth-card__band" aria-hidden="true"></span>
    <?php partial('logo-box') ?>
    <h1 class="auth-card__title" id="auth-title">Sign Into Your Account</h1>

    <p class="hint-box"><?= icon('sparkle', 18) ?>
        <span>Demo login: <strong>GL0000001</strong> / <strong>demo1234</strong></span></p>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate data-validate>
        <?= Csrf::field() ?>
        <div class="float-field">
            <input id="user_code" name="user_code" type="text" placeholder=" " required maxlength="12"
                   autocomplete="username" autocapitalize="characters" spellcheck="false"
                   value="<?= e(old('user_code')) ?>" aria-describedby="err-user_code"<?= invalid_attr('user_code') ?>
                   data-required="User ID is required.">
            <label for="user_code">User ID</label>
            <?= field_error('user_code') ?>
        </div>
        <div class="float-field">
            <input id="password" name="password" type="password" placeholder=" " required
                   autocomplete="current-password" aria-describedby="err-password"<?= invalid_attr('password') ?>
                   data-required="Password is required.">
            <label for="password">User Password</label>
            <?= field_error('password') ?>
        </div>

        <div class="auth-row">
            <label class="check"><input type="checkbox" name="remember" value="1"> <span>Remember me</span></label>
            <a class="link-blue" href="<?= e(url('/forgot-password')) ?>">Forgot Password?</a>
        </div>

        <button class="btn btn--blue btn--block btn--lg" type="submit">Login Now</button>
        <p class="auth-card__foot">Don't have an account? <a class="link-blue" href="<?= e(url('/register')) ?>">Register here</a></p>
    </form>
</section>
