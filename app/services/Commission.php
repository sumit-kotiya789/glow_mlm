<?php

declare(strict_types=1);

namespace App\Services;

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
            if ($upline['is_blocked']) {
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
            if ($upline['is_blocked']) {
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

    /** @return array<int, array<string, mixed>> */
    private static function uplines(int $userId): array
    {
        return self::$uplines[$userId] ??= User::uplines($userId, 10);
    }
}
