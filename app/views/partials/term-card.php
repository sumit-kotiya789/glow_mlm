<?php

/**
 * One investment term (25 / 50 / 100 days).
 *
 * @var array $term
 */

use App\Models\PlanTerm;

$rate = PlanTerm::dailyRate($term);
$example = 100 + 100 * $rate / 100 * (int) $term['days'];
?>
<article class="term-card">
    <p class="term-card__days"><?= e($term['days']) ?> <span>Days</span></p>
    <p class="term-card__rate"><?= e(pct($rate)) ?> daily</p>
    <dl class="term-card__rows">
        <div><dt>Principal part</dt><dd><?= e(pct((float) $term['principal_rate'])) ?></dd></div>
        <div><dt>Profit</dt><dd class="text-green"><?= e(pct((float) $term['profit_rate'])) ?></dd></div>
        <div><dt>Plans</dt><dd>Compound · Fixed</dd></div>
        <div class="term-card__total"><dt>Fixed $100 returns</dt><dd class="text-gold"><?= e(money($example)) ?></dd></div>
    </dl>
    <a class="btn btn--gold btn--block" href="<?= e(url('/package-details?days=' . $term['days'])) ?>">Invest Now</a>
</article>
