<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use DateTimeImmutable;

/**
 * Checks sign-in credentials, with protection against password guessing.
 *
 * - Per account: 5 failures within 15 minutes lock that account for the rest of the window.
 * - Per IP address: 100 failures within 15 minutes. Deliberately generous — a whole
 *   class signs in through the school's single Wi-Fi address, and a few typos from
 *   thirty students must never lock everyone out.
 * - Unknown accounts take as long to reject as wrong passwords (a dummy hash is
 *   verified), so response times do not reveal which usernames exist.
 */
final class Authenticator
{
    public const MAX_FAILURES_PER_ACCOUNT = 5;
    public const MAX_FAILURES_PER_IP = 100;
    public const WINDOW_MINUTES = 15;

    /** bcrypt hash of a random value nobody knows; verified when the account does not exist. */
    private const DUMMY_HASH = '$2y$10$lkiMdemFE78VxG7MTxaNIOwOlOcGIk6CKldaJ1P8yLCX8FH8uxelm';

    public static function attempt(string $identifier, string $password, string $ip): LoginResult
    {
        $identifier = mb_strtolower(trim($identifier));
        $now = new DateTimeImmutable();
        $windowStart = $now->modify('-' . self::WINDOW_MINUTES . ' minutes');

        $account = LoginAttempt::failuresFor($identifier, $windowStart);
        $network = LoginAttempt::failuresFromIp($ip, $windowStart);

        if ($account['failures'] >= self::MAX_FAILURES_PER_ACCOUNT) {
            return LoginResult::locked(self::minutesUntilFree($account['oldest'], $now));
        }

        if ($network['failures'] >= self::MAX_FAILURES_PER_IP) {
            return LoginResult::locked(self::minutesUntilFree($network['oldest'], $now));
        }

        $user = User::findForLogin($identifier);
        $passwordMatches = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);

        if ($user === null || !$passwordMatches) {
            LoginAttempt::record($identifier, $ip, false);
            return LoginResult::invalid();
        }

        LoginAttempt::record($identifier, $ip, true);

        if ($user['status'] !== 'active') {
            return LoginResult::inactive();
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::rehashPassword((int) $user['id'], $password);
        }

        User::touchLastLogin((int) $user['id']);

        // Now and then, forget attempts older than a month
        if (random_int(1, 50) === 1) {
            LoginAttempt::prune($now->modify('-30 days'));
        }

        return LoginResult::success(User::findById((int) $user['id']));
    }

    private static function minutesUntilFree(?string $oldestFailure, DateTimeImmutable $now): int
    {
        if ($oldestFailure === null) {
            return self::WINDOW_MINUTES;
        }

        $freeAt = (new DateTimeImmutable($oldestFailure))->modify('+' . self::WINDOW_MINUTES . ' minutes');

        return max(1, (int) ceil(($freeAt->getTimestamp() - $now->getTimestamp()) / 60));
    }
}
