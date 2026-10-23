<?php /** Public marketing shell. @var string $content */ ?>
<!doctype html>
<html lang="en">
<head>
<?php partial('head', ['title' => $title ?? 'Welcome', 'theme' => 'neon']) ?>
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
<script>document.documentElement.classList.add('js')</script>
</head>
<body class="guest ui-neon landing">
<a class="lp-skip" href="#main">Skip to content</a>
<?= $content ?>
<script src="<?= e(asset('js/ticker.js')) ?>" defer></script>
<script src="<?= e(asset('js/landing.js')) ?>" defer></script>
</body>
</html>
