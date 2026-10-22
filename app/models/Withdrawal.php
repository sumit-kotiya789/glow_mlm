<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use DomainException;

/**
 * Withdrawal requests. Admin sends funds manually, then marks Paid or Rejected (refund).
 * Rules: docs/RULES.md section 9.
 */
final class Withdrawal
{
    public const SOURCES = [
        'roi' => Ledger::WALLETS['roi'],
        'fd' => Ledger::WALLETS['fd'],
        'income' => Ledger::WALLETS['income'],
        'plan' => 'Plan principal',
    ];

    /**
     * @param string $source roi|fd|income ('plan' rows are old requests; pending now moves via Investment::release)
     * @throws DomainException with a user-facing message
     */
    public static function request(int $userId, string $source, float $amount): int
    {
        if ($source === 'plan' || !isset(self::SOURCES[$source])) {
            throw new DomainException('Choose a wallet.');
        }
        $min = Setting::num('min_withdraw');
        $max = Setting::num('max_withdraw');
        if ($amount < $min || $amount > $max) {
            throw new DomainException('Amount must be ' . money($min) . ' to ' . money($max) . ' per request.');
        }
        $limit = User::withdrawLimit($userId);
        $used = self::todayTotal($userId);
        if ($used + $amount > $limit + 0.001) {
            throw new DomainException('Daily limit is ' . money($limit) . ' (your investment).) Left today: '
                . money(max(0, $limit - $used)) . '.');
        }
        $user = User::find($userId);
        $fee = round($amount * Setting::num('withdraw_fee_pct') / 100, 2);

        return Database::transaction(static function () use ($user, $userId, $source, $amount, $fee): int {
            Ledger::post($userId, $source, 'withdrawal', -$amount, ['note' => 'Withdrawal request']);
            return Database::insert('withdrawals', [
                'user_id' => $userId,
                'source' => $source,
                'amount' => round($amount, 2),
                'fee' => $fee,
                'net_amount' => round($amount - $fee, 2),
                'wallet' => $user['wallet'],
                'status' => 'Pending',
                'created_at' => now(),
            ]);
        });
    }

    /** Sum of today's non-rejected requests (counts against the daily limit). */
    public static function todayTotal(int $userId): float
    {
        return (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status <> 'Rejected' AND created_at >= ?",
            [$userId, today() . ' 00:00:00']
        );
    }

    public static function markPaid(int $id, string $payoutHash, string $note): void
    {
        self::claim($id, 'Paid', $note, $payoutHash);
    }

    /** Reject and return the amount to where it came from. */
    public static function reject(int $id, string $note): void
    {
        Database::transaction(static function () use ($id, $note): void {
            $w = self::claim($id, 'Rejected', $note, '');
            $userId = (int) $w['user_id'];
            $amount = (float) $w['amount'];
            $meta = ['ref_id' => $id, 'note' => 'Withdrawal #' . $id . ' rejected'];

            if ($w['source'] !== 'plan') {
                Ledger::post($userId, $w['source'], 'refund', $amount, $meta);
                return;
            }
            $inv = Database::one('SELECT * FROM investments WHERE id = ?', [$w['investment_id']]);
            if ($inv !== null && $inv['status'] === 'Active') {
                // ponytail: principal was floored at 0 on withdraw; refund may restore slightly more in that edge case.
                Database::run(
                    'UPDATE investments SET pending = ?, principal = ? WHERE id = ?',
                    [round($inv['pending'] + $amount, 2), round($inv['principal'] + $amount, 2), $inv['id']]
                );
                Ledger::post($userId, 'plan', 'refund', $amount, $meta);
            } else {
                // Plan already matured: give it back through the Compound ROI wallet.
                Ledger::post($userId, 'roi', 'refund', $amount, $meta);
            }
        });
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginateForUser(int $userId, int $page, ?int $perPage = null): array
    {
        return Database::paginate(
            'SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC, id DESC',
            [$userId],
            $page,
            $perPage
        );
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginate(?string $status, int $page): array
    {
        $sql = 'SELECT w.*, u.user_code, u.name FROM withdrawals w JOIN users u ON u.id = w.user_id';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE w.status = ?';
            $params[] = $status;
        }
        return Database::paginate($sql . ' ORDER BY w.created_at DESC, w.id DESC', $params, $page);
    }

    /**
     * Move Pending -> $status atomically.
     *
     * @return array<string, mixed>
     */
    private static function claim(int $id, string $status, string $note, string $payoutHash): array
    {
        $claimed = Database::run(
            "UPDATE withdrawals SET status = ?, admin_note = ?, payout_hash = ?, processed_at = ?
             WHERE id = ? AND status = 'Pending'",
            [$status, $note !== '' ? $note : null, $payoutHash !== '' ? $payoutHash : null, now(), $id]
        )->rowCount();
        if ($claimed !== 1) {
            throw new DomainException('Withdrawal is not pending.');
        }
        return (array) Database::one('SELECT * FROM withdrawals WHERE id = ?', [$id]);
    }
}
