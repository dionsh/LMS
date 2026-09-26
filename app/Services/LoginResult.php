<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The outcome of a sign-in attempt (see Authenticator::attempt()).
 */
final class LoginResult
{
    public const SUCCESS = 'success';
    public const INVALID = 'invalid';    // unknown account or wrong password — never said which
    public const LOCKED = 'locked';      // too many recent failures
    public const INACTIVE = 'inactive';  // correct password, but the account is deactivated

    private function __construct(
        public readonly string $outcome,
        public readonly ?array $user = null,
        public readonly int $retryMinutes = 0,
    ) {
    }

    public static function success(array $user): self
    {
        return new self(self::SUCCESS, $user);
    }

    public static function invalid(): self
    {
        return new self(self::INVALID);
    }

    public static function locked(int $retryMinutes): self
    {
        return new self(self::LOCKED, retryMinutes: $retryMinutes);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }
}
