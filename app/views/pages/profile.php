<?php /** @var list<array{0: string, 1: string}> $items @var string $wallet */ ?>
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
