<?php /** @var list<array{label: string, href: string, active: bool}> $tabs */ ?>
<nav class="tabs" aria-label="Filter">
    <?php foreach ($tabs as $tab) : ?>
        <a class="tabs__item<?= $tab['active'] ? ' is-active' : '' ?>" href="<?= e($tab['href']) ?>"
            <?= $tab['active'] ? 'aria-current="page"' : '' ?>><?= e($tab['label']) ?></a>
    <?php endforeach ?>
</nav>
