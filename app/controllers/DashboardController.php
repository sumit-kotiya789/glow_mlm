<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Investment;
use App\Models\PlanTerm;
use App\Models\TradePair;
use App\Services\Salary;

/**
 * Dashboard and root redirect.
 */
final class DashboardController
{
    public function home(): void
    {
        redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function index(): void
    {
        $user = Auth::user();
        $userId = (int) $user['id'];
        $summary = Investment::summary($userId);

        view('dashboard', [
            'title' => 'Dashboard',
            'bodyClass' => 'page-mint',
            'user' => $user,
            'stats' => [
                ['label' => 'Compound Investment', 'value' => money($summary['compound']), 'tone' => 'gold'],
                ['label' => 'FD Investment', 'value' => money($summary['fixed']), 'tone' => 'gold'],
                ['label' => 'Deposit Wallet', 'value' => money($user['deposit_balance']), 'tone' => 'muted'],
                ['label' => 'Compound ROI Wallet', 'value' => money($user['roi_balance']), 'tone' => 'green'],
                ['label' => 'FD ROI Wallet', 'value' => money($user['fd_balance']), 'tone' => 'cyan',
                    'hint' => 'Locked in running FD: ' . money($summary['locked'])],
                ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'amber'],
            ],
            'salary' => Salary::progress($user),
            'referral' => self::referralLink($user['user_code']),
            'terms' => PlanTerm::all(),
            'plans' => Database::all(
                "SELECT * FROM investments WHERE user_id = ? AND status = 'Active' ORDER BY started_at DESC LIMIT 4",
                [$userId]
            ),
            'activeCount' => $summary['active'],
            'trades' => TradePair::all(),
            'scripts' => ['js/ticker.js'],
        ]);
    }

    /** Absolute register link with the user's code as sponsor. */
    public static function referralLink(string $userCode): string
    {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . url('/register?ref=' . rawurlencode($userCode));
    }
}
