<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Support tickets: member opens, admin replies (stored locally, nobody is emailed).
 */
final class Ticket
{
    public static function create(int $userId, string $subject, string $message): int
    {
        return Database::insert('tickets', [
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'status' => 'Open',
            'created_at' => now(),
        ]);
    }

    public static function reply(int $id, string $reply): void
    {
        Database::run(
            "UPDATE tickets SET admin_reply = ?, status = 'Replied', replied_at = ? WHERE id = ?",
            [$reply, now(), $id]
        );
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginateForUser(int $userId, int $page): array
    {
        return Database::paginate(
            'SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC, id DESC',
            [$userId],
            $page
        );
    }

    /** @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int} */
    public static function paginate(?string $status, int $page): array
    {
        $sql = 'SELECT t.*, u.user_code, u.name FROM tickets t JOIN users u ON u.id = t.user_id';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE t.status = ?';
            $params[] = $status;
        }
        return Database::paginate($sql . ' ORDER BY t.created_at DESC, t.id DESC', $params, $page);
    }
}
