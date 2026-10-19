<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Admin-configurable values (key/value table). Missing keys fall back to DEFAULTS.
 */
final class Setting
{
    public const DEFAULTS = [
        'signup_bonus_enabled' => '1',
        'signup_bonus_amount' => '5',
        'min_deposit' => '5',
        'min_invest' => '5',
        'min_invest_bonus' => '10', // Minimum investment for users who received the signup bonus.
        'max_invest' => '1000',
        'min_withdraw' => '1',
        'max_withdraw' => '1000',
        'min_transfer' => '1', // Minimum per P2P / wallet-to-Deposit transfer.
        'withdraw_fee_pct' => '10',
        'direct_pct' => '10',
        'level_deposit_pct' => '1',
        'roi_level_pcts' => '5,1,1,1,1,1,1,1,1,1', // ROI-on-ROI % for levels 1..10, applied to the downline's daily principal part.
        'salary_min_self' => '50',
        'salary_directs' => '5',
        'salary_level2' => '10',
        'salary_window_days' => '15',
        'salary_amount' => '100',
        'salary_months' => '12',
        'salary_pay_day' => '1',
        'lucky_prize' => '100',
        'lucky_winners' => '100',
        'payout_time' => '00:00',
        // 'hour' = testing mode: every "day" of the rules becomes one hour.
        'payout_period' => 'day',
        'deposit_address' => '0x0000000000000000000000000000000000000000',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = Database::all('SELECT name, value FROM settings');
            self::$cache = array_column($rows, 'value', 'name') + self::DEFAULTS;
        }
        return self::$cache;
    }

    public static function get(string $key): string
    {
        return (string) (self::all()[$key] ?? '');
    }

    public static function num(string $key): float
    {
        return (float) self::get($key);
    }

    public static function set(string $key, string $value): void
    {
        Database::run('DELETE FROM settings WHERE name = ?', [$key]);
        Database::insert('settings', ['name' => $key, 'value' => $value]);
        self::$cache = null;
    }

    /** @return list<float> 10 percentages, index 0 = level 1 */
    public static function roiLevels(): array
    {
        $pcts = array_map('floatval', explode(',', self::get('roi_level_pcts')));
        return array_slice(array_pad($pcts, 10, 0.0), 0, 10);
    }
}
