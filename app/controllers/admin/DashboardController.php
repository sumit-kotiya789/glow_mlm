<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Models\Ledger;
use App\Models\Setting;
use App\Services\Payout;

/**
 * Admin overview + daily payout buttons.
 */
final class DashboardController
{
    public function index(): void
    {
        $num = static fn (string $sql): float => (float) Database::value($sql);
        $count = static fn (string $sql): int => (int) Database::value($sql);

        $pendingDeposits = $count("SELECT COUNT(*) FROM deposits WHERE status = 'Pending'");
        $pendingWithdrawals = $count("SELECT COUNT(*) FROM withdrawals WHERE status = 'Pending'");
        $lastRun = Payout::lastRunDate();

        view('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                ['label' => 'Members', 'value' => (string) $count('SELECT COUNT(*) FROM users'), 'tone' => 'cyan'],
                ['label' => 'Active IDs', 'value' => (string) $count('SELECT COUNT(*) FROM users WHERE activated_at IS NOT NULL'), 'tone' => 'green'],
                ['label' => 'Active Investment', 'value' => money($num("SELECT COALESCE(SUM(amount), 0) FROM investments WHERE status = 'Active'")), 'tone' => 'gold'],
                ['label' => 'Deposits Approved', 'value' => money($num("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'Approved'")), 'tone' => 'green'],
                ['label' => 'Withdrawals Paid', 'value' => money($num("SELECT COALESCE(SUM(net_amount), 0) FROM withdrawals WHERE status = 'Paid'")), 'tone' => 'amber'],
                ['label' => 'Member Wallets Total', 'value' => money($num('SELECT COALESCE(SUM(deposit_balance + roi_balance + income_balance), 0) FROM users')), 'tone' => 'muted'],
            ],
            'queues' => [
                ['/admin/deposits', 'Pending deposits', $pendingDeposits, money($num("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'Pending'")), 'dollar'],
                ['/admin/withdrawals', 'Pending withdrawals', $pendingWithdrawals, money($num("SELECT COALESCE(SUM(net_amount), 0) FROM withdrawals WHERE status = 'Pending'")), 'card'],
                ['/admin/tickets', 'Open tickets', $count("SELECT COUNT(*) FROM tickets WHERE status = 'Open'"), '', 'ticket'],
            ],
            'lastRun' => $lastRun ? Payout::label($lastRun) : 'Never',
            'next' => Payout::label($lastRun ? Payout::next($lastRun) : Payout::current()),
            'unit' => Payout::unit(),
            'payoutTime' => Setting::get('payout_time'),
            'activity' => array_map(static fn ($r) => [
                'time' => fmt_date($r['created_at'], 'd-M H:i'),
                'event' => Ledger::TYPES[$r['type']] ?? $r['type'],
                'who' => $r['user_code'],
                'amount' => [money(abs((float) $r['amount'])), 'num ' . ((float) $r['amount'] < 0 ? 'is-red' : 'is-green')],
            ], array_slice(Ledger::feed(1)['rows'], 0, 8)),
        ], 'admin');
    }

    /** Pay every period that is due up to now (same as the cron). */
    public function runPayout(): void
    {
        $results = Payout::runDue();
        toast($results ? self::summary($results, 'Paid') : 'Nothing due yet. Last paid: ' . Payout::label((string) Payout::lastRunDate()) . '.');
        redirect('/admin');
    }

    /** Testing: pay the next 1 / 5 / 25 periods right now. */
    public function simulate(): void
    {
        $count = in_array((int) input('count'), [1, 5, 25], true) ? (int) input('count') : 1;
        toast(self::summary(Payout::simulate($count), 'Simulated'));
        redirect('/admin');
    }

    /** @param list<array{date: string, investments: int, salaries: int}> $results */
    private static function summary(array $results, string $verb): string
    {
        if (!$results) {
            return 'Nothing was paid.';
        }
        $last = end($results);
        return sprintf(
            '%s %d %s%s up to %s: %d plan payouts, %d salaries.',
            $verb,
            count($results),
            Payout::unit(),
            count($results) > 1 ? 's' : '',
            Payout::label($last['date']),
            array_sum(array_column($results, 'investments')),
            array_sum(array_column($results, 'salaries'))
        );
    }
}
