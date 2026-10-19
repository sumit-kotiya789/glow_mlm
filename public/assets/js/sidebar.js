/* Right slide-in sidebar: toggle, backdrop/ESC/link close, body scroll lock. */
(() => {
    'use strict';

    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!toggle || !sidebar || !backdrop) {
        return;
    }

    const icon = toggle.querySelector('path');
    const MENU = icon.getAttribute('d');
    const CLOSE = 'M18 6 6 18M6 6l12 12';
    const isOpen = () => sidebar.classList.contains('is-open');
    // Admin sidebar (.sidebar--docked) stays visible and usable on desktop.
    const desktop = window.matchMedia('(min-width: 1024px)');
    const isDocked = () => sidebar.classList.contains('sidebar--docked') && desktop.matches;

    const setOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        sidebar.inert = !open && !isDocked();
        document.documentElement.classList.toggle('no-scroll', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        icon.setAttribute('d', open ? CLOSE : MENU);
        if (open) {
            sidebar.querySelector('.is-active, a')?.focus({ preventScroll: true });
        }
    };

    toggle.addEventListener('click', () => setOpen(!isOpen()));
    backdrop.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });

    // Close on route change: link tap, and when the page is restored from back/forward cache.
    sidebar.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setOpen(false);
        }
    });
    window.addEventListener('pageshow', () => setOpen(false));
    desktop.addEventListener('change', () => setOpen(false));
})();
