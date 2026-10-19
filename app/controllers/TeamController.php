<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\LuckyDraw;
use App\Models\Setting;
use App\Models\User;
use App\Services\Salary;

/**
 * Real referral tree pages, salary achievers and lucky winners.
 */
final class TeamController
{
    private const MEMBER_COLUMNS = [
        'code' => 'User ID',
        'name' => 'Name',
        'level' => 'Level',
        'sponsor' => 'Sponsor',
        'joined' => 'Joined',
        'invested' => 'Invested',
        'status' => 'Status',
    ];

    public function direct(): void
    {
        $pager = User::paginateDownline((int) Auth::id(), 1, page_param());
        view('list', [
            'title' => 'Direct Team',
            'heading' => 'Direct Team',
            'subtitle' => 'Members you sponsored directly.',
            'icon' => 'users',
            'columns' => array_diff_key(self::MEMBER_COLUMNS, ['level' => true, 'sponsor' => true]),
            'rows' => $this->memberRows($pager['rows']),
            'pager' => $pager,
            'empty' => 'No direct members yet. Share your referral link.',
        ]);
    }

    public function level(): void
    {
        $level = (int) ($_GET['level'] ?? 0);
        $level = $level >= 1 && $level <= 10 ? $level : null;
        $pager = User::paginateDownline((int) Auth::id(), $level, page_param());

        $tabs = [['label' => 'All', 'href' => url('/level-team'), 'active' => $level === null]];
        for ($n = 1; $n <= 10; $n++) {
            $tabs[] = ['label' => 'L' . $n, 'href' => url('/level-team?level=' . $n), 'active' => $level === $n];
        }

        view('list', [
            'title' => 'Level Team',
            'heading' => 'Level Team',
            'subtitle' => 'Your downline over 10 levels.',
            'icon' => 'network',
            'tabs' => $tabs,
            'columns' => self::MEMBER_COLUMNS,
            'rows' => $this->memberRows($pager['rows']),
            'pager' => $pager,
            'empty' => 'No members on this level',
            'tree' => $level === null ? $this->tree((int) Auth::id()) : null,
            'scripts' => $level === null ? ['js/team-tree.js'] : [],
        ]);
    }

    /**
     * Data for the visual tree: the viewer + 10 levels. Levels 11-12 are loaded only so the
     * directs / level-2 counts of members on levels 9-10 are complete.
     *
     * @return array{need: array<string, mixed>, nodes: list<array<string, mixed>>}|null
     */
    private function tree(int $userId): ?array
    {
        $levelOf = [$userId => 0];
        foreach (User::downlineLevels($userId, 12) as $n => $ids) {
            $levelOf += array_fill_keys($ids, $n);
        }
        if (count($levelOf) === 1) {
            return null;
        }
        $ids = array_keys($levelOf);
        $rows = Database::all(
            "SELECT u.id, u.sponsor_id, u.user_code, u.name, u.created_at, u.activated_at,
                    u.salary_qualified_at, u.salary_months_paid, s.user_code AS sponsor_code,
                    (SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_id = u.id) AS invested,
                    (SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_id = u.id AND status = 'Active') AS running
             FROM users u LEFT JOIN users s ON s.id = u.sponsor_id
             WHERE u.id IN (" . Database::placeholders($ids) . ') ORDER BY u.id',
            $ids
        );
        $children = [];
        $active = [];
        foreach ($rows as $r) {
            $children[(int) $r['sponsor_id']][] = (int) $r['id'];
            $active[(int) $r['id']] = $r['activated_at'] !== null;
        }
        $activeIn = static fn (array $list): int => count(array_filter($list, static fn (int $id): bool => $active[$id]));

        $nodes = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $level = $levelOf[$id];
            if ($level > 10) {
                continue;
            }
            $kids = $children[$id] ?? [];
            $grandkids = array_merge([], ...array_map(static fn (int $k): array => $children[$k] ?? [], $kids));
            $deadline = Salary::deadline($r['created_at']);
            $nodes[] = [
                'id' => $id,
                'sponsor' => $level === 0 ? null : (int) $r['sponsor_id'],
                'sponsorCode' => $r['sponsor_code'] ?? '—',
                'code' => $r['user_code'],
                'name' => $r['name'],
                'level' => $level,
                'joined' => fmt_date($r['created_at']),
                'active' => $active[$id],
                'invested' => money($r['invested']),
                'running' => money($r['running']),
                'directs' => count($kids),
                'directsActive' => $activeIn($kids),
                'level2Active' => $activeIn($grandkids),
                'selfOk' => (float) $r['invested'] >= Setting::num('salary_min_self'),
                'salary' => $r['salary_qualified_at'] ? fmt_date($r['salary_qualified_at']) : null,
                'monthsPaid' => (int) $r['salary_months_paid'],
                'deadline' => fmt_date($deadline),
                'windowOpen' => now() <= $deadline,
            ];
        }
        return [
            'need' => [
                'self' => money(Setting::num('salary_min_self')),
                'directs' => (int) Setting::num('salary_directs'),
                'level2' => (int) Setting::num('salary_level2'),
                'months' => (int) Setting::num('salary_months'),
            ],
            'nodes' => $nodes,
        ];
    }

