<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Temporary passwords printed on credential slips, e.g. "mali-libri-deti-47".
 *
 * Easy to read aloud and type (short Albanian words without ë/ç), yet not
 * guessable: 50³ × 90 ≈ 11 million combinations, plus sign-in throttling, and the person
 * must replace it at first login.
 */
final class TemporaryPassword
{
    private const WORDS = [
        'arra', 'bari', 'bleta', 'bora', 'buka', 'deti', 'dita', 'dora', 'drita', 'era',
        'flaka', 'fleta', 'fusha', 'gjethi', 'guri', 'hena', 'kali', 'kodra', 'kupa', 'lepuri',
        'libri', 'lisi', 'lule', 'lumi', 'mali', 'molla', 'nata', 'ora', 'pema', 'peshku',
        'pylli', 'qeni', 'qielli', 'rera', 'reja', 'rruga', 'shiu', 'toka', 'ura', 'vera',
        'ylli', 'zogu', 'ari', 'gota', 'hapi', 'kanali', 'kripa', 'letra', 'mjalti', 'porta',
    ];

    public static function generate(): string
    {
        $words = [];
        for ($i = 0; $i < 3; $i++) {
            $words[] = self::WORDS[random_int(0, count(self::WORDS) - 1)];
        }

        return implode('-', $words) . '-' . random_int(10, 99);
    }
}
