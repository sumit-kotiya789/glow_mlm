<?php

/**
 * @var array{rows: list<array>, page: int, pages: int, total: int} $pager
 * @var string $defaultPrize
 * @var string $defaultWinners
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Lucky Draws', 'subtitle' => 'Create a draw, hand-pick favourites, fill the rest at random, then distribute.', 'icon' => 'gift']) ?>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="new-draw">
        <h2 class="card__title" id="new-draw">New Draw</h2>
        <form method="post" action="<?= e(url('/admin/lucky-draws')) ?>" class="form" novalidate data-validate>
            <?= Csrf::field() ?>
            <div class="field">
                <label class="field__label" for="f-title">Title</label>
                <input class="input" id="f-title" name="title" required minlength="3" maxlength="80"
                       value="<?= e(old('title', 'Lucky Draw ' . date('d M Y'))) ?>" aria-describedby="err-title"<?= invalid_attr('title') ?>>
                <?= field_error('title') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-prize">Prize per winner ($)</label>
                <input class="input" id="f-prize" name="prize" type="number" min="0.01" step="0.01" required
                       value="<?= e(old('prize', $defaultPrize)) ?>" aria-describedby="err-prize"<?= invalid_attr('prize') ?>>
                <?= field_error('prize') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-count">Number of winners</label>
                <input class="input" id="f-count" name="winners_count" type="number" min="1" max="9999" step="1" required
                       value="<?= e(old('winners_count', $defaultWinners)) ?>" aria-describedby="err-winners_count"<?= invalid_attr('winners_count') ?>>
                <?= field_error('winners_count') ?>
            </div>
            <button class="btn btn--gold btn--block" type="submit">Create Draw</button>
        </form>
    </section>

    <section class="card card--pad" aria-labelledby="draws-title">
        <h2 class="card__title" id="draws-title">Draws</h2>
        <?php partial('data-table', [
            'columns' => ['title' => 'Draw', 'prize' => 'Prize', 'slots' => 'Winners', 'status' => 'Status', 'date' => 'Created'],
            'rows' => array_map(static fn ($d) => [
                'title' => [$d['title'], 'is-gold', url('/admin/lucky-draw?id=' . $d['id'])],
                'prize' => [money($d['prize']), 'num'],
                'slots' => $d['picked'] . ' / ' . $d['winners_count'],
                'status' => [$d['status'], $d['status'] === 'Draft' ? 'is-gold' : 'is-green'],
                'date' => fmt_date($d['created_at']),
            ], $pager['rows']),
            'empty' => 'No draws yet',
        ]) ?>
        <?php partial('pagination', ['pager' => $pager]) ?>
    </section>
</div>
