<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Investment;
use App\Models\Setting;

/**
 * Payout runs: ROI for every running plan, maturities, monthly salary.
 *
 * A "period" is one day ('2026-10-15') or, in testing mode, one hour ('2026-10-15 13:00')
 * (setting payout_period = day | hour). Plans count periods, so a 25-day plan matures after
 * 25 hours in hour mode. Each period is paid at most once (payout_runs primary key).
 * Rules: docs/RULES.md section 10.
 */
final class Payout
{
    public static function hourly(): bool
    {
        return Setting::get('payout_period') === 'hour';
    }

    /** 'day' or 'hour'. */
    public static function unit(): string
    {
        return self::hourly() ? 'hour' : 'day';
    }

    /**
     * Pay one period. Returns null if it was already paid.
     *
     * @return array{date: string, investments: int, salaries: int}|null
     */
    public static function run(string $period): ?array
    {
        if (Database::value('SELECT 1 FROM payout_runs WHERE run_date = ?', [$period]) !== null) {
            return null;
        }
        // Ledger rows carry the payout moment: configured time for days, the hour itself for hours.
        $stamp = strlen($period) === 10
            ? $period . ' ' . (Setting::get('payout_time') ?: '00:00') . ':00'
            : $period . ':00';

        return Database::transaction(static function () use ($period, $stamp): array {
            Database::insert('payout_runs', ['run_date' => $period, 'investments_paid' => 0, 'created_at' => now()]);
            $previousClock = freeze_clock($stamp);
            try {
                // A plan bought during period P gets its first payout in the next period.
                $investments = Database::all(
                    "SELECT * FROM investments WHERE status = 'Active' AND started_at < ?
                       AND (last_paid_on IS NULL OR last_paid_on < ?) ORDER BY id",
                    [self::start($period), $period]
                );
                foreach ($investments as $inv) {
                    Investment::payDay($inv, $period);
                }
                $salaries = Salary::payMonthly(substr($period, 0, 10));
            } finally {
                freeze_clock($previousClock);
            }
            Database::run('UPDATE payout_runs SET investments_paid = ? WHERE run_date = ?', [count($investments), $period]);
            return ['date' => $period, 'investments' => count($investments), 'salaries' => $salaries];
        });
    }

    public static function lastRunDate(): ?string
    {
        $date = Database::value('SELECT MAX(run_date) FROM payout_runs');
        return $date === null ? null : (string) $date;
    }

    /**
     * Cron / "Run due payouts": pays every missed period up to now.
     * Day mode waits for the configured payout time; hour mode pays the current hour.
     *
     * @return list<array{date: string, investments: int, salaries: int}>
     */
    public static function runDue(): array
    {
        if (self::hourly()) {
            $until = date('Y-m-d H:00', (int) strtotime(now()));
        } else {
            $time = Setting::get('payout_time') ?: '00:00';
            $until = substr(now(), 11, 5) >= $time ? today() : self::addDays(today(), -1);
        }
        $last = self::lastRunDate();
        $results = [];
        for ($period = $last ? self::next($last) : $until; $period <= $until; $period = self::next($period)) {
            $result = self::run($period);
            if ($result !== null) {
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * Testing helper: pay the next $count periods right now, even if they are in the future.
     *
     * @return list<array{date: string, investments: int, salaries: int}>
     */
    public static function simulate(int $count = 1): array
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $last = self::lastRunDate();
            $result = self::run($last ? self::next($last) : self::current());
            if ($result !== null) {
                $results[] = $result;
            }
        }
        return $results;
    }

    /** Period that contains now(). */
    public static function current(): string
    {
        return self::hourly() ? date('Y-m-d H:00', (int) strtotime(now())) : today();
    }

    /** Period after $period in the current mode (also converts a key from the other mode). */
    public static function next(string $period): string
    {
        if (!self::hourly()) {
            return self::addDays(substr($period, 0, 10), 1);
        }
        return strlen($period) === 10
            ? self::addDays($period, 1) . ' 00:00'
            : date('Y-m-d H:00', (int) strtotime($period . ':00 +1 hour'));
    }

    /** First second of a period, as a DATETIME string. */
    public static function start(string $period): string
    {
        return strlen($period) === 10 ? $period . ' 00:00:00' : $period . ':00';
    }

    /** Human label for a period key. */
    public static function label(string $period): string
    {
        return strlen($period) === 10 ? fmt_date($period) : fmt_date($period . ':00', 'd-M-Y H:i');
    }

    public static function addDays(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' ' . ($days >= 0 ? '+' : '') . $days . ' days'));
    }
}
