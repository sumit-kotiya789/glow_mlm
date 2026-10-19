<?php

/**
 * AI trading status card with live client-side search.
 *
 * @var list<array<string, mixed>> $trades
 */

$decimals = static fn ($n) => number_format((float) $n, 6, '.', '');
?>
<section class="card card--topline trade-card" aria-labelledby="trade-title">
    <div class="trade-card__head">
        <h2 class="trade-card__title" id="trade-title"><?= icon('activity', 26) ?> AI Trading Status</h2>
        <p class="trade-card__stamp"><time datetime="<?= e(date(DATE_ATOM)) ?>"><?= e(date('Y-m-d\TH:i:sP')) ?></time> · <span class="tag">Sample data</span></p>
        <label class="search">
            <span class="sr-only">Search pair</span>
            <?= icon('search', 20) ?>
            <input type="search" placeholder="Search Pair..." autocomplete="off" data-table-search="trade-table">
        </label>
    </div>
    <div class="table-scroll skeleton-load">
        <table class="table table--trade" id="trade-table">
            <thead>
                <tr><th scope="col">Pair</th><th scope="col">Side</th><th scope="col">Entry</th><th scope="col">Latest</th><th scope="col" class="num">PNL</th></tr>
            </thead>
            <tbody>
            <?php foreach ($trades as $t) :
                $up = (float) $t['pnl'] >= 0; ?>
                <tr>
                    <td class="trade-pair"><?= e($t['pair']) ?></td>
                    <td class="<?= $t['side'] === 'Long' ? 'is-green' : 'is-red' ?>"><?= e($t['side'] . ' ' . $t['leverage'] . 'X') ?></td>
                    <td><?= e($decimals($t['entry_price'])) ?></td>
                    <td><?= e($decimals($t['latest_price'])) ?></td>
                    <td class="num <?= $up ? 'is-green' : 'is-red' ?>"><?= e(sprintf('%+.2f%%', $t['pnl'])) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <p class="table-noresult" hidden>No pairs match your search.</p>
    </div>
</section>
