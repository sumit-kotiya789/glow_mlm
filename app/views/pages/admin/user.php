<?php

/**
 * @var array $user
 * @var array $salary
 * @var array $summary
 * @var array<int, array> $uplines
 * @var list<array{level: int, members: int, active: int}> $team
 * @var list<array> $investments
 * @var array $ledger
 */

use App\Csrf;
use App\Models\Investment;
use App\Models\Ledger;

$blocked = (int) $user['is_blocked'] === 1;
?>
<div class="page-head page-head--split">
    <div class="page-head">
        <span class="page-head__icon"><?= icon('user', 24) ?></span>
        <div>
            <h1 class="page-head__title"><?= e($user['user_code']) ?> · <?= e($user['name']) ?></h1>
            <p class="page-head__sub">
                <?= $blocked ? '<span class="text-red">Blocked</span>' : ($user['activated_at'] ? '<span class="text-green">Active</span>' : 'Inactive') ?>
                · joined <?= e(fmt_date($user['created_at'])) ?>
            </p>
        </div>
    </div>
    <form method="post" action="<?= e(url('/admin/user/block')) ?>"
          data-confirm="<?= $blocked ? 'Unblock' : 'Block' ?> <?= e($user['user_code']) ?>?<?= $blocked ? '' : ' They are logged out and get no network income.' ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= e($user['id']) ?>">
        <button class="btn <?= $blocked ? 'btn--gold' : 'btn--danger' ?>" type="submit"><?= $blocked ? 'Unblock' : 'Block User' ?></button>
    </form>
</div>

<section class="stat-grid" aria-label="Wallets">
    <?php partial('stat-card', ['label' => 'Deposit Wallet', 'value' => money($user['deposit_balance']), 'tone' => 'muted']) ?>
    <?php partial('stat-card', ['label' => 'Compound ROI Wallet', 'value' => money($user['roi_balance']), 'tone' => 'green']) ?>
    <?php partial('stat-card', ['label' => 'FD ROI Wallet', 'value' => money($user['fd_balance']), 'tone' => 'cyan']) ?>
    <?php partial('stat-card', ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'amber']) ?>
    <?php partial('stat-card', ['label' => 'Active Investment', 'value' => money($summary['invested']), 'tone' => 'gold']) ?>
    <?php partial('stat-card', ['label' => 'Active Plans', 'value' => (string) $summary['active'], 'tone' => 'cyan']) ?>
    <?php partial('stat-card', ['label' => 'Plan Pending', 'value' => money($summary['pending']), 'tone' => 'muted']) ?>
</section>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="profile-title">
        <h2 class="card__title" id="profile-title">Profile</h2>
        <dl class="calc-result calc-result--sm">
            <div><dt>Sponsor</dt><dd><?= e($user['sponsor_code'] ?? '—') ?></dd></div>
            <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
            <div><dt>Mobile</dt><dd><?= e($user['mobile']) ?></dd></div>
            <div><dt>Country</dt><dd><?= e($user['country']) ?></dd></div>
            <div><dt>Wallet</dt><dd class="wallet-text"><?= e($user['wallet']) ?></dd></div>
            <div><dt>Activated</dt><dd><?= e($user['activated_at'] ? fmt_date($user['activated_at']) : '—') ?></dd></div>
            <div><dt>Signup bonus</dt><dd><?= $user['signup_bonus_paid'] ? 'Paid' : 'Not yet' ?></dd></div>
        </dl>
    </section>

    <section class="card card--pad" aria-labelledby="salary-title">
        <h2 class="card__title" id="salary-title">Salary</h2>
        <dl class="calc-result calc-result--sm">
            <div><dt>Own investment</dt><dd><?= e(money($salary['self'])) ?> / <?= e(money($salary['self_need'])) ?></dd></div>
            <div><dt>Active directs</dt><dd><?= e($salary['directs']) ?> / <?= e($salary['directs_need']) ?></dd></div>
            <div><dt>Active level-2</dt><dd><?= e($salary['level2']) ?> / <?= e($salary['level2_need']) ?></dd></div>
            <div><dt>Window ends</dt><dd><?= e(fmt_date($salary['deadline'], 'd-M-Y H:i')) ?></dd></div>
            <div><dt>Status</dt><dd class="<?= $salary['qualified'] ? 'text-green' : 'text-gold' ?>">
                <?= $salary['qualified'] ? 'Qualified · ' . e($salary['months_paid']) . '/' . e($salary['months']) . ' paid' : ($salary['window_open'] ? 'In progress' : 'Window closed') ?>
            </dd></div>
        </dl>
    </section>
