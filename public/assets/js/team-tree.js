/*
 * Team tree on Level Team: builds an org chart from #tree-data. "+N" opens a member's directs
 * (built on first open), tapping a member shows details in a native <dialog>.
 * Mouse drag pans the scroll box (touch scrolls natively); buttons zoom with CSS zoom.
 */
(() => {
    'use strict';

    const box = document.querySelector('[data-tree]');
    const data = document.getElementById('tree-data');
    if (!box || !data) {
        return;
    }
    const { need, nodes } = JSON.parse(data.textContent);
    const trophy = document.getElementById('tree-trophy');
    const modal = document.getElementById('tree-modal');
    const kids = new Map();
    nodes.forEach((n) => {
        if (n.sponsor !== null) {
            kids.set(n.sponsor, [...(kids.get(n.sponsor) || []), n]);
        }
    });

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    };

    const fill = (dl, rows) => dl.replaceChildren(...rows.map(([term, value, className]) => {
        const row = el('div');
        row.append(el('dt', '', term), el('dd', className || '', value));
        return row;
    }));

    const show = (n) => {
        modal.querySelector('#tree-modal-title').textContent = `${n.code} · ${n.name}`;
        fill(modal.querySelector('[data-tree-details]'), [
            ['Level', n.level ? `L${n.level}` : 'You'],
            ['Sponsor', n.sponsorCode],
            ['Joined', n.joined],
            ['Status', n.active ? 'Active' : 'Inactive', n.active ? 'is-green' : 'is-red'],
            ['Invested', n.invested],
            ['Running plans', n.running, 'is-gold'],
        ]);
        const ok = (met) => (met ? 'is-green' : 'is-red');
        fill(modal.querySelector('[data-tree-salary]'), n.salary
            ? [
                ['Status', 'Achieved', 'is-gold'],
                ['Qualified', n.salary],
                ['Months paid', `${n.monthsPaid} / ${need.months}`, 'is-green'],
            ]
            : [
                ['Self investment', `${n.invested} / ${need.self}`, ok(n.selfOk)],
                ['Direct active', `${n.directsActive} / ${need.directs}`, ok(n.directsActive >= need.directs)],
                ['Level-2 active', `${n.level2Active} / ${need.level2}`, ok(n.level2Active >= need.level2)],
                [n.windowOpen ? 'Window until' : 'Window closed', n.deadline, ok(n.windowOpen)],
            ]);
        modal.showModal();
    };

    const toggle = (li, button, children, reveal = true) => {
        let list = li.querySelector(':scope > ul');
        if (!list) {
            list = el('ul');
            children.forEach((child) => list.append(item(child)));
            li.append(list);
        }
        const open = button.getAttribute('aria-expanded') === 'true';
        list.hidden = open;
        button.setAttribute('aria-expanded', String(!open));
        button.textContent = open ? `+${children.length}` : '−';
        if (!open && reveal) {
            list.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    };

    function item(n) {
        const li = el('li');
        const card = el('button', 'tree-node'
            + (n.active ? ' is-active' : '')
            + (n.salary ? ' is-salary' : '')
            + (n.level === 0 ? ' is-me' : ''));
        card.type = 'button';
        const code = el('span', 'tree-node__code', n.code);
        if (n.salary) {
            code.append(trophy.content.cloneNode(true));
        }
        card.append(code, el('span', 'tree-node__name', n.name), el('span', 'tree-node__meta', n.invested));
        card.addEventListener('click', () => show(n));
        li.append(card);

        const children = kids.get(n.id) || [];
        if (children.length) {
            const button = el('button', 'tree-toggle', `+${children.length}`);
            button.type = 'button';
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', `Show or hide the ${children.length} direct members of ${n.code}`);
            button.addEventListener('click', () => toggle(li, button, children));
            li.append(button);
        }
        return li;
    }

    const canvas = el('div', 'tree__canvas');
    const root = el('ul');
    const me = nodes.find((n) => n.level === 0);
    root.append(item(me));
    canvas.append(root);
    box.append(canvas);
    toggle(root.firstChild, root.querySelector('.tree-toggle'), kids.get(me.id), false);
    box.scrollLeft = (box.scrollWidth - box.clientWidth) / 2;

    // Mouse drag to pan; a drag never counts as a click on a member.
    let drag = null;
    let swallowClick = false;
    box.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button === 0) {
            drag = { x: event.clientX, y: event.clientY, left: box.scrollLeft, top: box.scrollTop };
        }
    });
    window.addEventListener('pointermove', (event) => {
        if (!drag) {
            return;
        }
        const dx = event.clientX - drag.x;
        const dy = event.clientY - drag.y;
        if (Math.abs(dx) + Math.abs(dy) > 5) {
            swallowClick = true;
            box.classList.add('is-dragging');
        }
        box.scrollLeft = drag.left - dx;
        box.scrollTop = drag.top - dy;
    });
    window.addEventListener('pointerup', () => {
        drag = null;
        box.classList.remove('is-dragging');
        setTimeout(() => { swallowClick = false; }); // the click (if any) fires before this
    });
    box.addEventListener('click', (event) => {
        if (swallowClick) {
            event.stopPropagation();
        }
    }, true);

    let zoom = 1;
    document.querySelectorAll('[data-tree-zoom]').forEach((button) => button.addEventListener('click', () => {
        const step = Number(button.dataset.treeZoom);
        zoom = step ? Math.min(1.5, Math.max(0.4, Math.round((zoom + step * 0.1) * 10) / 10)) : 1;
        canvas.style.zoom = zoom;
    }));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.close();
        }
    });
})();
