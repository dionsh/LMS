<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Rules for a password a person chooses for themselves.
 * Returns errors keyed by form field: 'password' and 'password_confirmation'.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;
    public const MAX_BYTES = 72;   // bcrypt ignores anything longer

    /** Obvious choices that are refused even though they are long enough. */
    private const TOO_COMMON = [
        '12345678', '123456789', '1234567890', '87654321', '11111111', '00000000',
        'password', 'password1', 'qwertyui', 'qwertyuiop', 'asdfghjk', 'abcdefgh', 'abc12345',
        'iloveyou', 'admin123', 'fjalekalimi', 'fjalëkalimi', 'kosova123', 'kosovo123',
        'prishtina', 'shqiperia', 'shqipëria', 'gjimnazi', 'kuvendi', 'kuvendiiarberit',
        'kuvendiiarbërit', 'nxenesi', 'nxënësi', 'mesuesi', 'mësuesi',
    ];

    /**
     * @param array{username: string, email: ?string} $user
     * @param string|null $currentPassword the password being replaced, when known
     * @return array<string, string>
     */
    public static function validate(string $password, string $confirmation, array $user, ?string $currentPassword = null): array
    {
        $errors = [];
        $lower = mb_strtolower($password);

        if ($password === '') {
            $errors['password'] = 'Shkruani fjalëkalimin e ri.';
        } elseif (mb_strlen($password) < self::MIN_LENGTH) {
            $errors['password'] = 'Fjalëkalimi duhet të ketë të paktën ' . self::MIN_LENGTH . ' karaktere.';
        } elseif (strlen($password) > self::MAX_BYTES) {
            $errors['password'] = 'Fjalëkalimi është shumë i gjatë.';
        } elseif (
            $lower === mb_strtolower($user['username'])
            || (($user['email'] ?? null) !== null && $lower === mb_strtolower((string) $user['email']))
        ) {
            $errors['password'] = 'Fjalëkalimi nuk mund të jetë i njëjtë me emrin e përdoruesit ose me email-in.';
        } elseif (in_array($lower, self::TOO_COMMON, true) || count(array_unique(mb_str_split($lower))) < 4) {
            $errors['password'] = 'Ky fjalëkalim është shumë i thjeshtë. Zgjidhni një që nuk mund të merret me mend lehtë.';
        } elseif ($currentPassword !== null && $password === $currentPassword) {
            $errors['password'] = 'Fjalëkalimi i ri duhet të jetë i ndryshëm nga ai aktual.';
        }

        if (!isset($errors['password']) && $password !== $confirmation) {
            $errors['password_confirmation'] = 'Fjalëkalimet nuk përputhen. Shkruajeni të njëjtin fjalëkalim dy herë.';
        }

        return $errors;
    }
}
