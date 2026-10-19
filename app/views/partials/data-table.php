<?php

/**
 * Generic table: stacked cards under 640px, normal table above.
 *
 * @var array<string, string> $columns key => label
 * @var list<array<string, string|array>> $rows cell = text, [text, class] or [text, class, href]
 * @var string $empty
 */

?>
<?php if (!$rows) : ?>
    <div class="empty-state"><?= icon('inbox', 30) ?><p><?= e($empty ?? 'Nothing here yet') ?></p></div>
<?php else : ?>
    <div class="table-wrap skeleton-load">
        <table class="table table--stack">
            <thead>
                <tr><?php foreach ($columns as $label) : ?><th scope="col"><?= e($label) ?></th><?php endforeach ?></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                <?php foreach ($columns as $key => $label) :
                    $cell = $row[$key] ?? '';
                    [$text, $class, $href, $external] = is_array($cell) ? $cell + [1 => '', 2 => null, 3 => false] : [$cell, '', null, false]; ?>
                    <td data-label="<?= e($label) ?>"<?= $class ? ' class="' . e($class) . '"' : '' ?>><?php if ($href) : ?><a class="link-cell" href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($text) ?></a><?php else : ?><?= e($text) ?><?php endif ?></td>
                <?php endforeach ?>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
