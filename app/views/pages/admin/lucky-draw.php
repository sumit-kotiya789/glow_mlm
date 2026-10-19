<?php

/**
 * @var array $draw
 * @var list<array> $winners
 * @var string $query
 * @var list<array> $results
 */

use App\Csrf;

$draft = $draw['status'] === 'Draft';
$picked = count($winners);
$slots = (int) $draw['winners_count'];
$winnerIds = array_column($winners, 'user_id');
?>
<?php partial('page-head', ['heading' => $draw['title'], 'subtitle' => $draft ? 'Draft: pick winners, then distribute.' : 'Distributed ' . fmt_date($draw['distributed_at'], 'd-M-Y H:i') . '.', 'icon' => 'gift']) ?>

<section class="stat-grid" aria-label="Draw">
    <?php partial('stat-card', ['label' => 'Prize per winner', 'value' => money($draw['prize']), 'tone' => 'gold']) ?>
    <?php partial('stat-card', ['label' => 'Winners picked', 'value' => $picked . ' / ' . $slots, 'tone' => 'cyan']) ?>
    <?php partial('stat-card', ['label' => 'Total payout', 'value' => money($draw['prize'] * $picked), 'tone' => 'green']) ?>
</section>

<?php if ($draft) : ?>
    <div class="dash-split">
        <section class="card card--pad" aria-labelledby="pick-title">
            <h2 class="card__title" id="pick-title">1. Hand-pick favourites</h2>
            <form method="get" action="<?= e(url('/admin/lucky-draw')) ?>" class="search-form" role="search">
                <input type="hidden" name="id" value="<?= e($draw['id']) ?>">
                <label class="search">
                    <span class="sr-only">Search members</span>
                    <?= icon('search', 20) ?>
                    <input type="search" name="q" value="<?= e($query) ?>" placeholder="User ID, name or email">
                </label>
                <button class="btn btn--gold" type="submit">Search</button>
            </form>
            <?php if ($query !== '' && !$results) : ?>
                <p class="text-muted">No members match "<?= e($query) ?>".</p>
            <?php endif ?>
            <ul class="pick-list">
                <?php foreach ($results as $u) : ?>
                    <li class="pick-list__item">
                        <span><strong class="text-gold"><?= e($u['user_code']) ?></strong> <?= e($u['name']) ?></span>
                        <?php if (in_array($u['id'], $winnerIds, false)) : ?>
                            <span class="tag tag--green">Picked</span>
                        <?php else : ?>
                            <form method="post" action="<?= e(url('/admin/lucky-draw/add')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="draw_id" value="<?= e($draw['id']) ?>">
                                <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                <input type="hidden" name="q" value="<?= e($query) ?>">
                                <button class="btn btn--gold btn--sm" type="submit"<?= $picked >= $slots ? ' disabled' : '' ?>>Add</button>
                            </form>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>

        <section class="card card--pad" aria-labelledby="finish-title">
            <h2 class="card__title" id="finish-title">2. Fill &amp; distribute</h2>
            <div class="btn-stack">
                <form method="post" action="<?= e(url('/admin/lucky-draw/fill')) ?>"
                      data-confirm="Pick <?= e(max(0, $slots - $picked)) ?> more winners at random from members with an active investment?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="draw_id" value="<?= e($draw['id']) ?>">
                    <button class="btn btn--ghost btn--block" type="submit"<?= $picked >= $slots ? ' disabled' : '' ?>>
                        Fill <?= e(max(0, $slots - $picked)) ?> slots at random
                    </button>
                </form>
                <form method="post" action="<?= e(url('/admin/lucky-draw/distribute')) ?>"
                      data-confirm="Pay <?= e(money($draw['prize'])) ?> to each of <?= e($picked) ?> winners (<?= e(money($draw['prize'] * $picked)) ?> total)? This cannot be undone.">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="draw_id" value="<?= e($draw['id']) ?>">
                    <button class="btn btn--gold btn--block btn--lg" type="submit"<?= $picked ? '' : ' disabled' ?>>Distribute Prize</button>
                </form>
            </div>
            <p class="form__note"><?= icon('alert', 16) ?> Random picks only come from members with an active investment, no duplicates.</p>
        </section>
    </div>
<?php endif ?>

<section class="card card--pad" aria-labelledby="winners-title">
    <h2 class="card__title" id="winners-title">Winners (<?= e($picked) ?>)</h2>
    <?php if (!$winners) : ?>
        <div class="empty-state"><?= icon('inbox', 30) ?><p>No winners picked yet</p></div>
    <?php else : ?>
        <ul class="pick-list">
            <?php foreach ($winners as $w) : ?>
                <li class="pick-list__item">
                    <span><strong class="text-gold"><?= e($w['user_code']) ?></strong> <?= e($w['name']) ?>
                        <span class="tag<?= $w['picked_by'] === 'admin' ? '' : ' tag--green' ?>"><?= $w['picked_by'] === 'admin' ? 'Favourite' : 'Random' ?></span></span>
                    <?php if ($draft) : ?>
                        <form method="post" action="<?= e(url('/admin/lucky-draw/remove')) ?>">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="draw_id" value="<?= e($draw['id']) ?>">
                            <input type="hidden" name="user_id" value="<?= e($w['user_id']) ?>">
                            <button class="btn btn--ghost btn--sm" type="submit" aria-label="Remove <?= e($w['user_code']) ?>">Remove</button>
                        </form>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>
