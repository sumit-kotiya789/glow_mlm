<?php

/**
 * Visual team tree (built by assets/js/team-tree.js from the JSON below) + member detail dialog.
 *
 * @var array{need: array<string, mixed>, nodes: list<array<string, mixed>>} $tree
 */

?>
<section class="card card--pad" aria-labelledby="tree-title">
    <div class="tree-head">
        <h2 class="card__title" id="tree-title">Team Tree</h2>
        <div class="tree-zoom">
            <button class="btn btn--ghost" type="button" data-tree-zoom="-1" aria-label="Zoom out">−</button>
            <button class="btn btn--ghost" type="button" data-tree-zoom="0" aria-label="Reset zoom">100%</button>
            <button class="btn btn--ghost" type="button" data-tree-zoom="1" aria-label="Zoom in">+</button>
        </div>
    </div>
    <ul class="tree-legend">
        <li><span class="tree-dot is-active"></span>Active</li>
        <li><span class="tree-dot"></span>Inactive</li>
        <li class="is-gold"><?= icon('trophy', 14) ?>Salary achiever</li>
    </ul>
    <div class="tree" data-tree tabindex="0" aria-label="Team tree. Drag or scroll to move.">
        <noscript><p class="text-muted">Turn on JavaScript to see the tree. The full list is below.</p></noscript>
    </div>
    <p class="tree-tip text-muted">Tap a member for details. Tap +N to open their team.</p>
    <template id="tree-trophy"><?= icon('trophy', 14) ?></template>
    <script type="application/json" id="tree-data"><?= json_encode($tree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</section>

<dialog class="modal" id="tree-modal" aria-labelledby="tree-modal-title">
    <div class="modal__body">
        <h2 class="modal__title" id="tree-modal-title"></h2>
        <dl class="plan-card__nums" data-tree-details></dl>
        <h3 class="tree-salary-title">Monthly Salary</h3>
        <dl class="plan-card__nums" data-tree-salary></dl>
        <form method="dialog">
            <button class="btn btn--ghost btn--block" type="submit">Close</button>
        </form>
    </div>
</dialog>
