<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use DomainException;

/**
 * USDT deposit requests: user submits amount + tx hash, admin approves or rejects.
 */
final class Deposit
{
    public static function submit(int $userId, float $amount, string $txHash): int
    {
        return Database::insert('deposits', [
            'user_id' => $userId,
            'amount' => round($amount, 2),
            'tx_hash' => $txHash,
            'status' => 'Pending',
            'created_at' => now(),
        ]);
    }

    public static function hashExists(string $txHash): bool
    {
        return Database::value('SELECT 1 FROM deposits WHERE tx_hash = ?', [$txHash]) !== null;
    }

    /** Credit the deposit wallet. */
    public static function approve(int $id, string $note = ''): void
    {
        Database::transaction(static function () use ($id, $note): void {
            $deposit = self::claim($id, 'Approved', $note);
            $userId = (int) $deposit['user_id'];
            Ledger::post($userId, 'deposit', 'deposit', (float) $deposit['amount'], ['ref_id' => $id, 'note' => 'USDT BEP-20 deposit']);
        });
    }

    public static function reject(int $id, string $note): void
    {
        self::claim($id, 'Rejected', $note);
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginateForUser(int $userId, int $page, ?int $perPage = null): array
    {
        return Database::paginate(
            'SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC, id DESC',
            [$userId],
            $page,
            $perPage
        );
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginate(?string $status, int $page): array
    {
        $sql = 'SELECT d.*, u.user_code, u.name FROM deposits d JOIN users u ON u.id = d.user_id';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE d.status = ?';
            $params[] = $status;
        }
        return Database::paginate($sql . ' ORDER BY d.created_at DESC, d.id DESC', $params, $page);
    }

    /**
     * Move Pending -> $status atomically, so a double click can't process it twice.
     *
     * @return array<string, mixed>
     */
    private static function claim(int $id, string $status, string $note): array
    {
        $claimed = Database::run(
            "UPDATE deposits SET status = ?, admin_note = ?, reviewed_at = ? WHERE id = ? AND status = 'Pending'",
            [$status, $note !== '' ? $note : null, now(), $id]
        )->rowCount();
        if ($claimed !== 1) {
            throw new DomainException('Deposit is not pending.');
        }
        return (array) Database::one('SELECT * FROM deposits WHERE id = ?', [$id]);
    }
}
