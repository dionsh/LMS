<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The bell schedule (`lesson_periods`): lesson times per shift.
 * Timetable slots store only the period number, so changing these times
 * moves every lesson of the shift without touching the timetable.
 */
final class LessonPeriod extends Model
{
    /** @var array<int, array> shift => periods, loaded once per request */
    private static array $cache = [];

    /**
     * Periods of one shift, keyed by period number:
     * [1 => ['number' => 1, 'starts_at' => '08:00', 'ends_at' => '08:45', 'break_after' => 5], …]
     * break_after is the gap in minutes before the next period (null after the last one).
     */
    public static function forShift(int $shift): array
    {
        if (isset(self::$cache[$shift])) {
            return self::$cache[$shift];
        }

        $rows = self::fetchAll(
            "SELECT number,
                    TIME_FORMAT(starts_at, '%H:%i') AS starts_at,
                    TIME_FORMAT(ends_at, '%H:%i')   AS ends_at
               FROM lesson_periods
              WHERE shift = ?
              ORDER BY number",
            [$shift]
        );

        $periods = [];
        foreach ($rows as $index => $row) {
            $next = $rows[$index + 1] ?? null;
            $row['number'] = (int) $row['number'];
            $row['break_after'] = $next === null
                ? null
                : (int) ((strtotime($next['starts_at']) - strtotime($row['ends_at'])) / 60);
            $periods[$row['number']] = $row;
        }

        return self::$cache[$shift] = $periods;
    }

    /**
     * Replace a shift's bell schedule.
     *
     * @param list<array{0: string, 1: string}> $times [[starts_at, ends_at], …] for periods 1, 2, …
     */
    public static function replaceShift(int $shift, array $times): void
    {
        self::execute('DELETE FROM lesson_periods WHERE shift = ?', [$shift]);

        foreach (array_values($times) as $index => [$startsAt, $endsAt]) {
            self::run(
                'INSERT INTO lesson_periods (shift, number, starts_at, ends_at) VALUES (?, ?, ?, ?)',
                [$shift, $index + 1, $startsAt, $endsAt]
            );
        }

        unset(self::$cache[$shift]);
    }

    /** The highest period number the timetable uses in classes of this shift (0 = none). */
    public static function highestUsed(int $shift, int $academicYearId): int
    {
        return (int) self::fetchValue(
            'SELECT COALESCE(MAX(se.period_number), 0)
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
              WHERE c.shift = ? AND c.academic_year_id = ?',
            [$shift, $academicYearId]
        );
    }
}
