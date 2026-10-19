<?php

/**
 * @var list<array> $tabs
 * @var array{rows: list<array>, page: int, pages: int, total: int} $pager
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Tickets', 'subtitle' => 'Member questions. Replies show on the member\'s Support page.', 'icon' => 'ticket']) ?>

<section class="card card--pad">
    <?php partial('tabs', ['tabs' => $tabs]) ?>
    <?php if (!$pager['rows']) : ?>
        <div class="empty-state"><?= icon('inbox', 30) ?><p>No tickets here</p></div>
    <?php else : ?>
        <div class="ticket-list">
            <?php foreach ($pager['rows'] as $t) : ?>
                <article class="ticket">
                    <header class="ticket__head">
                        <h2 class="ticket__subject">#<?= e(str_pad((string) $t['id'], 4, '0', STR_PAD_LEFT)) ?> · <?= e($t['subject']) ?></h2>
                        <span class="tag<?= $t['status'] === 'Open' ? '' : ' tag--green' ?>"><?= e($t['status']) ?></span>
                    </header>
                    <p class="owned-item__meta">
                        <a class="link-cell" href="<?= e(url('/admin/user?id=' . $t['user_id'])) ?>"><?= e($t['user_code']) ?></a>
                        <?= e($t['name']) ?> · <?= e(fmt_date($t['created_at'], 'd-M-Y H:i')) ?>
                    </p>
                    <p class="ticket__message"><?= nl2br(e($t['message'])) ?></p>
                    <?php if ($t['admin_reply']) : ?>
                        <p class="ticket__reply"><strong>Reply:</strong> <?= nl2br(e($t['admin_reply'])) ?></p>
                    <?php else : ?>
                        <form method="post" action="<?= e(url('/admin/tickets/reply')) ?>" class="form" novalidate data-validate>
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                            <label class="sr-only" for="r-<?= e($t['id']) ?>">Reply</label>
                            <textarea class="input" id="r-<?= e($t['id']) ?>" name="reply" rows="3" required minlength="2" maxlength="1000"
                                      placeholder="Write a reply" aria-describedby="err-r-<?= e($t['id']) ?>"
                                      data-invalid="Reply must be 2-1000 characters."></textarea>
                            <p class="field-error" id="err-r-<?= e($t['id']) ?>"></p>
                            <button class="btn btn--gold" type="submit">Send Reply</button>
                        </form>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
        <?php partial('pagination', ['pager' => $pager]) ?>
    <?php endif ?>
</section>
