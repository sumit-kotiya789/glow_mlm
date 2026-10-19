<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Models\PlanTerm;
use App\Models\Setting;

/**
 * Every configurable number from docs/RULES.md, plus plan terms.
 */
final class SettingsController
{
    /** group => [key => [label, type]] ; types: bool money pct int day time address levels period */
    public const FIELDS = [
        'Signup bonus' => [
            'signup_bonus_enabled' => ['Welcome bonus on registration (ON / OFF)', 'bool'],
            'signup_bonus_amount' => ['Bonus amount ($)', 'money'],
        ],
        'Deposit' => [
            'min_deposit' => ['Minimum deposit ($)', 'money'],
            'deposit_address' => ['USDT (BEP-20) receiving address', 'address'],
        ],
        'Investment' => [
            'min_invest' => ['Minimum per investment ($)', 'money'],
            'min_invest_bonus' => ['Minimum per investment if user got signup bonus ($)', 'money'],
            'max_invest' => ['Maximum per investment ($)', 'money'],
        ],
        'Withdrawal' => [
            'min_withdraw' => ['Minimum per request ($)', 'money'],
            'max_withdraw' => ['Maximum per request ($)', 'money'],
            'withdraw_fee_pct' => ['Fee (%)', 'pct'],
        ],
        'Transfers' => [
            'min_transfer' => ['Minimum per transfer, P2P and wallet to Deposit ($)', 'money'],
        ],
        'Network income' => [
            'direct_pct' => ['Direct sponsor income (%)', 'pct'],
            'level_deposit_pct' => ['Level deposit income, levels 2–10 (%)', 'pct'],
            'roi_level_pcts' => ['Level ROI income on the 4% part (%), levels 1–10', 'levels'],
        ],
        'Salary' => [
            'salary_min_self' => ['Own investment needed ($)', 'money'],
            'salary_directs' => ['Active directs needed', 'int'],
            'salary_level2' => ['Active level-2 needed', 'int'],
            'salary_window_days' => ['Days after registration to qualify', 'int'],
            'salary_amount' => ['Salary per month ($)', 'money'],
            'salary_months' => ['Number of months', 'int'],
            'salary_pay_day' => ['Pay day of month (1–31, same for all)', 'day'],
        ],
        'Lucky draw defaults' => [
            'lucky_prize' => ['Prize per winner ($)', 'money'],
            'lucky_winners' => ['Number of winners', 'int'],
        ],
        'Payout' => [
            'payout_period' => ['Payout period (Hour = testing: each "day" of the rules lasts 1 hour)', 'period'],
            'payout_time' => ['Daily payout time, India time (HH:MM, day mode)', 'time'],
        ],
    ];

    public function edit(): void
    {
        view('admin/settings', [
            'title' => 'Settings',
            'fields' => self::FIELDS,
            'values' => Setting::all(),
            'levels' => Setting::roiLevels(),
            'terms' => PlanTerm::all(false),
        ], 'admin');
    }

    public function update(): void
    {
        $errors = [];
        $clean = [];
        foreach (self::FIELDS as $group) {
            foreach ($group as $key => [$label, $type]) {
                $value = $type === 'levels' ? ($_POST[$key] ?? []) : input($key);
                $result = self::validate($type, $value);
                if ($result === null) {
                    $errors[$key] = 'Invalid value for "' . $label . '".';
                } else {
                    $clean[$key] = $result;
                }
            }
        }
        if (!$errors && (float) $clean['min_invest'] > (float) $clean['max_invest']) {
            $errors['min_invest'] = 'Minimum investment is above the maximum.';
        }
        if (!$errors && (float) $clean['min_withdraw'] > (float) $clean['max_withdraw']) {
            $errors['min_withdraw'] = 'Minimum withdrawal is above the maximum.';
        }

        $terms = [];
        foreach ((array) ($_POST['terms'] ?? []) as $id => $term) {
            $days = self::validate('int', (string) ($term['days'] ?? ''));
            $principal = self::validate('pct', (string) ($term['principal_rate'] ?? ''));
            $profit = self::validate('pct', (string) ($term['profit_rate'] ?? ''));
            if ($days === null || (int) $days < 1 || $principal === null || $profit === null) {
                $errors['terms'] = 'Each term needs days (1+) and both rates (0–100).';
                break;
            }
            $terms[(int) $id] = [$days, $principal, $profit, empty($term['is_active']) ? 0 : 1];
        }
        if (count(array_unique(array_column($terms, 0))) !== count($terms)) {
            $errors['terms'] = 'Two terms have the same number of days.';
        }

        if ($errors) {
            fail('/admin/settings', $errors);
        }

        Database::transaction(static function () use ($clean, $terms): void {
            foreach ($clean as $key => $value) {
                Setting::set($key, $value);
            }
            // Clear days first so swapping two terms' days doesn't hit the UNIQUE constraint.
            foreach ($terms as $id => $t) {
                Database::run('UPDATE plan_terms SET days = ? WHERE id = ?', [-$id, $id]);
            }
            foreach ($terms as $id => [$days, $principal, $profit, $active]) {
                Database::run(
                    'UPDATE plan_terms SET days = ?, principal_rate = ?, profit_rate = ?, is_active = ? WHERE id = ?',
                    [$days, $principal, $profit, $active, $id]
                );
            }
        });
        toast('Settings saved. New values apply to new investments and future payouts.');
        redirect('/admin/settings');
    }

    /** Normalised string value, or null when invalid. */
    private static function validate(string $type, mixed $value): ?string
    {
        if ($type === 'levels') {
            if (!is_array($value) || count($value) !== 10) {
                return null;
            }
            $out = [];
            foreach ($value as $v) {
                $pct = self::validate('pct', is_string($v) ? trim($v) : '');
                if ($pct === null) {
                    return null;
                }
                $out[] = $pct;
            }
            return implode(',', $out);
        }
        if (!is_string($value)) {
            return null;
        }
        return match ($type) {
            'bool' => $value === '1' ? '1' : '0',
            'money' => preg_match('/^\d{1,7}(\.\d{1,2})?$/', $value) ? $value : null,
            'pct' => preg_match('/^\d{1,3}(\.\d{1,3})?$/', $value) && (float) $value <= 100 ? $value : null,
            'int' => preg_match('/^\d{1,6}$/', $value) ? (string) (int) $value : null,
            'day' => preg_match('/^\d{1,2}$/', $value) && (int) $value >= 1 && (int) $value <= 31 ? (string) (int) $value : null,
            'time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : null,
            'address' => preg_match('/^0x[a-fA-F0-9]{40}$/', $value) ? $value : null,
            'period' => in_array($value, ['day', 'hour'], true) ? $value : null,
            default => null,
        };
    }
}
