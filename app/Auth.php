<?php

declare(strict_types=1);

namespace App;

use App\Models\User;

/**
 * Session-based authentication.
 */
final class Auth
{
    private const REMEMBER_SECONDS = 60 * 60 * 24 * 30;

    public static function attempt(string $userCode, string $password, bool $remember): bool
    {
        $user = User::findByCode($userCode);
        if ($user === null || !password_verify($password, $user['password_hash']) || (int) $user['is_blocked'] === 1) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        if ($remember) {
            // Keep the session cookie for 30 days instead of until browser close.
            setcookie(session_name(), session_id(), [
                'expires' => time() + self::REMEMBER_SECONDS,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return true;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        static $cache = [];
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $cache[$id] ??= User::find($id);
        // A user blocked by the admin is logged out on their next request.
        return $cache[$id] !== null && (int) $cache[$id]['is_blocked'] === 0 ? $cache[$id] : null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        // Keep the session (admin login, flash toast) and only drop the member; new id.
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        setcookie(session_name(), session_id(), ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
}
