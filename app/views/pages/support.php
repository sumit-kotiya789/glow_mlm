<?php

/**
 * @var array<string, string> $columns
 * @var list<array> $rows
 * @var array $pager
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Support', 'subtitle' => 'Raise a ticket. Stored locally, nobody is emailed.', 'icon' => 'headset']) ?>

<div class="dash-split">
    <section class="card card--pad" aria-labelledby="new-ticket">
        <h2 class="card__title" id="new-ticket">New Ticket</h2>
        <form method="post" action="<?= e(url('/support')) ?>" class="form" novalidate data-validate>
            <?= Csrf::field() ?>
            <div class="field">
                <label class="field__label" for="f-subject">Subject</label>
                <input class="input" id="f-subject" name="subject" type="text" required minlength="3" maxlength="100"
                       placeholder="How can we help?" value="<?= e(old('subject')) ?>"
                       aria-describedby="err-subject"<?= invalid_attr('subject') ?>
                       data-invalid="Subject must be 3-100 characters.">
                <?= field_error('subject') ?>
            </div>
            <div class="field">
                <label class="field__label" for="f-message">Message</label>
                <textarea class="input" id="f-message" name="message" rows="5" required minlength="10" maxlength="1000"
                          placeholder="Describe your issue" aria-describedby="err-message"<?= invalid_attr('message') ?>
                          data-invalid="Message must be 10-1000 characters."><?= e(old('message')) ?></textarea>
                <?= field_error('message') ?>
            </div>
            <button class="btn btn--gold btn--block btn--lg" type="submit">Create Ticket</button>
        </form>
    </section>

    <section class="card card--pad" id="tickets" aria-labelledby="tickets-title">
        <h2 class="card__title" id="tickets-title">My Tickets</h2>
        <?php partial('data-table', ['columns' => $columns, 'rows' => $rows, 'empty' => 'No tickets yet']) ?>
        <?php partial('pagination', ['pager' => $pager]) ?>
    </section>
</div>
