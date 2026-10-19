<?php

/**
 * Invest page: plan type + term + amount.
 *
 * @var list<array> $terms
 * @var float $deposit
 * @var float $min
 * @var float $max
 * @var int $selectedDays
 */

use App\Csrf;
use App\Models\PlanTerm;

$oldPlan = old('plan', 'compound');
$oldDays = (int) old('days', (string) $selectedDays);
$plans = [
    'compound' => ['Compound', 'layers', 'Daily 4% principal part: compound the latest day or withdraw it. 1% profit goes to your ROI wallet every day.'],
    'fixed' => ['Fixed Deposit', 'box', 'Locked for the whole term. Principal + all daily ROI is paid to your ROI wallet at maturity.'],
];
?>
<?php partial('page-head', ['heading' => 'Package Details', 'subtitle' => 'Choose a plan, a term and an amount. Paid from your Deposit wallet.', 'icon' => 'zap']) ?>

<p class="balance-strip">Deposit wallet: <strong class="text-gold"><?= e(money($deposit)) ?></strong>
    <a class="link-gold" href="<?= e(url('/deposit')) ?>">Deposit</a>
</p>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="invest-title">
        <h2 class="card__title" id="invest-title">Invest Now</h2>
        <form method="post" action="<?= e(url('/invest')) ?>" class="form" novalidate data-validate
            data-confirm="Invest ${amount} for {days} days? The amount is taken from your Deposit wallet.">
            <?= Csrf::field() ?>

            <fieldset class="choice-set">
                <legend class="field__label">Plan</legend>
                <div class="choice-grid">
                    <?php foreach ($plans as $value => [$label, $ico, $desc]): ?>
                        <label class="choice">
                            <input type="radio" name="plan" value="<?= e($value) ?>" <?= $oldPlan === $value ? ' checked' : '' ?> required>
                            <span class="choice__title"><?= icon($ico, 20) ?>     <?= e($label) ?></span>
                            <span class="choice__desc"><?= e($desc) ?></span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>

            <fieldset class="choice-set">
                <legend class="field__label">Term</legend>
                <div class="choice-grid choice-grid--3">
                    <?php foreach ($terms as $term): ?>
                        <label class="choice choice--center">
                            <input type="radio" name="days" value="<?= e($term['days']) ?>" <?= $oldDays === (int) $term['days'] ? ' checked' : '' ?> required>
                            <span class="choice__title"><?= e($term['days']) ?> days</span>
                            <span class="choice__desc"><?= e(pct(PlanTerm::dailyRate($term))) ?> daily</span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>

            <div class="field">
                <label class="field__label" for="f-amount">Amount (USDT)</label>
                <input class="input" id="f-amount" name="amount" type="number" min="<?= e($min) ?>"
                    max="<?= e(min($max, $deposit)) ?>" step="0.01" inputmode="decimal" required
                    placeholder="<?= e(money($min)) ?> – <?= e(money($max)) ?>" value="<?= e(old('amount')) ?>"
                    aria-describedby="err-amount hint-amount" <?= invalid_attr('amount') ?>
                    data-invalid="<?= $deposit < $min
                        ? 'Minimum is ' . e(money($min)) . ' but your Deposit wallet has only ' . e(money($deposit)) . '. Please deposit first.'
                        : 'Enter ' . e(money($min)) . ' to ' . e(money(min($max, $deposit))) . ' (your deposit wallet).' ?>">
                <p class="field__hint" id="hint-amount"><?= e(money($min)) ?> – <?= e(money($max)) ?> per investment.
                    You can run several plans at
                    once.<?= $deposit < $min ? ' Add ' . e(money($min - $deposit)) . ' more to your <a class="link-inline" href="' . e(url('/deposit')) . '">Deposit wallet</a> to invest.' : '' ?>
                </p>
                <?= field_error('amount') ?>
            </div>

            <button class="btn btn--gold btn--block btn--lg" type="submit">Invest Now</button>
        </form>
    </section>

    <section class="card card--pad" aria-labelledby="terms-title">
        <h2 class="card__title" id="terms-title">How it works</h2>
        <?php partial('data-table', [
            'columns' => ['term' => 'Term', 'daily' => 'Daily', 'part' => 'Principal part', 'profit' => 'Profit', 'fixed' => 'Fixed $100'],
            'rows' => array_map(static fn($t) => [
                'term' => $t['days'] . ' days',
                'daily' => [pct(PlanTerm::dailyRate($t)), 'is-gold'],
                'part' => pct((float) $t['principal_rate']),
                'profit' => [pct((float) $t['profit_rate']), 'is-green'],
                'fixed' => [money(100 + PlanTerm::dailyRate($t) * (int) $t['days']), 'num'],
            ], $terms),
            'empty' => 'No terms available',
        ]) ?>
        <ol class="how-list">
            <li><strong>Compound:</strong> each day you can compound only the latest 4% part (principal goes up) or
                withdraw any pending part (principal goes down).</li>
            <li><strong>Fixed Deposit:</strong> nothing moves until maturity, then everything is released.</li>
            <li>At the end of the term, principal and all pending go to your ROI wallet.</li>
        </ol>
    </section>
</div>