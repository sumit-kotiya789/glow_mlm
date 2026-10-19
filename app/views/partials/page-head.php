<?php /** @var string $heading @var string $subtitle @var string $icon */ ?>
<div class="page-head">
    <span class="page-head__icon"><?= icon($icon ?? 'sparkle', 24) ?></span>
    <div>
        <h1 class="page-head__title"><?= e($heading) ?></h1>
        <?php if (!empty($subtitle)) : ?><p class="page-head__sub"><?= e($subtitle) ?></p><?php endif ?>
    </div>
</div>
