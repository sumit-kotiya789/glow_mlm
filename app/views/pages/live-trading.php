<?php /** @var list<array> $trades @var list<array> $summary */ ?>
<?php partial('page-head', [
    'heading' => 'Live Trading',
    'subtitle' => 'Sample AI positions from the seeder. Not market data, not real returns.',
    'icon' => 'trending',
]) ?>

<section class="stat-grid stat-grid--4" aria-label="Trading summary">
    <?php foreach ($summary as $card) {
        partial('stat-card', $card);
    } ?>
</section>

<?php partial('trade-table', ['trades' => $trades]) ?>