</div>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="adjust-title">
        <h2 class="card__title" id="adjust-title">Adjust Wallet</h2>
        <form method="post" action="<?= e(url('/admin/user/adjust')) ?>" class="form" novalidate data-validate
              data-confirm="Adjust <?= e($user['user_code']) ?>'s {wallet} wallet by ${amount}?">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
            <fieldset class="choice-set">
                <legend class="field__label">Wallet</legend>
                <div class="choice-grid choice-grid--3">
                    <?php foreach (['deposit' => 'Deposit'] as $value => $label) : ?>
                        <label class="choice choice--center">
                            <input type="radio" name="wallet" value="<?= e($value) ?>"<?= $value === 'income' ? ' checked' : '' ?>>
                            <span class="choice__title"><?= e($label) ?></span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>
            <div class="field">
                <label class="field__label" for="f-amount">Amount (negative to deduct)</label>
                <input class="input" id="f-amount" name="amount" type="text" inputmode="decimal" required
                       pattern="-?[0-9]{1,7}(\.[0-9]{1,2})?" placeholder="e.g. 25 or -10.50"
                       aria-describedby="err-amount" data-invalid="Amount like 25 or -10.50.">
                <?= field_error('amount') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-note">Reason (shown in the member's history)</label>
                <input class="input" id="f-note" name="note" type="text" required minlength="3" maxlength="140"
                       aria-describedby="err-note" data-invalid="Write a reason (3+ characters).">
                <?= field_error('note') ?>
            </div>
            <button class="btn btn--gold btn--block" type="submit">Apply Adjustment</button>
        </form>
    </section>

    <section class="card card--pad" aria-labelledby="tree-title">
        <h2 class="card__title" id="tree-title">Network</h2>
        <p class="field__label">Uplines</p>
        <p class="chip-row">
            <?php if (!$uplines) : ?><span class="text-muted">Top of the tree</span><?php endif ?>
            <?php foreach ($uplines as $level => $up) : ?>
                <a class="chip" href="<?= e(url('/admin/user?id=' . $up['id'])) ?>">L<?= e($level) ?> · <?= e($up['user_code']) ?></a>
            <?php endforeach ?>
        </p>
        <?php partial('data-table', [
            'columns' => ['level' => 'Level', 'members' => 'Members', 'active' => 'Active'],
            'rows' => array_map(static fn ($t) => [
                'level' => 'Level ' . $t['level'],
                'members' => (string) $t['members'],
                'active' => [(string) $t['active'], 'is-green'],
            ], $team),
            'empty' => 'No downline',
        ]) ?>
    </section>
</div>

<section class="card card--pad" aria-labelledby="inv-title">
    <h2 class="card__title" id="inv-title">Investments</h2>
    <?php partial('data-table', [
        'columns' => ['plan' => 'Plan', 'amount' => 'Invested', 'principal' => 'Principal', 'extra' => 'Pending / Locked', 'day' => 'Day', 'status' => 'Status'],
        'rows' => array_map(static fn ($i) => [
            'plan' => [Investment::label($i), 'is-gold'],
            'amount' => [money($i['amount']), 'num'],
            'principal' => [money($i['principal']), 'num'],
            'extra' => [money($i['plan'] === 'compound' ? $i['pending'] : $i['accrued']), 'num'],
            'day' => Investment::progressLabel($i),
            'status' => [$i['status'], status_class($i['status'])],
        ], $investments),
        'empty' => 'No investments',
    ]) ?>
</section>

<section class="card card--pad" aria-labelledby="ledger-title">
    <h2 class="card__title" id="ledger-title">Ledger</h2>
    <?php partial('data-table', [
        'columns' => ['date' => 'Date', 'wallet' => 'Wallet', 'type' => 'Type', 'amount' => 'Amount', 'note' => 'Note'],
        'rows' => array_map(static fn ($r) => [
            'date' => fmt_date($r['created_at'], 'd-M-Y H:i'),
            'wallet' => Ledger::WALLETS[$r['wallet']] ?? ucfirst($r['wallet']),
            'type' => Ledger::TYPES[$r['type']] ?? $r['type'],
            'amount' => [money($r['amount']), 'num ' . ((float) $r['amount'] < 0 ? 'is-red' : 'is-green')],
            'note' => ($r['from_code'] ? $r['from_code'] . ' L' . $r['level'] . ' · ' : '') . $r['note'],
        ], $ledger['rows']),
        'empty' => 'No entries',
    ]) ?>
    <?php partial('pagination', ['pager' => $ledger]) ?>
</section>
