<?php

/**
 * Authenticated shell: neon top bar, docked sidebar (desktop) / bottom bar + sheet (mobile), toast + modal hosts.
 *
 * @var string $content
 */

?>
<!doctype html>
<html lang="en">
<head>
<?php partial('head', ['title' => $title ?? 'Dashboard', 'theme' => 'neon']) ?>
</head>
<body class="app ui-neon">
<a class="skip-link" href="#main">Skip to content</a>
<div class="neon-bg" aria-hidden="true"></div>
<?php partial('topbar', ['title' => $title ?? 'Dashboard']) ?>
<?php partial('sidebar') ?>
<main id="main" class="page" tabindex="-1">
<?= $content ?>
</main>
<?php partial('bottom-nav') ?>
<?php partial('modal') ?>
<?php partial('notice') ?>
<?php partial('toast') ?>
<script src="<?= e(asset('js/toast.js')) ?>" defer></script>
<script src="<?= e(asset('js/notice.js')) ?>" defer></script>
<?php foreach ($scripts ?? [] as $script) : ?>
<script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/nav.js')) ?>" defer></script>
<script src="<?= e(asset('js/modal.js')) ?>" defer></script>
<script src="<?= e(asset('js/fee-preview.js')) ?>" defer></script>
<script src="<?= e(asset('js/countdown.js')) ?>" defer></script>
<script src="<?= e(asset('js/table-search.js')) ?>" defer></script>
</body>
</html>
