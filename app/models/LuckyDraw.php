<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use DomainException;

/**
 * Lucky draws: admin hand-picks winners, system fills the rest at random from users with an
 * active investment, admin distributes the prize. Rules: docs/RULES.md section 8.
 */
final class LuckyDraw
{
    public static function create(string $title, float $prize, int $winnersCount): int
    {
        return Database::insert('lucky_draws', [
            'title' => $title,
            'prize' => round($prize, 2),
            'winners_count' => $winnersCount,
            'status' => 'Draft',
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM lucky_draws WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function winners(int $drawId): array
    {
        return Database::all(
            'SELECT w.*, u.user_code, u.name FROM lucky_draw_winners w JOIN users u ON u.id = w.user_id
             WHERE w.draw_id = ? ORDER BY w.picked_by, u.user_code',
            [$drawId]
        );
    }

    public static function addWinner(int $drawId, int $userId, string $pickedBy = 'admin'): void
    {
        $draw = self::draft($drawId);
        $count = (int) Database::value('SELECT COUNT(*) FROM lucky_draw_winners WHERE draw_id = ?', [$drawId]);
        if ($count >= (int) $draw['winners_count']) {
            throw new DomainException('All winner slots are filled.');
        }
        if (Database::value('SELECT 1 FROM lucky_draw_winners WHERE draw_id = ? AND user_id = ?', [$drawId, $userId]) !== null) {
            throw new DomainException('User is already a winner in this draw.');
        }
        Database::insert('lucky_draw_winners', ['draw_id' => $drawId, 'user_id' => $userId, 'picked_by' => $pickedBy]);
    }

    public static function removeWinner(int $drawId, int $userId): void
    {
        self::draft($drawId);
        Database::run('DELETE FROM lucky_draw_winners WHERE draw_id = ? AND user_id = ?', [$drawId, $userId]);
    }

    /** Fill the remaining slots randomly from active investors not already picked. Returns how many were added. */
    public static function fillRandom(int $drawId): int
    {
        $draw = self::draft($drawId);
        $taken = array_map('intval', Database::column('SELECT user_id FROM lucky_draw_winners WHERE draw_id = ?', [$drawId]));
        $needed = (int) $draw['winners_count'] - count($taken);
        if ($needed <= 0) {
            return 0;
        }
        $eligible = array_map('intval', Database::column(
            "SELECT DISTINCT u.id FROM users u JOIN investments i ON i.user_id = u.id AND i.status = 'Active'
             WHERE u.is_blocked = 0"
        ));
        $pool = array_values(array_diff($eligible, $taken));
        shuffle($pool);
        $picked = array_slice($pool, 0, $needed);
        foreach ($picked as $userId) {
            Database::insert('lucky_draw_winners', ['draw_id' => $drawId, 'user_id' => $userId, 'picked_by' => 'random']);
        }
        return count($picked);
    }

    /** Pay every winner the prize into their income wallet. */
    public static function distribute(int $drawId): int
    {
        return Database::transaction(static function () use ($drawId): int {
            $draw = self::draft($drawId);
            $claimed = Database::run(
                "UPDATE lucky_draws SET status = 'Distributed', distributed_at = ? WHERE id = ? AND status = 'Draft'",
                [now(), $drawId]
            )->rowCount();
            $winners = self::winners($drawId);
            if ($claimed !== 1 || !$winners) {
                throw new DomainException('Add winners before distributing.');
            }
            foreach ($winners as $w) {
                Ledger::post((int) $w['user_id'], 'income', 'lucky', (float) $draw['prize'], [
                    'ref_id' => $drawId,
                    'note' => $draw['title'],
                ]);
            }
            return count($winners);
        });
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginate(int $page): array
    {
        return Database::paginate(
            'SELECT d.*, (SELECT COUNT(*) FROM lucky_draw_winners w WHERE w.draw_id = d.id) AS picked
             FROM lucky_draws d ORDER BY d.created_at DESC, d.id DESC',
            [],
            $page
        );
    }

    /**
     * Winners of distributed draws (member-facing list).
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateWinners(int $page): array
    {
        return Database::paginate(
            "SELECT d.title, d.prize, d.distributed_at, u.user_code, u.name
             FROM lucky_draw_winners w JOIN lucky_draws d ON d.id = w.draw_id JOIN users u ON u.id = w.user_id
             WHERE d.status = 'Distributed' ORDER BY d.distributed_at DESC, u.user_code",
            [],
            $page
        );
    }

    /** @return array<string, mixed> */
    private static function draft(int $drawId): array
    {
        $draw = self::find($drawId);
        if ($draw === null || $draw['status'] !== 'Draft') {
            throw new DomainException('Draw not found or already distributed.');
        }
        return $draw;
    }
}
