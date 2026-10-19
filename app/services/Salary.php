<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Ledger;
use App\Models\Setting;
use App\Models\User;

/**
 * Monthly salary: qualify within N days of registration, then a fixed amount on one
 * pay day per month for M months. Rules: docs/RULES.md section 7.
 */
final class Salary
{
    /** Mark the user qualified if every condition is met inside the window. */
    public static function check(int $userId): bool
    {
        $user = User::find($userId);
        if ($user === null || $user['salary_qualified_at'] !== null || (int) $user['is_blocked'] === 1) {
            return false;
        }
        $progress = self::progress($user);
        if (!$progress['window_open'] || !$progress['met']) {
            return false;
        }
        Database::run('UPDATE users SET salary_qualified_at = ? WHERE id = ? AND salary_qualified_at IS NULL', [now(), $userId]);
        return true;
    }

    /** @return array<string, mixed> */
    public static function progress(array $user): array
    {
        $levels = User::downlineLevels((int) $user['id'], 2);
        $deadline = self::deadline($user['created_at']);
        $p = [
            'self' => User::totalInvested((int) $user['id']),
            'self_need' => Setting::num('salary_min_self'),
            'directs' => User::activeCount($levels[1] ?? []),
            'directs_need' => (int) Setting::num('salary_directs'),
            'level2' => User::activeCount($levels[2] ?? []),
            'level2_need' => (int) Setting::num('salary_level2'),
            'deadline' => $deadline,
            'window_open' => now() <= $deadline,
            'qualified' => $user['salary_qualified_at'] !== null,
            'months_paid' => (int) $user['salary_months_paid'],
            'months' => (int) Setting::num('salary_months'),
            'amount' => Setting::num('salary_amount'),
            'next_pay' => self::nextPayDate(),
        ];
        $p['met'] = $p['self'] >= $p['self_need'] && $p['directs'] >= $p['directs_need'] && $p['level2'] >= $p['level2_need'];
        return $p;
    }

    /** Last moment to meet the conditions: registration + window days. */
    public static function deadline(string $createdAt): string
    {
        return date('Y-m-d H:i:s', strtotime($createdAt) + (int) Setting::num('salary_window_days') * 86400);
    }

    /** Pay day for a month ('Y-m'), clamped to the month's last day (e.g. 31 -> 30 Nov). */
    public static function payDayFor(string $month): string
    {
        $last = (int) date('t', strtotime($month . '-01'));
        return sprintf('%s-%02d', $month, min((int) Setting::num('salary_pay_day'), $last));
    }

    public static function nextPayDate(): string
    {
        $thisMonth = self::payDayFor(substr(today(), 0, 7));
        return $thisMonth > today() ? $thisMonth : self::payDayFor(date('Y-m', strtotime(substr(today(), 0, 7) . '-01 +1 month')));
    }

    /** Called by the daily payout. Pays every qualified user once on the month's pay day. */
    public static function payMonthly(string $date): int
    {
        $month = substr($date, 0, 7);
        if (self::payDayFor($month) !== $date) {
            return 0;
        }
        $amount = Setting::num('salary_amount');
        $months = (int) Setting::num('salary_months');
        $users = Database::all(
            'SELECT id, salary_months_paid FROM users
             WHERE salary_qualified_at IS NOT NULL AND salary_qualified_at <= ? AND salary_months_paid < ?
               AND (salary_last_month IS NULL OR salary_last_month <> ?) AND is_blocked = 0',
            [$date . ' 23:59:59', $months, $month]
        );
        foreach ($users as $user) {
            $n = (int) $user['salary_months_paid'] + 1;
            Ledger::post((int) $user['id'], 'income', 'salary', $amount, ['note' => "Salary month $n of $months"]);
            Database::run(
                'UPDATE users SET salary_months_paid = ?, salary_last_month = ? WHERE id = ?',
                [$n, $month, $user['id']]
            );
        }
        return count($users);
    }

    /**
     * Qualified users for the member-facing "Salary Achievers" list.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateAchievers(int $page): array
    {
        return Database::paginate(
            'SELECT user_code, name, salary_qualified_at, salary_months_paid FROM users
             WHERE salary_qualified_at IS NOT NULL ORDER BY salary_qualified_at DESC',
            [],
            $page
        );
    }
}
