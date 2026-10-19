<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\User;
use App\Services\Salary;
use DomainException;

/**
 * Members: search, detail, block, manual wallet adjustment; all investments.
 */
final class UserController
{
    public function index(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $pager = User::search($query, page_param());
        view('list', [
            'title' => 'Users',
            'heading' => 'Users',
            'subtitle' => $pager['total'] . ' members' . ($query !== '' ? ' matching "' . $query . '"' : '') . '.',
            'icon' => 'users',
            'search' => $query,
            'searchPlaceholder' => 'User ID, name or email',
            'columns' => [
                'code' => 'User ID', 'name' => 'Name', 'sponsor' => 'Sponsor', 'joined' => 'Joined',
                'invested' => 'Invested', 'wallets' => 'Dep / ROI / Income', 'status' => 'Status',
            ],
            'rows' => array_map(static fn ($u) => [
                'code' => [$u['user_code'], 'is-gold', url('/admin/user?id=' . $u['id'])],
                'name' => $u['name'],
                'sponsor' => $u['sponsor_code'] ?? '—',
                'joined' => fmt_date($u['created_at']),
                'invested' => [money($u['invested']), 'num'],
                'wallets' => [money($u['deposit_balance']) . ' / ' . money($u['roi_balance']) . ' / ' . money($u['income_balance']), 'num'],
                'status' => match (true) {
                    (int) $u['is_blocked'] === 1 => ['Blocked', 'is-red'],
                    $u['activated_at'] !== null => ['Active', 'is-green'],
                    default => ['Inactive', 'is-gold'],
                },
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No members found',
        ], 'admin');
    }

    public function show(): void
    {
        $user = User::find((int) ($_GET['id'] ?? 0));
        if ($user === null) {
            http_response_code(404);
            view('404', ['title' => 'Not Found'], 'admin');
            return;
        }
        $id = (int) $user['id'];
        $team = [];
        foreach (User::downlineLevels($id) as $level => $ids) {
            $team[] = ['level' => $level, 'members' => count($ids), 'active' => User::activeCount($ids)];
        }
        view('admin/user', [
            'title' => $user['user_code'],
            'user' => $user,
            'salary' => Salary::progress($user),
            'summary' => Investment::summary($id),
            'uplines' => User::uplines($id),
            'team' => $team,
            'investments' => Database::all('SELECT * FROM investments WHERE user_id = ? ORDER BY started_at DESC LIMIT 20', [$id]),
            'ledger' => Ledger::paginateAll(null, $id, page_param()),
        ], 'admin');
    }

    public function toggleBlock(): void
    {
        $user = User::find((int) input('id'));
        if ($user !== null) {
            $block = (int) $user['is_blocked'] === 0;
            User::setBlocked((int) $user['id'], $block);
            toast($user['user_code'] . ($block ? ' blocked. They are logged out and earn no network income.' : ' unblocked.'));
        }
        redirect('/admin/user?id=' . (int) input('id'));
    }

    /** Manual credit / debit, always written to the ledger as "Admin adjustment". */
    public function adjust(): void
    {
        $id = (int) input('id');
        $wallet = input('wallet');
        $raw = input('amount');
        $note = input('note');
        try {
            if (!in_array($wallet, ['deposit', 'roi', 'income'], true)) {
                throw new DomainException('Choose a wallet.');
            }
            if (!preg_match('/^-?\d{1,7}(\.\d{1,2})?$/', $raw) || (float) $raw == 0.0) {
                throw new DomainException('Amount like 25 or -10.50 (not zero).');
            }
            if (mb_strlen($note) < 3) {
                throw new DomainException('Write a reason (3+ characters).');
            }
            Ledger::post($id, $wallet, 'adjust', (float) $raw, ['note' => mb_substr('Admin: ' . $note, 0, 160)]);
            toast('Adjusted ' . $wallet . ' wallet by ' . money($raw) . '.');
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
        redirect('/admin/user?id=' . $id);
    }

    public function investments(): void
    {
        $tabs = ['Active' => 'Active', 'Matured' => 'Matured', 'all' => 'All'];
        $status = query_choice('status', array_keys($tabs), 'Active');
        $pager = Investment::paginateAll($status === 'all' ? null : $status, null, page_param());
        view('list', [
            'title' => 'Investments',
            'heading' => 'Investments',
            'subtitle' => 'Every plan on the platform.',
            'icon' => 'box',
            'tabs' => filter_tabs('/admin/investments', $tabs, $status),
            'columns' => [
                'plan' => 'Plan', 'user' => 'User', 'amount' => 'Invested', 'principal' => 'Principal',
                'extra' => 'Pending / Locked', 'day' => 'Day', 'started' => 'Started', 'status' => 'Status',
            ],
            'rows' => array_map(static fn ($i) => [
                'plan' => [Investment::label($i), 'is-gold'],
                'user' => [$i['user_code'], '', url('/admin/user?id=' . $i['user_id'])],
                'amount' => [money($i['amount']), 'num'],
                'principal' => [money($i['principal']), 'num'],
                'extra' => [money($i['plan'] === 'compound' ? $i['pending'] : $i['accrued']), 'num'],
                'day' => Investment::progressLabel($i),
                'started' => fmt_date($i['started_at']),
                'status' => [$i['status'], status_class($i['status'])],
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No investments',
        ], 'admin');
    }
}
