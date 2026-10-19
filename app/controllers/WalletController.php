<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Deposit;
use App\Models\Ledger;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Withdrawal;
use DomainException;

/**
 * Deposit requests, withdrawal requests, histories and income history.
 * Demo: nothing touches a blockchain; the admin approves / pays by clicking.
 */
final class WalletController
{
    private const INCOME_TABS = [
        'all' => 'All',
        'direct' => 'Direct',
        'level' => 'Level',
        'roi_on_roi' => 'Level ROI',
        'salary' => 'Salary',
        'lucky' => 'Lucky',
    ];

    /** All three wallet balances + one statement of every credit / debit. */
    public function wallet(): void
    {
        $user = Auth::user();
        $tabs = ['all' => 'All', 'deposit' => 'Deposit', 'roi' => 'ROI', 'income' => 'Income'];
        $wallet = query_choice('wallet', array_keys($tabs), 'all');
        $pager = Ledger::paginateWallet((int) $user['id'], $wallet === 'all' ? null : $wallet, page_param());
        view('list', [
            'title' => 'My Wallet',
            'heading' => 'My Wallet',
            'subtitle' => 'Deposit wallet is for investing. ROI and Income wallets can be withdrawn.',
            'icon' => 'wallet',
            'summary' => [
                ['label' => 'Deposit Wallet', 'value' => money($user['deposit_balance']), 'tone' => 'cyan'],
                ['label' => 'ROI Wallet', 'value' => money($user['roi_balance']), 'tone' => 'green'],
                ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'gold'],
                ['label' => 'Total', 'value' => money($user['deposit_balance'] + $user['roi_balance'] + $user['income_balance']), 'tone' => 'muted'],
            ],
            'tabs' => filter_tabs('/wallet', $tabs, $wallet, 'wallet'),
            'columns' => ['date' => 'Date', 'wallet' => 'Wallet', 'type' => 'Type', 'note' => 'Detail', 'amount' => 'Amount'],
            'rows' => array_map(static fn($r) => [
                'date' => fmt_date($r['created_at'], 'd-M-Y H:i'),
                'wallet' => $tabs[$r['wallet']],
                'type' => Ledger::TYPES[$r['type']] ?? $r['type'],
                'note' => $r['from_code'] && $r['level'] !== null ? $r['from_code'] . ' · L' . $r['level'] : ($r['note'] ?: '—'),
                'amount' => $r['amount'] >= 0
                    ? ['+' . money($r['amount']), 'num is-green']
                    : [money($r['amount']), 'num is-red'],
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No wallet activity yet',
        ]);
    }

    public function deposit(): void
    {
        $userId = (int) Auth::id();
        view('deposit', [
            'title' => 'Deposit',
            'address' => Setting::get('deposit_address'),
            'min' => Setting::num('min_deposit'),
            'recent' => $this->depositRows(Deposit::paginateForUser($userId, 1, 5)['rows']),
            'scripts' => ['js/vendor/qrcode.js'],
        ]);
    }

    public function submitDeposit(): void
    {
        $raw = input('amount');
        $hash = input('tx_hash');
        $min = Setting::num('min_deposit');
        $errors = [];
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw) || (float) $raw < $min) {
            $errors['amount'] = 'Enter at least ' . money($min) . ' (up to 2 decimals).';
        }
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $hash)) {
            $errors['tx_hash'] = 'Transaction hash is 0x followed by 64 hex characters.';
        } elseif (Deposit::hashExists($hash)) {
            $errors['tx_hash'] = 'This transaction hash was already submitted.';
        }
        if ($errors) {
            fail('/deposit', $errors);
        }
        Deposit::submit((int) Auth::id(), (float) $raw, $hash);
        toast('Deposit of ' . money($raw) . ' submitted. It will be credited after admin approval.');
        redirect('/deposit');
    }

    public function depositHistory(): void
    {
        $pager = Deposit::paginateForUser((int) Auth::id(), page_param());
        view('list', [
            'title' => 'Deposit History',
            'heading' => 'Deposit History',
            'subtitle' => 'Your USDT deposit requests.',
            'icon' => 'history',
            'columns' => ['date' => 'Date', 'hash' => 'Tx Hash', 'amount' => 'Amount', 'status' => 'Status', 'note' => 'Note'],
            'rows' => $this->depositRows($pager['rows']),
            'pager' => $pager,
            'empty' => 'No deposits yet',
        ]);
    }

    public function withdrawal(): void
    {
        $user = Auth::user();
        $limit = User::activeInvestment((int) $user['id']);
        $used = Withdrawal::todayTotal((int) $user['id']);
        view('withdrawal', [
            'title' => 'Withdrawal',
            'user' => $user,
            'limit' => $limit,
            'left' => max(0, $limit - $used),
            'min' => Setting::num('min_withdraw'),
            'max' => Setting::num('max_withdraw'),
            'fee' => Setting::num('withdraw_fee_pct'),
            'recent' => $this->withdrawalRows(Withdrawal::paginateForUser((int) $user['id'], 1, 5)['rows']),
        ]);
    }

    public function submitWithdrawal(): void
    {
        $source = input('source');
        $raw = input('amount');
        $errors = [];
        if (!in_array($source, ['roi', 'income'], true)) {
            $errors['source'] = 'Choose a wallet.';
        }
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
            $errors['amount'] = 'Enter a valid amount (up to 2 decimals).';
        }
        if (!$errors) {
            try {
                $id = Withdrawal::request((int) Auth::id(), $source, (float) $raw);
            } catch (DomainException $e) {
                $errors['amount'] = $e->getMessage();
            }
        }
        if ($errors) {
            fail('/withdrawal', $errors);
        }
        $w = Database::one('SELECT fee, net_amount FROM withdrawals WHERE id = ?', [$id]);
        toast('Withdrawal #' . $id . ' requested: ' . money($raw) . ', fee ' . money($w['fee']) . ', you get ' . money($w['net_amount']) . '. Admin will send it manually.');
        redirect('/withdrawal');
    }

    public function withdrawalHistory(): void
    {
        $pager = Withdrawal::paginateForUser((int) Auth::id(), page_param());
        view('list', [
            'title' => 'Withdrawal History',
            'heading' => 'Withdrawal History',
            'subtitle' => 'Requests are paid manually by the admin.',
            'icon' => 'download',
            'columns' => [
                'date' => 'Date',
                'source' => 'From',
                'amount' => 'Amount',
                'fee' => 'Fee',
                'net' => 'You Get',
                'status' => 'Status',
                'note' => 'Note',
            ],
            'rows' => $this->withdrawalRows($pager['rows']),
            'pager' => $pager,
            'empty' => 'No withdrawals yet',
        ]);
    }

    public function transfer(): void
    {
        $user = Auth::user();
        $userId = (int) $user['id'];
        view('transfer', [
            'title' => 'Transfer',
            'user' => $user,
            'min' => Setting::num('min_transfer'),
            'canSend' => Transfer::canSend($userId),
            'sendable' => Transfer::sendable($userId),
            'recent' => $this->transferRows(Transfer::history($userId, 1, 5)['rows']),
            'scripts' => ['js/transfer.js'],
        ]);
    }

    /** P2P: own Deposit wallet -> another member's Deposit wallet. */
    public function submitP2p(): void
    {
        $code = strtoupper(input('user_code'));
        $raw = input('p2p_amount');
        $errors = [];
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
            $errors['p2p_amount'] = 'Enter a valid amount (up to 2 decimals).';
        }
        if (!$errors) {
            try {
                $to = Transfer::p2p((int) Auth::id(), $code, (float) $raw);
            } catch (DomainException $e) {
                $errors[$e->getCode() === 1 ? 'user_code' : 'p2p_amount'] = $e->getMessage();
            }
        }
        if ($errors) {
            fail('/transfer', $errors);
        }
        toast(money($raw) . ' sent to ' . $to['user_code'] . ' (' . Transfer::maskName($to['name']) . ').');
        redirect('/transfer');
    }

    /** Own ROI / Income wallet -> own Deposit wallet. */
    public function submitWalletMove(): void
    {
        $source = input('source');
        $raw = input('move_amount');
        $errors = [];
        if (!isset(Transfer::SOURCES[$source])) {
            $errors['source'] = 'Choose a wallet.';
        }
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
            $errors['move_amount'] = 'Enter a valid amount (up to 2 decimals).';
        }
        if (!$errors) {
            try {
                Transfer::toDeposit((int) Auth::id(), $source, (float) $raw);
            } catch (DomainException $e) {
                $errors['move_amount'] = $e->getMessage();
            }
        }
        if ($errors) {
            fail('/transfer', $errors);
        }
        toast(money($raw) . ' moved from ' . Transfer::SOURCES[$source] . ' to your Deposit wallet.');
        redirect('/transfer');
    }

    /** JSON for the receiver check on the P2P form: masked name of a User ID. */
    public function lookupUser(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        try {
            $to = Transfer::receiver((int) Auth::id(), (string) ($_GET['code'] ?? ''));
            echo json_encode(['ok' => true, 'code' => $to['user_code'], 'name' => Transfer::maskName($to['name'])]);
        } catch (DomainException $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function transferHistory(): void
    {
        $pager = Transfer::history((int) Auth::id(), page_param());
        view('list', [
            'title' => 'Transfer History',
            'heading' => 'Transfer History',
            'subtitle' => 'P2P transfers and moves into your Deposit wallet.',
            'icon' => 'transfer',
            'columns' => ['date' => 'Date', 'type' => 'Type', 'wallet' => 'Wallet', 'note' => 'Detail', 'amount' => 'Amount'],
            'rows' => $this->transferRows($pager['rows']),
            'pager' => $pager,
            'empty' => 'No transfers yet',
        ]);
    }

    public function income(): void
    {
        $user = Auth::user();
        $userId = (int) $user['id'];
        $type = isset(self::INCOME_TABS[$_GET['type'] ?? '']) ? $_GET['type'] : 'all';
        $pager = Ledger::paginate($userId, $type === 'all' ? Ledger::INCOME_TYPES : [$type], page_param());

        $tabs = [];
        foreach (self::INCOME_TABS as $key => $label) {
            $tabs[] = [
                'label' => $label,
                'href' => url('/income' . ($key === 'all' ? '' : '?type=' . $key)),
                'active' => $key === $type,
            ];
        }

        view('list', [
            'title' => 'Income History',
            'heading' => 'Income History',
            'subtitle' => 'Everything credited to your Income wallet.',
            'icon' => 'wallet',
            'summary' => [
                ['label' => 'Income Wallet', 'value' => money($user['income_balance']), 'tone' => 'gold'],
                ['label' => "Today's Income", 'value' => money(Ledger::sum($userId, Ledger::INCOME_TYPES, today() . ' 00:00:00')), 'tone' => 'green'],
                ['label' => 'Total Income', 'value' => money(Ledger::sum($userId, Ledger::INCOME_TYPES)), 'tone' => 'cyan'],
            ],
            'tabs' => $tabs,
            'columns' => ['date' => 'Date', 'type' => 'Type', 'from' => 'From', 'note' => 'Detail', 'amount' => 'Amount'],
            'rows' => array_map(static fn($r) => [
                'date' => fmt_date($r['created_at'], 'd-M-Y H:i'),
                'type' => Ledger::TYPES[$r['type']] ?? $r['type'],
                'from' => $r['from_code'] ? $r['from_code'] . ' · L' . $r['level'] : '—',
                'note' => $r['note'],
                'amount' => ['+' . money($r['amount']), 'num is-green'],
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No income of this type yet',
        ]);
    }

    private function transferRows(array $rows): array
    {
        $wallets = ['deposit' => 'Deposit', 'roi' => 'ROI', 'income' => 'Income'];
        return array_map(static fn($r) => [
            'date' => fmt_date($r['created_at'], 'd-M-Y H:i'),
            'type' => Ledger::TYPES[$r['type']] ?? $r['type'],
            'wallet' => $wallets[$r['wallet']] ?? $r['wallet'],
            'note' => $r['note'] ?: '—',
            'amount' => $r['amount'] >= 0
                ? ['+' . money($r['amount']), 'num is-green']
                : [money($r['amount']), 'num is-red'],
        ], $rows);
    }

    private function depositRows(array $rows): array
    {
        return array_map(static fn($d) => [
            'date' => fmt_date($d['created_at'], 'd-M-Y H:i'),
            'hash' => substr($d['tx_hash'], 0, 10) . '…' . substr($d['tx_hash'], -6),
            'amount' => [money($d['amount']), 'num'],
            'status' => [$d['status'], status_class($d['status'])],
            'note' => $d['admin_note'] ?? '—',
        ], $rows);
    }

    private function withdrawalRows(array $rows): array
    {
        return array_map(static fn($w) => [
            'date' => fmt_date($w['created_at'], 'd-M-Y H:i'),
            'source' => Withdrawal::SOURCES[$w['source']] . ($w['investment_id'] ? ' #' . $w['investment_id'] : ''),
            'amount' => [money($w['amount']), 'num'],
            'fee' => [money($w['fee']), 'num'],
            'net' => [money($w['net_amount']), 'num is-gold'],
            'status' => [$w['status'], status_class($w['status'])],
            'note' => $w['admin_note'] ?? '—',
        ], $rows);
    }
}
