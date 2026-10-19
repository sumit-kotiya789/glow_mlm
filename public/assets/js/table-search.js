/* Live client-side row filter: <input data-table-search="table-id">. */
(() => {
    'use strict';

    document.querySelectorAll('[data-table-search]').forEach((input) => {
        const table = document.getElementById(input.dataset.tableSearch);
        if (!table) {
            return;
        }
        const rows = [...table.tBodies[0].rows];
        const empty = table.parentElement.querySelector('.table-noresult');

        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            let shown = 0;
            rows.forEach((row) => {
                const hit = row.textContent.toLowerCase().includes(query);
                row.hidden = !hit;
                shown += hit ? 1 : 0;
            });
            if (empty) {
                empty.hidden = shown > 0;
            }
        });
    });
})();
