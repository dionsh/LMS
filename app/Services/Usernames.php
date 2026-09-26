<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Usernames generated from a person's name: "Arta Gashi" → "arta.gashi",
 * then "arta.gashi2", "arta.gashi3" … when taken. Lowercase ASCII only.
 */
final class Usernames
{
    private const TRANSLITERATION = [
        'ë' => 'e', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'á' => 'a', 'à' => 'a', 'â' => 'a',
        'ä' => 'a', 'ö' => 'o', 'ó' => 'o', 'ü' => 'u', 'ú' => 'u', 'í' => 'i', 'ı' => 'i', 'ş' => 's',
        'š' => 's', 'ğ' => 'g', 'ć' => 'c', 'č' => 'c', 'đ' => 'dj', 'ž' => 'z', 'ñ' => 'n',
    ];

    public static function suggest(string $firstName, string $lastName): string
    {
        $base = trim(self::slug($firstName) . '.' . self::slug($lastName), '.');
        $base = $base === '' ? 'perdorues' : mb_substr($base, 0, 50);

        $candidate = $base;
        for ($n = 2; User::usernameExists($candidate); $n++) {
            $candidate = $base . $n;
        }

        return $candidate;
    }

    /** "Ëndrit Hoxha-Krasniqi" → "endrit", "hoxhakrasniqi" */
    public static function slug(string $value): string
    {
        $value = strtr(mb_strtolower(trim($value)), self::TRANSLITERATION);

        return (string) preg_replace('/[^a-z0-9]/', '', $value);
    }
}
