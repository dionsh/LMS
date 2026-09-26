<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeInterface;

/**
 * Every sign-in attempt, used to slow down password guessing.
 */
final class LoginAttempt extends Model
{
    public static function record(string $identifier, string $ip, bool $succeeded): void
    {
        self::run(
            'INSERT INTO login_attempts (identifier, ip_address, succeeded) VALUES (?, ?, ?)',
            [mb_substr($identifier, 0, 190), $ip, $succeeded]
        );
    }

    /**
     * Failed attempts for one identifier since $since, not counting failures
     * before its last successful sign-in (a typo after logging in is not an attack).
     *
     * @return array{failures: int, oldest: ?string}
     */
    public static function failuresFor(string $identifier, DateTimeInterface $since): array
    {
        $row = self::fetchOne(
            'SELECT COUNT(*) AS failures, MIN(attempted_at) AS oldest
               FROM login_attempts
              WHERE identifier = :identifier
                AND succeeded = 0
                AND attempted_at >= :since
                AND attempted_at > COALESCE(
                    (SELECT MAX(s.attempted_at) FROM login_attempts s WHERE s.identifier = :same AND s.succeeded = 1),
                    :epoch)',
            ['identifier' => $identifier, 'same' => $identifier, 'since' => $since, 'epoch' => '1970-01-01 00:00:00']
        );

        return ['failures' => (int) ($row['failures'] ?? 0), 'oldest' => $row['oldest'] ?? null];
    }

    /** @return array{failures: int, oldest: ?string} */
    public static function failuresFromIp(string $ip, DateTimeInterface $since): array
    {
        $row = self::fetchOne(
            'SELECT COUNT(*) AS failures, MIN(attempted_at) AS oldest
               FROM login_attempts
              WHERE ip_address = ? AND succeeded = 0 AND attempted_at >= ?',
            [$ip, $since]
        );

        return ['failures' => (int) ($row['failures'] ?? 0), 'oldest' => $row['oldest'] ?? null];
    }

    /** Lift a lock early (e.g. after an admin issues a new password). */
    public static function clearFor(string $identifier): void
    {
        self::execute('DELETE FROM login_attempts WHERE identifier = ?', [$identifier]);
    }

    /** Remove attempts older than $before (keeps the table small). */
    public static function prune(DateTimeInterface $before): void
    {
        self::execute('DELETE FROM login_attempts WHERE attempted_at < ?', [$before]);
    }
}
