<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hardened PHP session + flash data.
 *
 * - strict mode, cookies only, HttpOnly, SameSite=Lax, Secure on HTTPS;
 * - the cookie is scoped to the app's path (other apps in htdocs never see it);
 * - signed-in sessions expire after inactivity and after an absolute lifetime;
 * - flash data set during one request is available during the next one only.
 */
final class Session
{
    /** Session key holding the signed-in user's id (used by Auth in T04). */
    public const USER_KEY = 'user_id';

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) Config::get('session.absolute_timeout', 43200));

        session_cache_limiter('');   // Response decides cache headers
        session_name((string) Config::get('session.name', 'kai_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => Config::basePath() . '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        self::ageFlashData();
        self::enforceTimeouts();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Read a value and remove it. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    /**
     * Empty the session and move it to a new id (the old one is deleted).
     * Used on logout and when a signed-in account is no longer valid.
     */
    public static function clear(): void
    {
        $_SESSION = [];
        self::regenerate();
    }

    /** New session id, same data — call on login, logout and privilege changes. */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
        session_destroy();
    }

    /** Store a value for the NEXT request (e.g. a success message before a redirect). */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_next'][$key] = $value;
    }

    /** Read a value flashed by the PREVIOUS request. */
    public static function flashed(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_now'][$key] ?? $default;
    }

    /** Keep submitted form values for one request so the form can be refilled. Never pass passwords. */
    public static function flashInput(array $input): void
    {
        self::flash('_old_input', $input);
    }

    public static function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['_flash_now']['_old_input'][$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private static function ageFlashData(): void
    {
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    /** Timeouts apply to signed-in users only; a guest's session just carries a CSRF token. */
    private static function enforceTimeouts(): void
    {
        $now = time();

        if (isset($_SESSION[self::USER_KEY])) {
            $idle = (int) Config::get('session.idle_timeout', 3600);
            $absolute = (int) Config::get('session.absolute_timeout', 43200);
            $lastActivity = (int) ($_SESSION['_last_activity'] ?? $now);
            $signedInAt = (int) ($_SESSION['_signed_in_at'] ?? $now);

            if ($now - $lastActivity > $idle || $now - $signedInAt > $absolute) {
                $_SESSION = [];
                session_regenerate_id(true);
                self::flash('session_expired', true);
            }
        }

        $_SESSION['_last_activity'] = $now;
    }
}
