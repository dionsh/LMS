<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * The signed-in user.
 *
 * Only the user's id is kept in the session. The account is re-read from the
 * database on every request, so deactivating an account or changing its role
 * takes effect on the very next click — not at the next login.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /** Where each role lands after signing in. */
    private const HOME = [
        'student' => '/nxenesi',
        'teacher' => '/mesimdhenesi',
        'admin'   => '/admin',
    ];

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;
        $id = Session::get(Session::USER_KEY);

        if (!is_int($id)) {
            return null;
        }

        $user = User::findById($id);

        if ($user !== null && $user['status'] === 'active') {
            return self::$user = $user;
        }

        // The account was deactivated or deleted while signed in
        Session::clear();
        Session::flash('warning', $user === null
            ? 'Llogaria juaj nuk ekziston më. Për ndihmë drejtohuni administratës së shkollës.'
            : 'Llogaria juaj është çaktivizuar. Për ndihmë drejtohuni administratës së shkollës.');

        return null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** Sign in: a new session id (prevents session fixation) and a fresh CSRF token. */
    public static function login(array $user): void
    {
        Session::regenerate();
        Csrf::rotate();

        $now = time();
        Session::set(Session::USER_KEY, (int) $user['id']);
        Session::set('_signed_in_at', $now);
        Session::set('_last_activity', $now);

        self::$user = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::clear();
        self::$user = null;
        self::$resolved = true;
    }

    /** Re-read the signed-in user after their account was changed during this request. */
    public static function refresh(): void
    {
        self::$resolved = false;
        self::$user = null;
    }

    /** The dashboard path for a role (default: the signed-in user's). */
    public static function homePath(?string $role = null): string
    {
        return self::HOME[$role ?? self::role() ?? ''] ?? '/';
    }
}
