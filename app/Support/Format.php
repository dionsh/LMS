<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Albanian formatting of dates, times and numbers.
 * (The intl extension is not available on every host, so this is done by hand.)
 */
final class Format
{
    /**
     * Styles:
     *   'date'      2 tetor 2026
     *   'long'      e premte, 2 tetor 2026
     *   'datetime'  2 tetor 2026, 08:50
     *   'day_month' 2 tetor
     *   'short'     02.10.2026
     *   'time'      08:50
     *   'weekday'   e premte
     */
    public static function date(DateTimeInterface|string|null $value, string $style = 'date'): string
    {
        $date = self::toDate($value);

        if ($date === null) {
            return '';
        }

        $day = (int) $date->format('j');
        $month = Labels::month((int) $date->format('n'));
        $year = $date->format('Y');
        $weekday = Labels::day((int) $date->format('N'));

        return match ($style) {
            'long'      => "{$weekday}, {$day} {$month} {$year}",
            'datetime'  => "{$day} {$month} {$year}, " . $date->format('H:i'),
            'day_month' => "{$day} {$month}",
            'short'     => $date->format('d.m.Y'),
            'time'      => $date->format('H:i'),
            'weekday'   => $weekday,
            default     => "{$day} {$month} {$year}",
        };
    }

    /** 4.25 → "4,25"; 1234 → "1 234" (non-breaking space). */
    public static function number(float|int $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', "\u{00A0}");
    }

    /** Average of marks with two decimals, or an em dash when there is none yet. */
    public static function average(float|int|null $value): string
    {
        return $value === null ? '—' : self::number($value, 2);
    }

    /** Mirëmëngjes / Mirëdita / Mirëmbrëma, depending on the time of day. */
    public static function greeting(?DateTimeInterface $now = null): string
    {
        $hour = (int) ($now ?? new DateTimeImmutable())->format('G');

        return match (true) {
            $hour < 12 => 'Mirëmëngjes',
            $hour < 18 => 'Mirëdita',
            default    => 'Mirëmbrëma',
        };
    }

    /** 12, 1 → "XII-1" — the only way a class label is written (as on the school's official timetable). */
    public static function classLabel(int $gradeLevel, int $section): string
    {
        return self::grade($gradeLevel) . '-' . $section;
    }

    /** Grade level as a Roman numeral: 10 → "X", 12 → "XII". Any grade the school adds works. */
    public static function grade(int $level): string
    {
        $roman = '';
        foreach (['X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $numeral => $value) {
            while ($level >= $value) {
                $roman .= $numeral;
                $level -= $value;
            }
        }

        return $roman;
    }

    /** "08:00:00" → "08:00" (times from TIME columns). */
    public static function time(?string $value): string
    {
        return $value === null ? '' : substr($value, 0, 5);
    }

    /** "Prof. Enver Bajrami" (title only when present). */
    public static function personName(?string $title, string $firstName, string $lastName): string
    {
        return trim(($title !== null && $title !== '' ? $title . ' ' : '') . $firstName . ' ' . $lastName);
    }

    /** "Arta", "Gashi" → "AG" (avatars). */
    public static function initials(string $firstName, string $lastName): string
    {
        return mb_strtoupper(mb_substr(trim($firstName), 0, 1) . mb_substr(trim($lastName), 0, 1));
    }

    /** Uppercase the first letter, UTF-8 safe: "e premte" → "E premte". */
    public static function ucfirst(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    private static function toDate(DateTimeInterface|string|null $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value === null || trim($value) === '') {
            return null;
        }

        return new DateTimeImmutable($value);
    }
}
