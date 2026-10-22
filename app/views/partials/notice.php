<?php

/**
 * Animated popup: success after an action (server: notice()), error from JS (window.notice()). See js/notice.js.
 */

$notice = flashed('notice');
?>
<dialog class="modal notice" id="notice-modal" aria-labelledby="notice-title"<?= $notice ? ' data-open' : '' ?>>
    <div class="notice__burst" aria-hidden="true" data-confetti></div>
    <div class="modal__body notice__body">
        <span class="notice__badge" aria-hidden="true">
            <svg class="notice__tick" viewBox="0 0 52 52"><circle cx="26" cy="26" r="23"/><path d="M15 27l7 7 15-15"/></svg>
            <svg class="notice__cross" viewBox="0 0 52 52"><circle cx="26" cy="26" r="23"/><path d="M18 18l16 16M34 18 18 34"/></svg>
        </span>
        <h2 class="modal__title notice__title" id="notice-title" data-notice-title><?= e($notice['title'] ?? '') ?></h2>
        <p class="modal__text" data-notice-text><?= e($notice['text'] ?? '') ?></p>
        <?php if (!empty($notice['rows'])) : ?>
            <dl class="notice__rows" data-notice-rows>
                <?php foreach ($notice['rows'] as $label => $value) : ?>
                    <div>
                        <dt><?= e($label) ?></dt>
                        <dd><?= e($value) ?><?php if ($label === 'User ID') : ?>
                            <button class="copy-mini" type="button" data-copy="<?= e($value) ?>" data-copy-msg="User ID copied"
                                aria-label="Copy User ID"><?= icon('copy', 16) ?></button><?php endif ?></dd>
                    </div>
                <?php endforeach ?>
            </dl>
        <?php endif ?>
        <button class="btn btn--gold btn--block" type="button" data-notice-close>OK</button>
    </div>
</dialog>
