<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use DomainException;

/**
 * Every balance movement goes through post(): it updates the wallet balance (refusing to go
 * below zero) and writes the history row.
 */
final class Ledger
{
    private const BALANCE_COLUMNS = [
        'deposit' => 'deposit_balance',
        'roi' => 'roi_balance',
        'income' => 'income_balance',
    ];

    public const TYPES = [
        'deposit' => 'Deposit approved',
        'invest' => 'Investment',
        'profit' => 'Daily profit',
        'plan_part' => 'Daily principal part',
        'locked_roi' => 'Locked ROI',
        'maturity' => 'Plan maturity',
        'compound' => 'Compounded',
        'direct' => 'Direct sponsor income',
        'level' => 'Level deposit income',
        'roi_on_roi' => 'Level ROI income',
        'lucky' => 'Lucky draw',
        'salary' => 'Monthly salary',
        'signup_bonus' => 'Signup bonus',
        'withdrawal' => 'Withdrawal',
        'refund' => 'Withdrawal refund',
        'adjust' => 'Admin adjustment',
        'p2p_out' => 'P2P sent',
        'p2p_in' => 'P2P received',
        'wallet_out' => 'Transfer out',
        'wallet_in' => 'Transfer in',
    ];

    /** Income types shown on the income history page. */
    public const INCOME_TYPES = ['direct', 'level', 'roi_on_roi', 'salary', 'lucky'];

    /**
     * @param string $wallet deposit|roi|income, or plan (history only, no wallet balance)
     * @param array{from_user_id?: int, level?: int, ref_id?: int, note?: string} $meta
     * @throws DomainException when a debit would make the balance negative
     */
    public static function post(int $userId, string $wallet, string $type, float $amount, array $meta = []): void
    {
        $amount = round($amount, 2);
        if (abs($amount) < 0.005) {
            return;
        }
        if (isset(self::BALANCE_COLUMNS[$wallet])) {
            $col = self::BALANCE_COLUMNS[$wallet];
            $changed = Database::run(
                "UPDATE users SET $col = ROUND($col + ?, 2) WHERE id = ? AND $col + ? >= 0",
                [$amount, $userId, $amount]
            )->rowCount();
            if ($changed === 0) {
                throw new DomainException('Insufficient ' . $wallet . ' wallet balance.');
            }
        }
        Database::insert('ledger', [
            'user_id' => $userId,
            'wallet' => $wallet,
            'type' => $type,
            'amount' => $amount,
            'from_user_id' => $meta['from_user_id'] ?? null,
            'level' => $meta['level'] ?? null,
            'ref_id' => $meta['ref_id'] ?? null,
            'note' => mb_substr($meta['note'] ?? '', 0, 160),
            'created_at' => now(),
        ]);
    }

    /**
     * @param list<string> $types
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginate(int $userId, array $types, int $page): array
    {
        return Database::paginate(
            'SELECT l.*, f.user_code AS from_code FROM ledger l LEFT JOIN users f ON f.id = l.from_user_id
             WHERE l.user_id = ? AND l.type IN (' . Database::placeholders($types) . ')
             ORDER BY l.created_at DESC, l.id DESC',
            array_merge([$userId], $types),
            $page
        );
    }

    /**
     * Wallet statement: deposit / roi / income movements (all three when $wallet is null).
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateWallet(int $userId, ?string $wallet, int $page): array
    {
        $wallets = $wallet === null ? array_keys(self::BALANCE_COLUMNS) : [$wallet];
        return Database::paginate(
            'SELECT l.*, f.user_code AS from_code FROM ledger l LEFT JOIN users f ON f.id = l.from_user_id
             WHERE l.user_id = ? AND l.wallet IN (' . Database::placeholders($wallets) . ')
             ORDER BY l.id DESC',
            array_merge([$userId], $wallets),
            $page
        );
    }

    /**
     * Admin ledger across all users, optionally one type and/or one user.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateAll(?string $type, ?int $userId, int $page): array
    {
        $where = [];
        $params = [];
        if ($type !== null) {
            $where[] = 'l.type = ?';
            $params[] = $type;
        }
        if ($userId !== null) {
            $where[] = 'l.user_id = ?';
            $params[] = $userId;
        }
        return Database::paginate(
            'SELECT l.*, u.user_code, f.user_code AS from_code FROM ledger l
             JOIN users u ON u.id = l.user_id LEFT JOIN users f ON f.id = l.from_user_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY l.created_at DESC, l.id DESC',
            $params,
            $page
        );
    }

    /** @param list<string> $types */
    public static function sum(int $userId, array $types, ?string $since = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE user_id = ? AND type IN ('
            . Database::placeholders($types) . ')';
        $params = array_merge([$userId], $types);
        if ($since !== null) {
            $sql .= ' AND created_at >= ?';
            $params[] = $since;
        }
        return (float) Database::value($sql, $params);
    }

    /** @return array<string, float> income type => total */
    public static function incomeTotals(int $userId): array
    {
        $rows = Database::all(
            'SELECT type, SUM(amount) AS total FROM ledger WHERE user_id = ? AND type IN ('
            . Database::placeholders(self::INCOME_TYPES) . ') GROUP BY type',
            array_merge([$userId], self::INCOME_TYPES)
        );
        $totals = array_fill_keys(self::INCOME_TYPES, 0.0);
        foreach ($rows as $row) {
            $totals[$row['type']] = (float) $row['total'];
        }
        return $totals;
    }

    /**
     * Platform-wide activity for the live feed.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function feed(int $page): array
    {
        $types = ['deposit', 'invest', 'direct', 'salary', 'lucky', 'maturity', 'withdrawal'];
        return Database::paginate(
            'SELECT l.type, l.amount, l.created_at, u.user_code FROM ledger l JOIN users u ON u.id = l.user_id
             WHERE l.type IN (' . Database::placeholders($types) . ') ORDER BY l.created_at DESC, l.id DESC',
            $types,
            $page
        );
    }
}
