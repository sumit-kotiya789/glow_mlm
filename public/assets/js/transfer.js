/* P2P form: look up the receiver's (masked) name as the User ID is typed, and block submit until the ID is valid. */
(() => {
    'use strict';

    const form = document.querySelector('form[data-p2p]');
    const code = form?.elements.user_code;
    const out = form?.querySelector('[data-receiver]');
    if (!form || !code || !out) {
        return;
    }
    const baseConfirm = form.dataset.confirm;
    const idle = out.textContent;
    let timer = 0;
    let seq = 0;

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

    const lookup = async () => {
        const value = code.value.trim().toUpperCase();
        const mine = ++seq;
        form.dataset.confirm = baseConfirm;
        if (value.length < 3) {
            show('', idle);
            setValid(value ? 'Enter a valid User ID.' : '');
            return;
        }
        show('', 'Checking…');
        setValid('Checking the User ID…');
        try {
            const res = await fetch(form.dataset.lookup + '?code=' + encodeURIComponent(value), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (mine !== seq) {
                return; // a newer keystroke already started another lookup
            }
            if (data.ok) {
                const name = String(data.name).replace(/[{}]/g, '');
                show('ok', 'Receiver: ' + name + ' (' + data.code + ')');
                form.dataset.confirm = 'Send ${p2p_amount} to ' + name + ' (' + data.code + ')? Check the name before confirming.';
                setValid('');
            } else {
                show('err', data.message);
                setValid(data.message);
            }
        } catch {
            if (mine === seq) {
                show('err', 'Could not check the ID. Try again.');
                setValid('Could not check the ID. Try again.');
            }
        }
    };

    code.addEventListener('input', () => {
        code.value = code.value.toUpperCase();
        clearTimeout(timer);
        timer = setTimeout(lookup, 300);
    });

    if (code.value.trim() !== '') {
        lookup();
    }
})();
