<?php

/**
 * @var list<array> $tabs
 * @var array{rows: list<array>, page: int, pages: int, total: int} $pager
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Deposits', 'subtitle' => 'Check the hash, then approve (credits the Deposit wallet) or reject.', 'icon' => 'dollar']) ?>

<section class="card card--pad">
    <?php partial('tabs', ['tabs' => $tabs]) ?>
    <?php if (!$pager['rows']) : ?>
        <div class="empty-state"><?= icon('inbox', 30) ?><p>No deposits here</p></div>
    <?php else : ?>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr>
                    <th scope="col">User</th><th scope="col">Amount</th><th scope="col">Tx Hash</th>
                    <th scope="col">Date</th><th scope="col">Status</th><th scope="col">Action</th>
                </tr></thead>
                <tbody>
                <?php foreach ($pager['rows'] as $d) : ?>
                    <tr>
                        <td data-label="User"><a class="link-cell" href="<?= e(url('/admin/user?id=' . $d['user_id'])) ?>"><?= e($d['user_code']) ?></a> <span class="text-muted"><?= e($d['name']) ?></span></td>
                        <td data-label="Amount" class="num is-gold"><?= e(money($d['amount'])) ?></td>
                        <td data-label="Tx Hash" class="mono-cell">
                            <?= e(substr($d['tx_hash'], 0, 12) . '…' . substr($d['tx_hash'], -8)) ?>
                            <button class="copy-mini" type="button" data-copy="<?= e($d['tx_hash']) ?>" data-copy-msg="Hash copied" aria-label="Copy hash"><?= icon('copy', 16) ?></button>
                        </td>
                        <td data-label="Date"><?= e(fmt_date($d['created_at'], 'd-M-Y H:i')) ?></td>
                        <td data-label="Status" class="<?= e(status_class($d['status'])) ?>"><?= e($d['status']) ?><?= $d['admin_note'] ? ' · ' . e($d['admin_note']) : '' ?></td>
                        <td data-label="Action" class="actions-cell">
                            <?php if ($d['status'] === 'Pending') : ?>
                                <form method="post" action="<?= e(url('/admin/deposits/approve')) ?>"
                                      data-confirm="Approve <?= e(money($d['amount'])) ?> for <?= e($d['user_code']) ?>? It is credited to their Deposit wallet.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                    <button class="btn btn--gold btn--sm" type="submit">Approve</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/deposits/reject')) ?>" class="action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                    <label class="sr-only" for="dn-<?= e($d['id']) ?>">Reject reason</label>
                                    <input class="input input--sm" id="dn-<?= e($d['id']) ?>" name="note" placeholder="Reason" required minlength="3" maxlength="255">
                                    <button class="btn btn--ghost btn--sm" type="submit">Reject</button>
                                </form>
                            <?php else : ?>
                                <span class="text-muted"><?= e(fmt_date($d['reviewed_at'], 'd-M H:i')) ?></span>
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
