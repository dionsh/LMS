<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    /** Columns safe to keep in memory for the signed-in user (never the password hash). */
    private const PUBLIC_COLUMNS = 'id, role, username, first_name, last_name, email, phone, avatar_path,
                                    status, must_change_password, last_login_at, created_at';

    public static function findById(int $id): ?array
    {
        return self::fetchOne('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?', [$id]);
    }

    /**
     * The account for a login identifier, including its password hash.
     * Usernames never contain "@", so the identifier decides which column is searched.
     */
    public static function findForLogin(string $identifier): ?array
    {
        $column = str_contains($identifier, '@') ? 'email' : 'username';

        return self::fetchOne(
            'SELECT ' . self::PUBLIC_COLUMNS . ', password_hash FROM users WHERE ' . $column . ' = ? LIMIT 1',
            [$identifier]
        );
    }

    public static function passwordHash(int $id): ?string
    {
        $hash = self::fetchValue('SELECT password_hash FROM users WHERE id = ?', [$id]);

        return is_string($hash) ? $hash : null;
    }

    /** Store a new password. $temporary = true means it was issued by the school and must be changed. */
    public static function updatePassword(int $id, string $plainPassword, bool $temporary = false): void
    {
        self::execute(
            'UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_DEFAULT), $temporary, $id]
        );
    }

    /** Re-hash with the current algorithm/cost (after a successful login), keeping everything else. */
    public static function rehashPassword(int $id, string $plainPassword): void
    {
        self::execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }

    /** 'active' | 'inactive'. A deactivated user is signed out on their next request. */
    public static function setStatus(int $id, string $status): void
    {
        self::execute('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function touchLastLogin(int $id): void
    {
        self::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function updateContact(int $id, ?string $email, ?string $phone): void
    {
        self::execute('UPDATE users SET email = ?, phone = ? WHERE id = ?', [$email, $phone, $id]);
    }

    public static function usernameExists(string $username): bool
    {
        return self::fetchValue('SELECT 1 FROM users WHERE username = ?', [$username]) !== null;
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM users WHERE email = ? AND id <> ?',
            [$email, $exceptId ?? 0]
        ) !== null;
    }

    /**
     * @param array{role: string, username: string, first_name: string, last_name: string,
     *              email?: ?string, password: string, status?: string, must_change_password?: bool} $data
     */
    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO users (role, username, first_name, last_name, email, password_hash, status, must_change_password)
             VALUES (:role, :username, :first_name, :last_name, :email, :password_hash, :status, :must_change)',
            [
                'role'          => $data['role'],
                'username'      => $data['username'],
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'email'         => $data['email'] ?? null,
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'status'        => $data['status'] ?? 'active',
                'must_change'   => $data['must_change_password'] ?? true,
            ]
        );
    }

    /** Active accounts per role: ['student' => 0, 'teacher' => 0, 'admin' => 1]. */
    public static function countActiveByRole(): array
    {
        $counts = ['student' => 0, 'teacher' => 0, 'admin' => 0];
        $rows = self::fetchAll("SELECT role, COUNT(*) AS total FROM users WHERE status = 'active' GROUP BY role");

        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }

        return $counts;
    }

    /** Active accounts that have never signed in (their credential slip has not been used yet). */
    public static function countNeverSignedIn(): int
    {
        return (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active' AND last_login_at IS NULL");
    }
}
