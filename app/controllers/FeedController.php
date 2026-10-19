<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ledger;
use App\Models\TradePair;

/**
 * System live feed (platform activity) and live trading (sample data).
 */
final class FeedController
{
    public function liveFeed(): void
    {
        $pager = Ledger::feed(page_param());
        $rows = array_map(static fn ($r) => [
            'time' => fmt_date($r['created_at'], 'd-M H:i'),
            'event' => Ledger::TYPES[$r['type']] ?? $r['type'],
            'who' => substr($r['user_code'], 0, 4) . '•••' . substr($r['user_code'], -2),
            'amount' => [money(abs((float) $r['amount'])), 'num ' . ((float) $r['amount'] < 0 ? 'is-red' : 'is-green')],
        ], $pager['rows']);

        view('list', [
            'title' => 'System Live Feed',
            'heading' => 'System Live Feed',
            'subtitle' => 'Recent platform activity.',
            'icon' => 'activity',
            'columns' => ['time' => 'Time', 'event' => 'Event', 'who' => 'User ID', 'amount' => 'Amount'],
            'rows' => $rows,
            'pager' => $pager,
            'empty' => 'No activity yet',
        ]);
    }

    public function liveTrading(): void
    {
        $trades = TradePair::all();
        $wins = count(array_filter($trades, static fn ($t) => (float) $t['pnl'] >= 0));
        view('live-trading', [
            'title' => 'Live Trading',
            'trades' => $trades,
            'summary' => [
                ['label' => 'Open Positions', 'value' => (string) count($trades), 'tone' => 'cyan'],
                ['label' => 'In Profit', 'value' => (string) $wins, 'tone' => 'green'],
                ['label' => 'In Loss', 'value' => (string) (count($trades) - $wins), 'tone' => 'red'],
                [
                    'label' => 'Avg PNL',
                    'value' => sprintf('%+.2f%%', $trades ? array_sum(array_column($trades, 'pnl')) / count($trades) : 0),
                    'tone' => 'amber',
                ],
            ],
        ]);
    }
}
