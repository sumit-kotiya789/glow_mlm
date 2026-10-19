/* Toasts: window.toast(message, type) and server flash messages. */
(() => {
    'use strict';

    const region = document.getElementById('toast-region');
    const PATHS = {
        success: 'M22 11.1V12a10 10 0 1 1-5.9-9.1M22 4 12 14l-3-3',
        error: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 8v4M12 16h.01',
    };

    const toast = (message, type = 'success') => {
        if (!region) {
            return;
        }
        const kind = type === 'error' ? 'error' : 'success';
        const el = document.createElement('div');
        el.className = `toast toast--${kind}`;
        el.innerHTML = '<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            + `<path d="${PATHS[kind]}"/></svg>`;
        const text = document.createElement('span');
        text.textContent = message;
        el.append(text);
        region.append(el);
        setTimeout(() => {
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 300);
        }, kind === 'error' ? 4500 : 3200);
    };

    window.toast = toast;

    try {
        JSON.parse(region?.dataset.flash || '[]').forEach((t) => toast(t.message, t.type));
    } catch {
        // ignore malformed flash payload
    }
})();
