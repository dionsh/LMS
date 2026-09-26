<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One random token per session. Every form includes it (csrf_field()) and
 * the router rejects any POST whose token does not match.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!isset($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function isValid(mixed $token): bool
    {
        return is_string($token)
            && $token !== ''
            && isset($_SESSION[self::KEY])
            && is_string($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /** Issue a fresh token (on login/logout). */
    public static function rotate(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
