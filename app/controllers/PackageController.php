<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Investment;
use App\Models\PlanTerm;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use DomainException;

/**
 * Invest (Compound / Fixed Deposit), my plans, compound + move-to-wallet actions, calculator.
 */
final class PackageController
{
    public function details(): void
    {
        $terms = PlanTerm::all();
        view('package-details', [
            'title' => 'Invest',
            'terms' => $terms,
            'deposit' => (float) Auth::user()['deposit_balance'],
            'min' => User::minInvest((int) Auth::id()),
            'max' => Setting::num('max_invest'),
            'selectedDays' => (int) ($_GET['days'] ?? ($terms[0]['days'] ?? 25)),
        ]);
    }

    public function invest(): void
    {
        $plan = input('plan');
        $days = (int) input('days');
        $raw = input('amount');
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
            fail('/package-details', ['amount' => 'Enter a valid amount (up to 2 decimals).']);
        }
        try {
            $id = Investment::open((int) Auth::id(), $plan, $days, (float) $raw);
        } catch (DomainException $e) {
            fail('/package-details', ['amount' => $e->getMessage()]);
        }
        $inv = Investment::findForUser((int) Auth::id(), $id);
        notice('Investment Successful', 'Your plan is running. Payouts start from the next period.', [
            'Plan' => Investment::label($inv),
            'Amount' => money($inv['amount']),
            'Daily rate' => pct(PlanTerm::dailyRate($inv)),
            'First payout' => Investment::firstPayout($inv),
            'Matures on' => Investment::maturesOn($inv),
            'Deposit wallet left' => money(User::find((int) Auth::id())['deposit_balance']),
        ]);
        redirect('/my-packages');
    }

    public function mine(): void
    {
        $this->plans(null, 'My Packages', 'All your plans. Compound the daily principal part or move it to your Compound ROI wallet.');
    }

    public function compound(): void
    {
        $this->plans('compound', 'Compound Packages', 'Compound plans only. Only the latest day\'s part can be compounded.');
    }

    public function compoundPart(): void
    {
        $back = $this->back();
        try {
            $part = Investment::compound((int) Auth::id(), (int) input('id'));
            $inv = Investment::findForUser((int) Auth::id(), (int) input('id'));
            notice('Compounded', 'The latest part was added to your principal.', [
                'Plan' => Investment::label($inv),
                'Compounded' => money($part),
                'New principal' => money($inv['principal']),
                'Pending left' => money($inv['pending']),
            ]);
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
        redirect($back);
    }

    /** Pending principal parts -> Compound ROI wallet (instant). Withdrawn from the wallet page. */
    public function releasePending(): void
    {
        $back = $this->back();
        $raw = input('amount');
        $userId = (int) Auth::id();
        try {
            if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
                throw new DomainException('Enter a valid amount (up to 2 decimals).');
            }
            Investment::release($userId, (int) input('id'), (float) $raw);
            $inv = Investment::findForUser($userId, (int) input('id'));
            notice('Moved to Compound ROI Wallet', 'Withdraw it from the Withdrawal page.', [
                'Plan' => Investment::label($inv),
                'Amount' => money($raw),
                'New principal' => money($inv['principal']),
                'Pending left' => money($inv['pending']),
                'Compound ROI wallet' => money(User::find($userId)['roi_balance']),
            ]);
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
        redirect($back);
    }

    /** No id: all plans, each linking to its timeline. With id: that plan's timeline. */
    public function history(): void
    {
        if (!isset($_GET['id'])) {
            $pager = Investment::paginateForUser((int) Auth::id(), page_param());
            view('list', [
                'title' => 'Investment History',
                'heading' => 'Investment History',
                'subtitle' => 'Every plan you started. Open one to see each payout, compound and withdrawal.',
                'icon' => 'history',
                'columns' => [
                    'plan' => 'Plan',
                    'started' => 'Started',
                    'amount' => 'Invested',
                    'principal' => 'Principal',
                    'progress' => 'Progress',
                    'status' => 'Status',
                    'open' => '',
                ],
                'rows' => array_map(static fn($i) => [
                    'plan' => [Investment::label($i), 'is-gold', url('/investment?id=' . $i['id'])],
                    'started' => fmt_date($i['started_at'], 'd-M-Y H:i'),
                    'amount' => [money($i['amount']), 'num'],
                    'principal' => [money($i['principal']), 'num'],
                    'progress' => Investment::progressLabel($i),
                    'status' => [$i['status'], status_class($i['status'])],
                    'open' => ['View history', 'is-gold', url('/investment?id=' . $i['id'])],
                ], $pager['rows']),
                'pager' => $pager,
                'empty' => 'No investments yet',
            ]);
            return;
        }
        $inv = Investment::findForUser((int) Auth::id(), (int) $_GET['id']);
        if ($inv === null) {
            http_response_code(404);
            view('404', ['title' => 'Not Found']);
            return;
        }
        $compound = $inv['plan'] === 'compound';
        $rows = array_reverse(Investment::history($inv)); // newest first
        view('list', [
            'title' => 'Plan History',
            'heading' => Investment::label($inv) . ' · ' . $inv['status'],
            'subtitle' => 'Every movement of this plan, newest first. ' . Investment::progressLabel($inv) . '.',
            'icon' => 'history',
            'summary' => [
                ['label' => 'Invested', 'value' => money($inv['amount']), 'tone' => 'muted'],
                ['label' => 'Principal now', 'value' => money($inv['principal']), 'tone' => 'gold'],
                $compound
                ? ['label' => 'Pending now', 'value' => money($inv['pending']), 'tone' => 'cyan']
                : ['label' => 'Locked ROI now', 'value' => money($inv['accrued']), 'tone' => 'cyan'],
                ['label' => 'Paid to ' . ($compound ? 'Compound' : 'FD') . ' ROI wallet', 'value' => money(array_sum(array_column($rows, 'roi'))), 'tone' => 'green'],
            ],
            'columns' => [
                'date' => 'Date',
                'event' => 'Event',
                'detail' => 'Detail',
                'roi' => 'To ROI wallet',
                'principal' => 'Principal after',
                'held' => $compound ? 'Pending after' : 'Locked ROI after',
            ],
            'rows' => array_map(static fn($r) => [
                'date' => fmt_date($r['date'], 'd-M-Y H:i'),
                'event' => [$r['event'], 'is-gold'],
                'detail' => $r['detail'],
                'roi' => $r['roi'] > 0 ? ['+' . money($r['roi']), 'num is-green'] : '—',
                'principal' => [money($r['principal']), 'num'],
                'held' => [money($r['held']), 'num'],
            ], $rows),
            'empty' => 'No activity yet',
        ]);
    }

    public function calculator(): void
    {
        $terms = PlanTerm::all();
        $plan = ($_GET['plan'] ?? 'compound') === 'fixed' ? 'fixed' : 'compound';
        $days = (int) ($_GET['days'] ?? ($terms[0]['days'] ?? 25));
        $amount = isset($_GET['amount']) ? (float) $_GET['amount'] : null;
        $term = PlanTerm::findActive($days);
        $error = '';
        $result = null;
        if ($amount !== null) {
            if ($term === null) {
                $error = 'Choose a valid term.';
            } elseif ($amount <= 0 || $amount > 1000000) {
                $error = 'Enter an amount between 0.01 and 1,000,000.';
            } else {
                $result = self::project($plan, $amount, $term);
            }
        }
        view('calculator', [
            'title' => 'Calculator',
            'terms' => $terms,
            'plan' => $plan,
            'days' => $days,
            'amount' => $amount,
            'error' => $error,
            'result' => $result,
        ]);
    }

    /**
     * Illustrative projection using the same daily maths as the payout engine.
     *
     * @return array<string, float>
     */
    public static function project(string $plan, float $amount, array $term): array
    {
        $days = (int) $term['days'];
        $pr = (float) $term['principal_rate'] / 100;
        $pf = (float) $term['profit_rate'] / 100;
        if ($plan === 'fixed') {
            $roi = round($amount * ($pr + $pf), 2) * $days;
            return ['daily' => round($amount * ($pr + $pf), 2), 'profit' => $roi, 'final' => $amount + $roi, 'total' => $amount + $roi];
        }
        // Compound every day (allowed until the last day).
        $principal = $amount;
        $profit = 0.0;
        for ($d = 1; $d <= $days; $d++) {
            $part = round($principal * $pr, 2);
            $profit += round($principal * $pf, 2);
            $principal = round($principal + $part, 2);
        }
        // Withdraw every day's part instead: principal shrinks, everything is paid out along the way.
        $base = $amount;
        $paidOut = 0.0;
        for ($d = 1; $d <= $days; $d++) {
            $part = round($base * $pr, 2);
            $paidOut += $part + round($base * $pf, 2);
            $base = $d < $days ? round($base - $part, 2) : $base;
        }
        // At maturity the invested amount is not returned, only what grew on top of it.
        return [
            'daily' => round($amount * ($pr + $pf), 2),
            'profit' => $profit,
            'final' => $principal - $amount,
            'total' => $principal - $amount + $profit,
            'withdraw_total' => $paidOut,
        ];
    }

    private function plans(?string $plan, string $heading, string $subtitle): void
    {
        view('my-packages', [
            'title' => $heading,
            'heading' => $heading,
            'subtitle' => $subtitle,
            'compoundOnly' => $plan === 'compound',
            'pager' => Investment::paginateForUser((int) Auth::id(), page_param(), $plan),
        ]);
    }

    /**
     * Popup rows for a new withdrawal request (also used by WalletController).
     *
     * @return array<string, string>
     */
    public static function withdrawalRows(int $id): array
    {
        $w = Database::one('SELECT * FROM withdrawals WHERE id = ?', [$id]);
        return [
            'Request' => '#' . $id . ' · Pending',
            'From' => Withdrawal::SOURCES[$w['source']] . ($w['investment_id'] ? ' #' . $w['investment_id'] : ''),
            'Amount' => money($w['amount']),
            'Fee' => money($w['fee']),
            'You get' => money($w['net_amount']),
            'To wallet' => substr($w['wallet'], 0, 8) . '…' . substr($w['wallet'], -6),
        ];
    }

    private function back(): string
    {
        return input('back') === '/compound-packages' ? '/compound-packages' : '/my-packages';
    }
}
