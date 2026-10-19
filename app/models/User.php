<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Members and the referral tree (users.sponsor_id).
 */
final class User
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT u.*, s.user_code AS sponsor_code FROM users u LEFT JOIN users s ON s.id = u.sponsor_id WHERE u.id = ?',
            [$id]
        );
    }

    /** @return array<string, mixed>|null */
    public static function findByCode(string $code): ?array
    {
        return Database::one('SELECT * FROM users WHERE user_code = ?', [strtoupper($code)]);
    }

    /**
     * Pay the signup bonus into the Deposit wallet. Called at registration only: once per user,
     * and only while the admin switch is ON at that moment. Returns true when the bonus was paid.
     */
    public static function grantSignupBonus(int $userId): bool
    {
        $amount = Setting::num('signup_bonus_amount');
        if (Setting::get('signup_bonus_enabled') !== '1' || $amount <= 0) {
            return false;
        }
        return (bool) Database::transaction(static function () use ($userId, $amount): bool {
            $paid = Database::run('UPDATE users SET signup_bonus_paid = 1 WHERE id = ? AND signup_bonus_paid = 0', [$userId])->rowCount() === 1;
            if ($paid) {
                Ledger::post($userId, 'deposit', 'signup_bonus', $amount, ['note' => 'Registration bonus']);
            }
            return $paid;
        });
    }


    /** Create a member under $sponsorId and return the generated user code. */
    public static function create(array $data, ?int $sponsorId): string
    {
        do {
            $code = sprintf('GL%07d', random_int(1000000, 9999999));
        } while (self::findByCode($code) !== null);

        Database::insert('users', [
            'user_code' => $data['user_code'] ?? $code,
            'sponsor_id' => $sponsorId,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'],
            'country' => $data['country'],
            'wallet' => $data['wallet'],
            'password_hash' => $data['password_hash'] ?? password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => now(),
        ]);
        return $data['user_code'] ?? $code;
    }

    /**
     * Sponsors above a user: [1 => direct sponsor, 2 => their sponsor, ...].
     *
     * @return array<int, array<string, mixed>>
     */
    public static function uplines(int $userId, int $depth = 10): array
    {
        $uplines = [];
        $sponsorId = Database::value('SELECT sponsor_id FROM users WHERE id = ?', [$userId]);
        for ($level = 1; $level <= $depth && $sponsorId !== null; $level++) {
            $sponsor = Database::one('SELECT id, user_code, sponsor_id, is_blocked FROM users WHERE id = ?', [$sponsorId]);
            if ($sponsor === null) {
                break;
            }
            $uplines[$level] = $sponsor;
            $sponsorId = $sponsor['sponsor_id'];
        }
        return $uplines;
    }

    /**
     * Downline ids per level: [1 => [ids of directs], 2 => [...], ...].
     *
     * @return array<int, list<int>>
     */
    public static function downlineLevels(int $userId, int $depth = 10): array
    {
        $levels = [];
        $ids = [$userId];
        for ($level = 1; $level <= $depth; $level++) {
            $ids = array_map('intval', Database::column(
                'SELECT id FROM users WHERE sponsor_id IN (' . Database::placeholders($ids) . ')',
                $ids
            ));
            if (!$ids) {
                break;
            }
            $levels[$level] = $ids;
        }
        return $levels;
    }

    /**
     * Downline members (optionally one level) with level, sponsor and invested amount.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function paginateDownline(int $userId, ?int $level, int $page): array
    {
        $levels = self::downlineLevels($userId);
        if ($level !== null) {
            $levels = isset($levels[$level]) ? [$level => $levels[$level]] : [];
        }
        $levelOf = [];
        foreach ($levels as $n => $ids) {
            foreach ($ids as $id) {
                $levelOf[$id] = $n;
            }
        }
        if (!$levelOf) {
            return ['rows' => [], 'page' => 1, 'pages' => 1, 'total' => 0];
        }
        $ids = array_keys($levelOf);
        $pager = Database::paginate(
            'SELECT u.id, u.user_code, u.name, u.country, u.created_at, u.activated_at, s.user_code AS sponsor_code,
                    (SELECT COALESCE(SUM(i.amount), 0) FROM investments i WHERE i.user_id = u.id) AS invested
             FROM users u LEFT JOIN users s ON s.id = u.sponsor_id
             WHERE u.id IN (' . Database::placeholders($ids) . ') ORDER BY u.created_at DESC, u.id DESC',
            $ids,
            $page
        );
        foreach ($pager['rows'] as $i => $row) {
            $pager['rows'][$i]['level'] = $levelOf[(int) $row['id']];
        }
        return $pager;
    }

    /**
     * Admin user list, optionally filtered by code / name / email.
     *
     * @return array{rows: list<array<string, mixed>>, page: int, pages: int, total: int}
     */
    public static function search(string $query, int $page): array
    {
        $sql = 'SELECT u.*, s.user_code AS sponsor_code,
                       (SELECT COALESCE(SUM(i.amount), 0) FROM investments i WHERE i.user_id = u.id) AS invested
                FROM users u LEFT JOIN users s ON s.id = u.sponsor_id';
        $params = [];
        if ($query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $sql .= ' WHERE u.user_code LIKE ? OR u.name LIKE ? OR u.email LIKE ?';
            $params = [$like, $like, $like];
        }
        return Database::paginate($sql . ' ORDER BY u.id DESC', $params, $page);
    }

    public static function setBlocked(int $userId, bool $blocked): void
    {
        Database::run('UPDATE users SET is_blocked = ? WHERE id = ?', [$blocked ? 1 : 0, $userId]);
    }

    /** Number of users in $ids whose ID is activated (made at least one investment). */
    public static function activeCount(array $ids): int
    {
        if (!$ids) {
            return 0;
        }
        return (int) Database::value(
            'SELECT COUNT(*) FROM users WHERE activated_at IS NOT NULL AND id IN (' . Database::placeholders($ids) . ')',
            $ids
        );
    }

    /** Lifetime amount invested. */
    public static function totalInvested(int $userId): float
    {
        return (float) Database::value('SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_id = ?', [$userId]);
    }

    /** Minimum investment: higher for users who actually received the signup bonus. */
    public static function minInvest(int $userId): float
    {
        $min = Setting::num('min_invest');
        $gotBonus = (int) Database::value('SELECT signup_bonus_paid FROM users WHERE id = ?', [$userId]) === 1;
        return $gotBonus ? max($min, Setting::num('min_invest_bonus')) : $min;
    }

    /** Amount currently invested in running plans (the daily withdrawal limit). */
    public static function activeInvestment(int $userId): float
    {
        return (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_id = ? AND status = 'Active'",
            [$userId]
        );
    }
}
