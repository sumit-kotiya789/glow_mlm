<?php /** @var string $title @var string $theme classic|neon */ $theme = $theme ?? 'classic'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="<?= $theme === 'neon' ? '#0a0805' : '#0a1020' ?>">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'GLOW') ?> · GLOW</title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if ($theme === 'neon') : ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@400;600&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/neon.css')) ?>">
<?php else : ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/pages.css')) ?>">
<?php endif ?>
