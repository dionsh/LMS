<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Lessons per week as typed in a form: empty (not decided / as in the
 * curriculum) or a whole number from 1 to 12 — the same range the database allows.
 */
final class WeeklyHours
{
    public const MESSAGE = 'Shkruani orët në javë si numër nga 1 deri në 12, ose lëreni bosh.';

    public static function valid(string $value): bool
    {
        return $value === '' || (ctype_digit($value) && (int) $value >= 1 && (int) $value <= 12);
    }

    public static function parse(string $value): ?int
    {
        return $value === '' ? null : (int) $value;
    }
}
