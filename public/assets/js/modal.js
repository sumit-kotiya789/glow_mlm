/*
 * Confirm dialog for forms: <form data-confirm="Invest ${amount} for {days} days?">.
 * {name} is replaced with the form field's current value. Native <dialog>: ESC + focus trap built in.
 */
(() => {
    'use strict';

    const dialog = document.getElementById('confirm-modal');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }
    const text = dialog.querySelector('[data-confirm-text]');
    let pending = null;

    // Runs after app.js validation (form-level listener fires first); skips invalid forms.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');
        if (!form || event.defaultPrevented || form.dataset.confirmed) {
            return;
        }
        event.preventDefault();
        pending = form;
        text.textContent = form.dataset.confirm.replace(/\{(\w+)\}/g, (_, name) => form.elements[name]?.value ?? '');
        dialog.showModal();
    });

    dialog.querySelector('[data-modal-ok]').addEventListener('click', () => {
        if (!pending) {
            return;
        }
        pending.dataset.confirmed = '1';
        dialog.close();
        pending.submit();
    });

    dialog.querySelector('[data-modal-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // Back/forward cache: ask again next time.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-confirmed]').forEach((form) => delete form.dataset.confirmed);
    });
})();
