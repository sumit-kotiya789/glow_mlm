<?php

/**
 * Generic list page: header, optional summary cards / search / tabs, table, pagination.
 *
 * @var string $heading
 * @var string $subtitle
 * @var string $icon
 * @var array<string, string> $columns
 * @var list<array> $rows
 * @var string $empty
 */

?>
<?php partial('page-head', ['heading' => $heading, 'subtitle' => $subtitle, 'icon' => $icon]) ?>

<?php if (!empty($summary)) : ?>
    <section class="stat-grid" aria-label="Summary">
        <?php foreach ($summary as $card) {
            partial('stat-card', $card);
        } ?>
    </section>
<?php endif ?>

<?php if (!empty($tree)) {
    partial('team-tree', ['tree' => $tree]);
} ?>

<section class="card card--pad">
    <?php if (isset($search)) : ?>
        <form method="get" action="<?= e(url(current_path())) ?>" class="search-form" role="search">
            <label class="search">
                <span class="sr-only">Search</span>
                <?= icon('search', 20) ?>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e($searchPlaceholder ?? 'Search…') ?>">
            </label>
            <button class="btn btn--gold" type="submit">Search</button>
        </form>
    <?php endif ?>

    <?php if (!empty($tabs)) {
        partial('tabs', ['tabs' => $tabs]);
    } ?>

    <?php partial('data-table', ['columns' => $columns, 'rows' => $rows, 'empty' => $empty]) ?>
    <?php if (!empty($pager)) {
        partial('pagination', ['pager' => $pager]);
    } ?>
</section>
