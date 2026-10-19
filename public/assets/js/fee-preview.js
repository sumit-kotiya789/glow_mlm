(() => {
    'use strict';

    const usd = (n) => '$' + n.toFixed(2);

    document.querySelectorAll('form[data-fee-pct]').forEach((form) => {
        const pct = parseFloat(form.dataset.feePct) || 0;
        const input = form.elements.amount;
        if (!input) {
            return;
        }
        const baseConfirm = form.dataset.confirm || '';
        const box = (key) => form.querySelector('[data-fee-' + key + ']');

        const update = () => {
            const amount = parseFloat(input.value) || 0;
            const fee = Math.round(amount * pct) / 100;
            const net = Math.round((amount - fee) * 100) / 100;
            [['amount', amount], ['fee', fee], ['net', net]].forEach(([key, value]) => {
                const el = box(key);
                if (el) {
                    el.textContent = usd(value);
                }
            });
            form.dataset.confirm = baseConfirm + ' Fee ' + usd(fee) + ' (' + pct + '%), you get ' + usd(net) + '.';
        };

        input.addEventListener('input', update);
        update();
    });
})();