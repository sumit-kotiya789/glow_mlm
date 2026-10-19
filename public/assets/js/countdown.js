/* Live countdown: <span data-countdown="ISO-8601 deadline">. */
(() => {
    'use strict';

    const els = document.querySelectorAll('[data-countdown]');
    if (!els.length) {
        return;
    }

    const tick = () => els.forEach((el) => {
        const ms = Math.max(0, Date.parse(el.dataset.countdown) - Date.now());
        const d = Math.floor(ms / 864e5);
        const h = Math.floor((ms % 864e5) / 36e5);
        const m = Math.floor((ms % 36e5) / 6e4);
        const s = Math.floor((ms % 6e4) / 1e3);
        el.textContent = ms ? `${d}d ${h}h ${m}m ${String(s).padStart(2, '0')}s left` : 'Period ended';
    });

    tick();
    setInterval(tick, 1000);
})();
