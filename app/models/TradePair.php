<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Sample AI trading positions (random seeder values, not market data).
 */
final class TradePair
{
    /**
     * Positions stay as seeded (pair, side, leverage, entry price). The latest price and PNL
     * are re-rolled on every call, so each page load shows different numbers.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $rows = Database::all('SELECT * FROM trade_pairs');
        foreach ($rows as &$row) {
            $entry = (float) $row['entry_price'];
            $latest = $entry * (1 + random_int(-150, 450) / 1000);
            $row['latest_price'] = round($latest, 6);
            $row['pnl'] = round(
                ($latest - $entry) / $entry * (int) $row['leverage'] * 100 * ($row['side'] === 'Long' ? 1 : -1),
                2
            );
        }
        unset($row);
        usort($rows, static fn($a, $b) => $b['pnl'] <=> $a['pnl']);
        return $rows;
    }
}