    public function business(): void
    {
        $rows = [];
        $totals = ['members' => 0, 'active' => 0, 'business' => 0.0];
        foreach (User::downlineLevels((int) Auth::id()) as $level => $ids) {
            $business = (float) Database::value(
                'SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_id IN (' . Database::placeholders($ids) . ')',
                $ids
            );
            $active = User::activeCount($ids);
            $totals['members'] += count($ids);
            $totals['active'] += $active;
            $totals['business'] += $business;
            $rows[] = [
                'level' => 'Level ' . $level,
                'members' => (string) count($ids),
                'active' => [(string) $active, 'is-green'],
                'business' => [money($business), 'num is-gold'],
            ];
        }

        view('list', [
            'title' => 'Team Business',
            'heading' => 'Team Business',
            'subtitle' => 'Total invested by your downline, per level.',
            'icon' => 'chart',
            'summary' => [
                ['label' => 'Team Members', 'value' => (string) $totals['members'], 'tone' => 'cyan'],
                ['label' => 'Active IDs', 'value' => (string) $totals['active'], 'tone' => 'green'],
                ['label' => 'Total Business', 'value' => money($totals['business']), 'tone' => 'gold'],
            ],
            'columns' => ['level' => 'Level', 'members' => 'Members', 'active' => 'Active', 'business' => 'Business'],
            'rows' => $rows,
            'empty' => 'No team business yet',
        ]);
    }

    public function salaryAchievers(): void
    {
        $pager = Salary::paginateAchievers(page_param());
        view('list', [
            'title' => 'Salary Achievers',
            'heading' => 'Salary Achievers',
            'subtitle' => 'Members who qualified for the monthly salary.',
            'icon' => 'trophy',
            'columns' => ['code' => 'User ID', 'name' => 'Name', 'date' => 'Qualified', 'paid' => 'Months Paid'],
            'rows' => array_map(static fn ($r) => [
                'code' => [$r['user_code'], 'is-gold'],
                'name' => $r['name'],
                'date' => fmt_date($r['salary_qualified_at']),
                'paid' => [$r['salary_months_paid'] . ' / ' . (int) Setting::num('salary_months'), 'is-green'],
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No salary achievers yet',
        ]);
    }

    public function luckyWinners(): void
    {
        $pager = LuckyDraw::paginateWinners(page_param());
        view('list', [
            'title' => 'Lucky Winners',
            'heading' => 'Lucky Winners',
            'subtitle' => 'Winners of distributed lucky draws.',
            'icon' => 'gift',
            'columns' => ['draw' => 'Draw', 'code' => 'User ID', 'name' => 'Name', 'prize' => 'Prize', 'date' => 'Date'],
            'rows' => array_map(static fn ($r) => [
                'draw' => [$r['title'], 'is-gold'],
                'code' => $r['user_code'],
                'name' => $r['name'],
                'prize' => [money($r['prize']), 'num is-green'],
                'date' => fmt_date($r['distributed_at']),
            ], $pager['rows']),
            'pager' => $pager,
            'empty' => 'No lucky winners yet',
        ]);
    }

    /** @param list<array<string, mixed>> $members */
    private function memberRows(array $members): array
    {
        return array_map(static fn ($m) => [
            'code' => $m['user_code'],
            'name' => $m['name'],
            'level' => 'L' . $m['level'],
            'sponsor' => $m['sponsor_code'] ?? '—',
            'joined' => fmt_date($m['created_at']),
            'invested' => [money($m['invested']), 'num'],
            'status' => $m['activated_at'] ? ['Active', 'is-green'] : ['Inactive', 'is-red'],
        ], $members);
    }
}
