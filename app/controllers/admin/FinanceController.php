<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Deposit;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Withdrawal;
use DomainException;

/**
 * Deposit approvals, withdrawal processing, ledger.
 */
final class FinanceController
{
    private const DEPOSIT_TABS = ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected', 'all' => 'All'];
    private const WITHDRAWAL_TABS = ['Pending' => 'Pending', 'Paid' => 'Paid', 'Rejected' => 'Rejected', 'all' => 'All'];

    public function deposits(): void
    {
        $status = query_choice('status', array_keys(self::DEPOSIT_TABS), 'Pending');
        view('admin/deposits', [
            'title' => 'Deposits',
            'tabs' => filter_tabs('/admin/deposits', self::DEPOSIT_TABS, $status),
            'pager' => Deposit::paginate($status === 'all' ? null : $status, page_param()),
        ], 'admin');
    }

    public function approveDeposit(): void
    {
        $this->act(static fn () => Deposit::approve((int) input('id'), input('note')), 'Deposit approved and credited.');
        redirect('/admin/deposits');
    }

    public function rejectDeposit(): void
    {
        $note = input('note');
        $this->act(static function () use ($note): void {
            self::requireNote($note);
            Deposit::reject((int) input('id'), $note);
        }, 'Deposit rejected.');
        redirect('/admin/deposits');
    }

    public function withdrawals(): void
    {
        $status = query_choice('status', array_keys(self::WITHDRAWAL_TABS), 'Pending');
        view('admin/withdrawals', [
            'title' => 'Withdrawals',
            'tabs' => filter_tabs('/admin/withdrawals', self::WITHDRAWAL_TABS, $status),
            'pager' => Withdrawal::paginate($status === 'all' ? null : $status, page_param()),
        ], 'admin');
    }

    public function payWithdrawal(): void
    {
        $hash = input('payout_hash');
        $this->act(static function () use ($hash): void {
            if ($hash !== '' && !preg_match('/^0x[a-fA-F0-9]{64}$/', $hash)) {
                throw new DomainException('Payout hash must be 0x + 64 hex characters (or empty).');
            }
            Withdrawal::markPaid((int) input('id'), $hash, mb_substr(input('note'), 0, 255));
        }, 'Withdrawal marked as paid.');
        redirect('/admin/withdrawals');
    }

    public function rejectWithdrawal(): void
    {
        $note = input('note');
        $this->act(static function () use ($note): void {
            self::requireNote($note);
            Withdrawal::reject((int) input('id'), $note);
        }, 'Withdrawal rejected and refunded.');
        redirect('/admin/withdrawals');
    }

    public function ledger(): void
    {
        $types = ['all' => 'All'] + Ledger::TYPES;
        $type = query_choice('type', array_keys($types), 'all');
        $code = strtoupper(trim((string) ($_GET['user'] ?? '')));
        $user = $code !== '' ? User::findByCode($code) : null;
        $pager = Ledger::paginateAll($type === 'all' ? null : $type, $user ? (int) $user['id'] : null, page_param());

        view('list', [
            'title' => 'Ledger',
            'heading' => 'Ledger',
            'subtitle' => 'Every wallet movement' . ($user ? ' for ' . $user['user_code'] : '') . '.',
            'icon' => 'history',
            'tabs' => filter_tabs('/admin/ledger', $types, $type, 'type'),
            'columns' => [
                'date' => 'Date', 'user' => 'User', 'wallet' => 'Wallet', 'type' => 'Type',
                'amount' => 'Amount', 'from' => 'From', 'note' => 'Note',
            ],
            'rows' => array_map(static fn ($r) => [
                'date' => fmt_date($r['created_at'], 'd-M-Y H:i'),
                'user' => [$r['user_code'], '', url('/admin/user?id=' . $r['user_id'])],
                'wallet' => Ledger::WALLETS[$r['wallet']] ?? ucfirst($r['wallet']),
                'type' => Ledger::TYPES[$r['type']] ?? $r['type'],
                'amount' => [money($r['amount']), 'num ' . ((float) $r['amount'] < 0 ? 'is-red' : 'is-green')],
                'from' => $r['from_code'] ? $r['from_code'] . ' · L' . $r['level'] : '—',
                'note' => $r['note'] ?: '—',
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No entries',
        ], 'admin');
    }

    /** Run an admin action; DomainException becomes an error toast. */
    private function act(callable $fn, string $success): void
    {
        try {
            $fn();
            toast($success);
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
    }

    private static function requireNote(string $note): void
    {
        if (mb_strlen($note) < 3 || mb_strlen($note) > 255) {
            throw new DomainException('Write a reason (3-255 characters) so the member sees why.');
        }
    }
}
