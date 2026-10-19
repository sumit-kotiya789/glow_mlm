/* User panel navigation: desktop = docked sidebar; mobile = "More" button opens the menu as a bottom sheet. */
(() => {
    'use strict';

    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!toggle || !sidebar || !backdrop) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 1024px)');
    const isOpen = () => sidebar.classList.contains('is-open');

    const setOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        sidebar.inert = !open && !desktop.matches;
        document.documentElement.classList.toggle('no-scroll', open && !desktop.matches);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.classList.toggle('is-active', open);
        if (open) {
            sidebar.querySelector('.is-active, a')?.focus({ preventScroll: true });
        }
    };

    const sync = () => setOpen(false);
    sync();

    toggle.addEventListener('click', () => setOpen(!isOpen()));
    backdrop.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });

    sidebar.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setOpen(false);
        }
    });
    window.addEventListener('pageshow', sync);
    desktop.addEventListener('change', sync);

    // Swipe down on the sheet to dismiss (mobile).
    let startY = null;
    sidebar.addEventListener('touchstart', (e) => {
        startY = sidebar.scrollTop === 0 ? e.touches[0].clientY : null;
    }, { passive: true });
    sidebar.addEventListener('touchend', (e) => {
        if (startY !== null && e.changedTouches[0].clientY - startY > 80) {
            setOpen(false);
        }
        startY = null;
    }, { passive: true });
})();
