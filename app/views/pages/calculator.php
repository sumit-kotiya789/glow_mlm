<?php

/**
 * @var list<array> $terms
 * @var string $plan
 * @var int $days
 * @var float|null $amount
 * @var string $error
 * @var array<string, float>|null $result
 */

use App\Models\PlanTerm;

?>
<?php partial('page-head', ['heading' => 'Calculator', 'subtitle' => 'Estimate a plan with the same daily maths as the payout. Illustrative only.', 'icon' => 'calculator']) ?>

<div class="dash-split">
    <section class="card card--pad" aria-label="Calculator form">
        <form method="get" action="<?= e(url('/calculator')) ?>" class="form" novalidate data-validate>
            <fieldset class="choice-set">
                <legend class="field__label">Plan</legend>
                <div class="choice-grid">
                    <?php foreach (['compound' => 'Compound', 'fixed' => 'Fixed Deposit'] as $value => $label) : ?>
                        <label class="choice choice--center">
                            <input type="radio" name="plan" value="<?= e($value) ?>"<?= $plan === $value ? ' checked' : '' ?>>
                            <span class="choice__title"><?= e($label) ?></span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>
            <fieldset class="choice-set">
                <legend class="field__label">Term</legend>
                <div class="choice-grid choice-grid--3">
                    <?php foreach ($terms as $term) : ?>
                        <label class="choice choice--center">
                            <input type="radio" name="days" value="<?= e($term['days']) ?>"<?= $days === (int) $term['days'] ? ' checked' : '' ?>>
                            <span class="choice__title"><?= e($term['days']) ?> days</span>
                            <span class="choice__desc"><?= e(pct(PlanTerm::dailyRate($term))) ?> daily</span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>
            <div class="field">
                <label class="field__label" for="f-amount">Amount (USDT)</label>
                <input class="input" id="f-amount" name="amount" type="number" min="0.01" max="1000000" step="0.01"
                       inputmode="decimal" required placeholder="e.g. 100" value="<?= e($amount ?? '') ?>"
                       aria-describedby="err-amount" data-invalid="Enter 0.01 to 1,000,000.">
                <?= field_error('amount') ?>
            </div>
            <?php if ($error) : ?><p class="field-error"><?= e($error) ?></p><?php endif ?>
            <button class="btn btn--gold btn--block btn--lg" type="submit">Calculate</button>
        </form>
    </section>

    <section class="card card--pad" aria-live="polite" aria-labelledby="calc-out">
        <h2 class="card__title" id="calc-out">Result</h2>
        <?php if ($result) : ?>
            <dl class="calc-result">
                <div><dt>Day-1 return</dt><dd class="text-green"><?= e(money($result['daily'])) ?></dd></div>
                <?php if ($plan === 'fixed') : ?>
                    <div><dt>Total ROI (<?= e($days) ?> days)</dt><dd class="text-green"><?= e(money($result['profit'])) ?></dd></div>
                    <div><dt>Paid at maturity</dt><dd class="text-gold"><?= e(money($result['final'])) ?></dd></div>
                <?php else : ?>
                    <div><dt>1% profit paid along the way</dt><dd class="text-green"><?= e(money($result['profit'])) ?></dd></div>
                    <div><dt>Released at maturity</dt><dd class="text-gold"><?= e(money($result['final'])) ?></dd></div>
                    <div><dt>Total if you compound daily</dt><dd class="text-cyan"><?= e(money($result['total'])) ?></dd></div>
                    <div><dt>Total if you withdraw daily</dt><dd><?= e(money($result['withdraw_total'])) ?></dd></div>
                <?php endif ?>
            </dl>
            <p class="form__note"><?= icon('alert', 16) ?> Sample maths only. Not a promise of returns.</p>
        <?php else : ?>
            <p class="empty-dashed">Enter an amount to see the estimate</p>
        <?php endif ?>
    </section>
</div>
