/* Shared behaviour: copy buttons and inline client-side form validation. */
(() => {
    'use strict';

    // ---- Copy to clipboard: <button data-copy="text" data-copy-msg="Copied"> ----
    const fallbackCopy = (text) => {
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.className = 'sr-only';
        document.body.append(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    };

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-copy]');
        if (!btn) {
            return;
        }
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
        } catch {
            fallbackCopy(btn.dataset.copy); // non-secure origin (e.g. LAN IP)
        }
        window.toast?.(btn.dataset.copyMsg || 'Copied');
    });

    // ---- QR codes: <div data-qr="text"> (vendor/qrcode.js loaded on pages that need it) ----
    document.querySelectorAll('[data-qr]').forEach((box) => {
        if (typeof window.qrcode !== 'function') {
            return;
        }
        const qr = window.qrcode(0, 'M');
        qr.addData(box.dataset.qr);
        qr.make();
        box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    });

    // ---- Validation: forms with [data-validate]; native constraints, custom messages ----
    const errorBox = (el) => document.getElementById((el.getAttribute('aria-describedby') || '').split(' ')[0]);

    const messageFor = (el) => {
        if (el.validity.valid) {
            return '';
        }
        if (el.validity.valueMissing) {
            return el.dataset.required || 'This field is required.';
        }
        if (el.validity.customError) {
            return el.validationMessage; // e.g. "Sponsor ID not found" from js/lookup.js
        }
        return el.dataset.invalid || el.validationMessage;
    };

    const check = (el) => {
        const msg = messageFor(el);
        const box = errorBox(el);
        if (box) {
            box.textContent = msg;
        }
        if (msg) {
            el.setAttribute('aria-invalid', 'true');
        } else {
            el.removeAttribute('aria-invalid');
        }
        return !msg;
    };

    document.querySelectorAll('form[data-validate]').forEach((form) => {
        const fields = () => form.querySelectorAll('input:not([type=hidden]), select, textarea');

        form.addEventListener('submit', (event) => {
            const invalid = [...fields()].filter((el) => !check(el));
            if (invalid.length) {
                event.preventDefault();
                invalid[0].focus();
            }
        });

        // Re-check live once a field has been flagged, so errors clear as the user fixes them.
        const recheck = (event) => {
            if (event.target.hasAttribute('aria-invalid')) {
                check(event.target);
            }
        };
        form.addEventListener('input', recheck);
        form.addEventListener('change', recheck);
    });
})();
