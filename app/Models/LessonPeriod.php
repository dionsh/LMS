<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The bell schedule (`lesson_periods`): lesson times per shift.
 */
final class LessonPeriod extends Model
{
    /**
     * Periods of one shift, keyed by period number:
     * [1 => ['number' => 1, 'starts_at' => '08:00', 'ends_at' => '08:45', 'break_after' => 5], …]
     * break_after is the gap in minutes before the next period (null after the last one).
     */
    public static function forShift(int $shift): array
    {
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
            $row['break_after'] = $next === null
                ? null
                : (int) ((strtotime($next['starts_at']) - strtotime($row['ends_at'])) / 60);
            $periods[(int) $row['number']] = $row;
        }

        return $periods;
    }
}
