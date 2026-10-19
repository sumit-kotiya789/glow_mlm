<?php

declare(strict_types=1);

namespace App;

/**
 * Per-session CSRF token, checked on every POST by the router.
 */
final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): bool
    {
        $sent = $_POST['_csrf'] ?? null;
        return is_string($sent) && hash_equals(self::token(), $sent);
    }
}
