<?php

declare(strict_types=1);

namespace App;

/**
 * Admin session login (separate session key from members, so both can be open in one browser).
 */
final class AdminAuth
{
    public static function attempt(string $username, string $password): bool
    {
        $admin = Database::one('SELECT * FROM admins WHERE username = ?', [$username]);
        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        return true;
    }

    /** @return array<string, mixed>|null */
    public static function admin(): ?array
    {
        static $cache = [];
        $id = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
        if ($id === null) {
            return null;
        }
        return $cache[$id] ??= Database::one('SELECT id, username, name FROM admins WHERE id = ?', [$id]);
    }

    public static function check(): bool
    {
        return self::admin() !== null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id']);
        session_regenerate_id(true);
    }
}
