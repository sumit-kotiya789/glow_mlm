<?php

/**
 * @var list<array> $tabs
 * @var array{rows: list<array>, page: int, pages: int, total: int} $pager
 */

use App\Csrf;
use App\Models\Withdrawal;

?>
<?php partial('page-head', ['heading' => 'Withdrawals', 'subtitle' => 'Send USDT manually to the wallet shown, then mark Paid. Reject refunds the member.', 'icon' => 'card']) ?>

<section class="card card--pad">
    <?php partial('tabs', ['tabs' => $tabs]) ?>
    <?php if (!$pager['rows']) : ?>
        <div class="empty-state"><?= icon('inbox', 30) ?><p>No withdrawals here</p></div>
    <?php else : ?>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr>
                    <th scope="col">User</th><th scope="col">From</th><th scope="col">Amount</th><th scope="col">Send</th>
                    <th scope="col">To Wallet</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col">Action</th>
                </tr></thead>
                <tbody>
                <?php foreach ($pager['rows'] as $w) : ?>
                    <tr>
                        <td data-label="User"><a class="link-cell" href="<?= e(url('/admin/user?id=' . $w['user_id'])) ?>"><?= e($w['user_code']) ?></a> <span class="text-muted"><?= e($w['name']) ?></span></td>
                        <td data-label="From"><?= e(Withdrawal::SOURCES[$w['source']] . ($w['investment_id'] ? ' #' . $w['investment_id'] : '')) ?></td>
                        <td data-label="Amount" class="num"><?= e(money($w['amount'])) ?><br><span class="text-muted">fee <?= e(money($w['fee'])) ?></span></td>
                        <td data-label="Send" class="num is-gold"><?= e(money($w['net_amount'])) ?></td>
                        <td data-label="To Wallet" class="mono-cell">
                            <?= e(substr($w['wallet'], 0, 8) . '…' . substr($w['wallet'], -6)) ?>
                            <button class="copy-mini" type="button" data-copy="<?= e($w['wallet']) ?>" data-copy-msg="Wallet copied" aria-label="Copy wallet"><?= icon('copy', 16) ?></button>
                        </td>
                        <td data-label="Date"><?= e(fmt_date($w['created_at'], 'd-M-Y H:i')) ?></td>
                        <td data-label="Status" class="<?= e(status_class($w['status'])) ?>"><?= e($w['status']) ?><?= $w['admin_note'] ? ' · ' . e($w['admin_note']) : '' ?></td>
                        <td data-label="Action" class="actions-cell">
                            <?php if ($w['status'] === 'Pending') : ?>
                                <form method="post" action="<?= e(url('/admin/withdrawals/paid')) ?>" class="action-form"
                                      data-confirm="Mark <?= e(money($w['net_amount'])) ?> to <?= e($w['user_code']) ?> as paid? Only do this after sending it.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= e($w['id']) ?>">
                                    <label class="sr-only" for="ph-<?= e($w['id']) ?>">Payout tx hash (optional)</label>
                                    <input class="input input--sm input--mono" id="ph-<?= e($w['id']) ?>" name="payout_hash" placeholder="Payout hash (optional)" maxlength="66">
                                    <button class="btn btn--gold btn--sm" type="submit">Mark Paid</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/withdrawals/reject')) ?>" class="action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= e($w['id']) ?>">
                                    <label class="sr-only" for="wn-<?= e($w['id']) ?>">Reject reason</label>
                                    <input class="input input--sm" id="wn-<?= e($w['id']) ?>" name="note" placeholder="Reason" required minlength="3" maxlength="255">
                                    <button class="btn btn--ghost btn--sm" type="submit">Reject</button>
                                </form>
                            <?php else : ?>
                                <span class="text-muted"><?= e(fmt_date($w['processed_at'], 'd-M H:i')) ?></span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php partial('pagination', ['pager' => $pager]) ?>
    <?php endif ?>
</section>
