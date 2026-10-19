<?php

/**
 * Used by both My Packages and Compound Packages.
 *
 * @var string $heading
 * @var string $subtitle
 * @var bool $compoundOnly
 * @var array{rows: list<array>, page: int, pages: int, total: int} $pager
 */

use App\Csrf;
use App\Models\Investment;

$back = $compoundOnly ? '/compound-packages' : '/my-packages';
?>
<?php partial('page-head', ['heading' => $heading, 'subtitle' => $subtitle, 'icon' => $compoundOnly ? 'layers' : 'box']) ?>

<?php if (!$pager['rows']): ?>
    <section class="card card--pad">
        <p class="empty-dashed">No <?= $compoundOnly ? 'Compound' : 'Active' ?> Packages Found</p>
        <a class="btn btn--gold btn--block" href="<?= e(url('/package-details')) ?>">Invest Now</a>
    </section>
<?php else: ?>
    <div class="plan-grid">
        <?php foreach ($pager['rows'] as $p):
            $active = $p['status'] === 'Active';
            $compound = $p['plan'] === 'compound'; ?>
            <article class="plan-card<?= $active ? '' : ' is-done' ?>">
                <header class="plan-card__head">
                    <h2 class="plan-card__name"><?= e(Investment::label($p)) ?></h2>
                    <span class="tag<?= $active ? ' tag--green' : '' ?>"><?= e($p['status']) ?></span>
                </header>
                <p class="owned-item__meta">Started <?= e(fmt_date($p['started_at'], 'd-M-Y H:i')) ?> · Matures
                    <?= e(Investment::maturesOn($p)) ?></p>
                <progress class="progress" max="<?= e($p['term_days']) ?>" value="<?= e($p['days_paid']) ?>"
                    aria-label="Payouts received"><?= e($p['days_paid']) ?></progress>
                <p class="owned-item__meta"><?= e(Investment::progressLabel($p)) ?>
                    <?php if ($active && (int) $p['days_paid'] === 0): ?>
                        · <span class="text-gold">first payout on <?= e(Investment::firstPayout($p)) ?></span>
                    <?php endif ?>
                </p>

                <dl class="plan-card__nums">
                    <div>
                        <dt>Invested</dt>
                        <dd><?= e(money($p['amount'])) ?></dd>
                    </div>
                    <div>
                        <dt>Principal</dt>
                        <dd class="text-gold"><?= e(money($p['principal'])) ?></dd>
                    </div>
                    <?php if ($compound): ?>
                        <div>
                            <dt>Pending (withdrawable)</dt>
                            <dd><?= e(money($p['pending'])) ?></dd>
                        </div>
                        <div>
                            <dt>Latest part (compoundable)</dt>
                            <dd class="text-green"><?= e(money($p['latest_part'])) ?></dd>
                        </div>
                    <?php else: ?>
                        <div>
                            <dt>Locked ROI</dt>
                            <dd class="text-green"><?= e(money($p['accrued'])) ?></dd>
                        </div>
                        <div>
                            <dt>Daily</dt>
                            <dd><?= e(pct((float) $p['principal_rate'] + (float) $p['profit_rate'])) ?></dd>
                        </div>
                    <?php endif ?>
                </dl>

                <?php if ($active && $compound): ?>
                    <div class="plan-card__actions">
                        <form method="post" action="<?= e(url('/investments/compound')) ?>"
                            data-confirm="Compound <?= e(money($p['latest_part'])) ?> into principal? Principal becomes <?= e(money($p['principal'] + $p['latest_part'])) ?>.">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <input type="hidden" name="back" value="<?= e($back) ?>">
                            <button class="btn btn--gold btn--block" type="submit" <?= (float) $p['latest_part'] > 0 ? '' : ' disabled' ?>>
                                Compound <?= e(money($p['latest_part'])) ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(url('/investments/withdraw')) ?>" class="inline-form" novalidate
                            data-validate data-fee-pct="<?= e(\App\Models\Setting::num('withdraw_fee_pct')) ?>"
                            data-confirm="Withdraw ${amount} from this plan? Principal goes down by the same amount.">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <input type="hidden" name="back" value="<?= e($back) ?>">
                            <label class="sr-only" for="w-<?= e($p['id']) ?>">Amount to withdraw</label>
                            <input class="input" id="w-<?= e($p['id']) ?>" name="amount" type="number" step="0.01" min="1"
                                max="<?= e($p['pending']) ?>" inputmode="decimal" required value="<?= e($p['pending']) ?>"
                                aria-describedby="err-w-<?= e($p['id']) ?>"
                                data-invalid="Enter $1.00 to <?= e(money($p['pending'])) ?>." <?= (float) $p['pending'] >= 1 ? '' : ' disabled' ?>>
                            <button class="btn btn--ghost" type="submit" <?= (float) $p['pending'] >= 1 ? '' : ' disabled' ?>>Withdraw</button>
                            <label class="sr-only" for="wt-<?= e($p['id']) ?>">Transaction password</label>
                            <input class="input input--full" id="wt-<?= e($p['id']) ?>" name="txn_password" type="password" required
                                maxlength="64" autocomplete="off" placeholder="Transaction password"
                                aria-describedby="err-w-<?= e($p['id']) ?>" data-required="Enter your transaction password."
                                <?= (float) $p['pending'] >= 1 ? '' : ' disabled' ?>>
                            <p class="field-error" id="err-w-<?= e($p['id']) ?>"></p>
                        </form>
                    </div>
                <?php endif ?>
                <a class="btn btn--ghost btn--block"
                    href="<?= e(url('/investment?id=' . $p['id'])) ?>"><?= icon('history', 18) ?> View History</a>
            </article>
        <?php endforeach ?>
    </div>
    <?php partial('pagination', ['pager' => $pager]) ?>
<?php endif ?>