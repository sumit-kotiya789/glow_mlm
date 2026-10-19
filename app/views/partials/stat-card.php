<?php

/**
 * @var string $label
 * @var string $value
 * @var string $tone gold|cyan|green|amber|red|muted
 */

?>
<div class="stat-card stat-card--<?= e($tone ?? 'muted') ?>">
    <p class="stat-card__label"><?= e($label) ?></p>
    <p class="stat-card__value"><?= e($value) ?></p>
</div>
