<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Investment terms (25 / 50 / 100 days) and their daily rates.
 */
final class PlanTerm
{
    /** @return list<array<string, mixed>> */
    public static function all(bool $activeOnly = true): array
    {
        return Database::all(
            'SELECT * FROM plan_terms' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY days'
        );
    }

    /** @return array<string, mixed>|null */
    public static function findActive(int $days): ?array
    {
        return Database::one('SELECT * FROM plan_terms WHERE days = ? AND is_active = 1', [$days]);
    }

    public static function dailyRate(array $term): float
    {
        return (float) $term['principal_rate'] + (float) $term['profit_rate'];
    }
}
