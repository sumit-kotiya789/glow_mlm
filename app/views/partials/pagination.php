<?php

/** @var array{page: int, pages: int, total: int} $pager */

if ($pager['pages'] <= 1) {
    return;
}
$link = static function (int $page): string {
    $query = array_merge($_GET, ['page' => $page]);
    return url(current_path()) . '?' . http_build_query($query);
};
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($pager['page'] > 1) : ?>
        <a class="pagination__btn" href="<?= e($link($pager['page'] - 1)) ?>" rel="prev"><?= icon('chevron-left', 18) ?><span>Prev</span></a>
    <?php else : ?>
        <span class="pagination__btn is-disabled" aria-disabled="true"><?= icon('chevron-left', 18) ?><span>Prev</span></span>
    <?php endif ?>
    <span class="pagination__info">Page <?= e($pager['page']) ?> of <?= e($pager['pages']) ?> · <?= e($pager['total']) ?> rows</span>
    <?php if ($pager['page'] < $pager['pages']) : ?>
        <a class="pagination__btn" href="<?= e($link($pager['page'] + 1)) ?>" rel="next"><span>Next</span><?= icon('chevron-right', 18) ?></a>
    <?php else : ?>
        <span class="pagination__btn is-disabled" aria-disabled="true"><span>Next</span><?= icon('chevron-right', 18) ?></span>
    <?php endif ?>
</nav>
