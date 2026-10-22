/*
 * Member ID lookup (P2P receiver, register sponsor): <input data-lookup="/url"> shows the member's name in
 * [data-lookup-out] of the same form and blocks submit until the ID is valid.
 * Optional: [data-lookup-name] hidden input gets the name (for confirm texts like "{receiver_name}"),
 * data-lookup-popup on the input also shows a wrong ID in a popup (window.notice).
 */
(() => {
    'use strict';

    document.querySelectorAll('input[data-lookup]').forEach((code) => {
        const form = code.form;
        const out = form.querySelector('[data-lookup-out]');
        const nameField = form.querySelector('[data-lookup-name]');
        const label = code.dataset.lookupLabel || 'Name';
        const idle = out.textContent;
        let timer = 0;
        let seq = 0;
        let popped = '';

        const show = (state, text) => {
            out.textContent = text;
            out.classList.remove('is-green', 'is-red');
            if (state) {
                out.classList.add(state === 'ok' ? 'is-green' : 'is-red');
            }
        };

        const setValid = (message) => {
            code.setCustomValidity(message);
            if (code.hasAttribute('aria-invalid')) {
                code.dispatchEvent(new Event('input', { bubbles: true })); // re-run the shared inline check
            }
        };

        const fail = (value, message) => {
            show('err', message);
            setValid(message);
            if (code.hasAttribute('data-lookup-popup') && popped !== value) {
                popped = value; // once per typed ID, not on every keystroke
                window.notice?.({ type: 'error', title: 'Invalid ' + label, text: message });
            }
        };

        const lookup = async () => {
            const value = code.value.trim().toUpperCase();
            const mine = ++seq;
            if (nameField) {
                nameField.value = '';
            }
            if (value.length < 3) {
                show('', idle);
                setValid(value ? 'Enter a valid User ID.' : '');
                return;
            }
            if (code.validity.patternMismatch) {
                show('', idle); // still typing: the native pattern message covers it, no lookup yet
                setValid('');
                return;
            }
            show('', 'Checking…');
            setValid('Checking the User ID…');
            try {
                const res = await fetch(code.dataset.lookup + '?code=' + encodeURIComponent(value), {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                if (mine !== seq) {
                    return; // a newer keystroke already started another lookup
                }
                if (data.ok) {
                    show('ok', label + ': ' + data.name + ' (' + data.code + ')');
                    if (nameField) {
                        nameField.value = String(data.name).replace(/[{}]/g, '');
                    }
                    setValid('');
                } else {
                    fail(value, data.message);
                }
            } catch {
                if (mine === seq) {
                    show('err', 'Could not check the ID. Try again.');
                    setValid('Could not check the ID. Try again.');
                }
            }
        };

        code.addEventListener('input', (event) => {
            if (!event.isTrusted) {
                return; // our own re-check event
            }
            code.value = code.value.toUpperCase();
            clearTimeout(timer);
            timer = setTimeout(lookup, 300);
        });

        if (code.value.trim() !== '') {
            lookup();
        }
    });
})();
