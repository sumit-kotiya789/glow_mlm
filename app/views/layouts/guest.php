<?php

/**
 * Guest shell: navy backdrop with gold light streaks and network dots.
 *
 * @var string $content
 */

$neon = !str_starts_with(current_path(), '/admin'); // admin login keeps the classic look
?>
<!doctype html>
<html lang="en">
<head>
<?php partial('head', ['title' => $title ?? 'Welcome', 'theme' => $neon ? 'neon' : 'classic']) ?>
</head>
<body class="guest<?= $neon ? ' ui-neon' : '' ?>">
<div class="guest-bg" aria-hidden="true">
    <span class="guest-bg__dots"></span>
    <span class="guest-bg__streak guest-bg__streak--top"></span>
    <span class="guest-bg__streak guest-bg__streak--bottom"></span>
    <span class="guest-bg__orb"></span>
</div>
<header class="guest-header">
    <span class="demo-badge">WEALTH IN PROGRESS</span>
</header>
<main id="main" class="guest-main">
<?= $content ?>
</main>
<?php if ($neon) {
    partial('notice');
} ?>
<?php partial('toast') ?>
<script src="<?= e(asset('js/toast.js')) ?>" defer></script>
<script src="<?= e(asset('js/notice.js')) ?>" defer></script>
<?php foreach ($scripts ?? [] as $script) : ?>
<script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
