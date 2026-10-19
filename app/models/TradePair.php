<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Sample AI trading positions (random seeder values, not market data).
 */
final class TradePair
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM trade_pairs ORDER BY pnl DESC');
    }
}
