/*
 * Landing page behaviour: scroll reveal, count-up, sliders (scroll-snap + arrows/dots/autoplay),
 * card tilt, sticky nav, mobile CTA bar and the Fixed Deposit calculator chart.
 * Every effect is skipped or made static under prefers-reduced-motion.
 */
(() => {
    'use strict';

    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

    /* ---- Scroll reveal + count-up ---- */
    const countUp = (node) => {
        const target = parseFloat(node.dataset.count);
        if (Number.isNaN(target) || reduce) {
            return;
        }
        const dec = parseInt(node.dataset.dec || '0', 10);
        const pre = node.dataset.prefix || '';
        const suf = node.dataset.suffix || '';
        const start = performance.now();
        const tick = (now) => {
            const t = Math.min(1, (now - start) / 1100);
            const eased = 1 - Math.pow(1 - t, 3);
            node.textContent = pre + (target * eased).toFixed(dec) + suf;
            if (t < 1) {
                requestAnimationFrame(tick);
            }
        };
        requestAnimationFrame(tick);
    };

    const revealables = $$('[data-reveal]');
    if ('IntersectionObserver' in window && !reduce) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('is-in');
                $$('[data-count]', entry.target).forEach(countUp);
                io.unobserve(entry.target);
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
        revealables.forEach((node) => io.observe(node));
        const bars = $('.lp-bars');
        if (bars) {
            io.observe(bars);
        }
        $$('.lp-rings .lp-ringcard').forEach((node) => io.observe(node));
    } else {
        revealables.forEach((node) => node.classList.add('is-in'));
    }

    /* ---- Sliders ---- */
    $$('[data-slider]').forEach((slider) => {
        const track = $('[data-track]', slider);
        const slides = [...track.children];
        const dots = $('[data-dots]', slider);
        const autoplay = parseInt(slider.dataset.autoplay || '0', 10);
        let index = 0;
        let timer = null;
        let hovering = false;

        const buttons = slides.map((_, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('role', 'tab');
            b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
            b.addEventListener('click', () => go(i));
            dots.append(b);
            return b;
        });

        const paint = () => buttons.forEach((b, i) => b.setAttribute('aria-selected', String(i === index)));

        const go = (i) => {
            index = (i + slides.length) % slides.length;
            const slide = slides[index];
            track.scrollTo({
                left: slide.offsetLeft - (track.clientWidth - slide.clientWidth) / 2,
                behavior: reduce ? 'auto' : 'smooth',
            });
            paint();
        };

        // Keep the dots in sync when the user swipes or scrolls the track.
        let raf = 0;
        track.addEventListener('scroll', () => {
            cancelAnimationFrame(raf);
            raf = requestAnimationFrame(() => {
                const center = track.scrollLeft + track.clientWidth / 2;
                let best = 0;
                slides.forEach((s, i) => {
                    if (Math.abs(s.offsetLeft + s.clientWidth / 2 - center) < Math.abs(slides[best].offsetLeft + slides[best].clientWidth / 2 - center)) {
                        best = i;
                    }
                });
                if (best !== index) {
                    index = best;
                    paint();
                }
            });
        }, { passive: true });

        $('[data-prev]', slider)?.addEventListener('click', () => go(index - 1));
        $('[data-next]', slider)?.addEventListener('click', () => go(index + 1));
        slider.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                go(index - 1);
            } else if (e.key === 'ArrowRight') {
                go(index + 1);
            }
        });

        const stop = () => {
            clearInterval(timer);
            timer = null;
        };
        const start = () => {
            if (autoplay && !reduce && !timer && !hovering && !document.hidden) {
                timer = setInterval(() => go(index + 1), autoplay);
            }
        };
        slider.addEventListener('pointerenter', () => { hovering = true; stop(); });
        slider.addEventListener('pointerleave', () => { hovering = false; start(); });
        slider.addEventListener('focusin', stop);
        slider.addEventListener('focusout', start);
        slider.addEventListener('touchstart', stop, { passive: true });
        document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

        paint();
        start();
    });

    /* ---- Card tilt (fine pointers only) ---- */
    if (!reduce && matchMedia('(hover: hover) and (pointer: fine)').matches) {
        $$('[data-tilt]').forEach((card) => {
            card.addEventListener('pointermove', (e) => {
                const r = card.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transition = 'border-color .3s, box-shadow .3s';
                card.style.transform = `perspective(700px) rotateY(${x * 8}deg) rotateX(${-y * 8}deg) translateY(-4px)`;
            });
            card.addEventListener('pointerleave', () => {
                card.style.transition = 'transform .5s cubic-bezier(.2,.8,.2,1), border-color .3s, box-shadow .3s';
                card.style.transform = '';
            });
        });
    }

    /* ---- Sticky nav + mobile CTA bar ---- */
    const nav = $('#lp-nav');
    const mcta = $('#lp-mcta');
    const onScroll = () => {
        nav.classList.toggle('is-stuck', scrollY > 16);
        mcta.classList.toggle('is-on', scrollY > 520);
    };
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ---- Calculator + growth chart ---- */
    const form = $('#lp-calc');
    if (form) {
        const amt = $('#lp-amt');
        const days = $('#lp-days');
        const out = $('#lp-out');
        const end = $('#lp-end');
        const line = $('#lp-line-p');
        const area = $('#lp-area-p');
        const rate = parseFloat(form.dataset.rate);
        const min = parseFloat(amt.min) || 0;
        const max = parseFloat(amt.max) || Infinity;
        const W = 320;
        const H = 150;

        const calc = () => {
            const d = parseInt(days.value, 10);
            const v = Math.min(max, Math.max(0, parseFloat(amt.value) || 0));
            const total = v + v * rate / 100 * d;
            out.textContent = '$' + total.toFixed(2);
            end.textContent = 'Day ' + d;
            amt.setCustomValidity(v >= min ? '' : 'Minimum is $' + min);

            // Straight-line growth: locked ROI accrues evenly until maturity.
            const pts = [];
            const steps = 24;
            const top = Math.max(total, 1);
            for (let i = 0; i <= steps; i++) {
                const val = v + (total - v) * i / steps;
                pts.push([i / steps * W, H - 12 - (val / top) * (H - 30) * (v > 0 ? 1 : 0)]);
            }
            const d1 = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
            line.setAttribute('d', d1);
            area.setAttribute('d', d1 + ` L${W} ${H} L0 ${H} Z`);
            line.classList.remove('is-drawn');
            void line.getBoundingClientRect(); // restart the draw animation
            line.classList.add('is-drawn');
        };
        amt.addEventListener('input', calc);
        days.addEventListener('change', calc);
        calc();
    }
})();
