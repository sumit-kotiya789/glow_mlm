<?php

/**
 * Admin shell: same design system, sidebar docked on desktop (>= 1024px).
 *
 * @var string $content
 */

?>
<!doctype html>
<html lang="en">
<head>
<?php partial('head', ['title' => ($title ?? 'Admin') . ' · Admin']) ?>
</head>
<body class="app page-dark page-admin">
<a class="skip-link" href="#main">Skip to content</a>
<?php partial('header', ['admin' => true]) ?>
<?php partial('admin-sidebar') ?>
<main id="main" class="page" tabindex="-1">
<?= $content ?>
</main>
<?php partial('modal') ?>
<?php partial('toast') ?>
<script src="<?= e(asset('js/toast.js')) ?>" defer></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/sidebar.js')) ?>" defer></script>
<script src="<?= e(asset('js/modal.js')) ?>" defer></script>
<script src="<?= e(asset('js/table-search.js')) ?>" defer></script>
</body>
</html>
