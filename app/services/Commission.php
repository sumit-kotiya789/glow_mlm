<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Ledger;
use App\Models\Setting;
use App\Models\User;

/**
 * Network incomes: direct + level deposit income on every investment, ROI-on-ROI on every
 * daily principal part of a compound plan. Rules: docs/RULES.md section 6.
 */
final class Commission
{
    /** @var array<int, array<int, array<string, mixed>>> uplines per user, cached per request */
    private static array $uplines = [];

    public static function onInvest(int $investorId, float $amount, int $investmentId): void
    {
        foreach (self::uplines($investorId) as $level => $upline) {
            if (!self::earns($upline, $level)) {
                continue;
            }
            $direct = $level === 1;
            $pct = Setting::num($direct ? 'direct_pct' : 'level_deposit_pct');
            Ledger::post((int) $upline['id'], 'income', $direct ? 'direct' : 'level', $amount * $pct / 100, [
                'from_user_id' => $investorId,
                'level' => $level,
                'ref_id' => $investmentId,
                'note' => pct($pct) . ' of ' . money($amount) . ' investment',
            ]);
        }
    }

    public static function onDailyPart(int $investorId, float $part, int $investmentId): void
    {
        $pcts = Setting::roiLevels();
        foreach (self::uplines($investorId) as $level => $upline) {
            if (!self::earns($upline, $level)) {
                continue;
            }
            Ledger::post((int) $upline['id'], 'income', 'roi_on_roi', $part * $pcts[$level - 1] / 100, [
                'from_user_id' => $investorId,
                'level' => $level,
                'ref_id' => $investmentId,
                'note' => pct($pcts[$level - 1]) . ' of ' . money($part) . ' daily part',
            ]);
        }
    }

    /**
     * An upline earns level N income only while not blocked, with a running plan of their own and at
     * least N active directs (active = has a running plan). Income they miss is not passed further up.
     */
    private static function earns(array $upline, int $level): bool
    {
        if ((int) $upline['is_blocked'] === 1) {
            return false;
        }
        $row = Database::one(
            "SELECT EXISTS(SELECT 1 FROM investments WHERE user_id = ? AND status = 'Active') AS active,
                    (SELECT COUNT(DISTINCT u.id) FROM users u JOIN investments i ON i.user_id = u.id AND i.status = 'Active'
                      WHERE u.sponsor_id = ?) AS directs",
            [$upline['id'], $upline['id']]
        );
        return (int) $row['active'] === 1 && (int) $row['directs'] >= $level;
    }

    /** @return array<int, array<string, mixed>> */
    private static function uplines(int $userId): array
    {
        return self::$uplines[$userId] ??= User::uplines($userId, 10);
    }
}
