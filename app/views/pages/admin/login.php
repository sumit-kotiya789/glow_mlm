<?php use App\Csrf; ?>
<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__band" aria-hidden="true"></span>
    <?php partial('logo-box') ?>
    <h1 class="auth-card__title" id="auth-title">Admin Panel</h1>

    <p class="hint-box"><?= icon('sparkle', 18) ?>
        <span>Demo admin: <strong>admin</strong> / <strong>admin1234</strong></span></p>

    <form method="post" action="<?= e(url('/admin/login')) ?>" novalidate data-validate>
        <?= Csrf::field() ?>
        <div class="float-field">
            <input id="username" name="username" type="text" placeholder=" " required maxlength="40"
                   autocomplete="username" spellcheck="false" value="<?= e(old('username')) ?>"
                   aria-describedby="err-username" data-required="Username is required.">
            <label for="username">Username</label>
            <?= field_error('username') ?>
        </div>
        <div class="float-field">
            <input id="password" name="password" type="password" placeholder=" " required
                   autocomplete="current-password" aria-describedby="err-password"<?= invalid_attr('password') ?>
                   data-required="Password is required.">
            <label for="password">Password</label>
            <?= field_error('password') ?>
        </div>
        <button class="btn btn--blue btn--block btn--lg" type="submit">Login</button>
        <p class="auth-card__foot">Member area? <a class="link-blue" href="<?= e(url('/login')) ?>">Member login</a></p>
    </form>
</section>
