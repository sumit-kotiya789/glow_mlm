/*
 * Animated popup (partials/notice.php): opens itself after a successful action (server flash),
 * window.notice({ type: 'error', title, text }) opens it from JS. Confetti on success only.
 */
(() => {
    'use strict';

    const dialog = document.getElementById('notice-modal');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }
    const burst = dialog.querySelector('[data-confetti]');
    const COLORS = ['#d4af37', '#f3d27a', '#fff3c4', '#b8860b', '#ffffff'];

    const confetti = () => {
        burst.replaceChildren(...Array.from({ length: 40 }, (_, i) => {
            const piece = document.createElement('i');
            piece.style.setProperty('--x', Math.round((Math.random() * 2 - 1) * 190) + 'px');
            piece.style.setProperty('--y', -Math.round(70 + Math.random() * 150) + 'px');
            piece.style.setProperty('--r', Math.round(Math.random() * 720 - 360) + 'deg');
            piece.style.setProperty('--d', (Math.random() * 0.25).toFixed(2) + 's');
            piece.style.background = COLORS[i % COLORS.length];
            return piece;
        }));
    };

    const open = (type) => {
        const error = type === 'error';
        dialog.classList.toggle('notice--error', error);
        if (error) {
            burst.replaceChildren();
        } else {
            confetti();
        }
        if (!dialog.open) {
            dialog.showModal();
        }
    };

    window.notice = ({ type = 'success', title = '', text = '' }) => {
        dialog.querySelector('[data-notice-title]').textContent = title;
        dialog.querySelector('[data-notice-text]').textContent = text;
        dialog.querySelector('[data-notice-rows]')?.remove();
        open(type);
    };

    dialog.querySelector('[data-notice-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open')) {
        open('success');
    }
})();
