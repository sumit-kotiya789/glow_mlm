<dialog class="modal" id="confirm-modal" aria-labelledby="confirm-title">
    <div class="modal__body">
        <h2 class="modal__title" id="confirm-title">Please confirm</h2>
        <p class="modal__text" data-confirm-text></p>
        <p class="modal__note"><?= icon('alert', 16) ?> Demo only. No real funds move.</p>
        <div class="modal__actions">
            <button class="btn btn--ghost" type="button" data-modal-close>Cancel</button>
            <button class="btn btn--gold" type="button" data-modal-ok>Confirm</button>
        </div>
    </div>
</dialog>
