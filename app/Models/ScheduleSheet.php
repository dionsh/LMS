<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The timetable as the school prints it (`schedule_sheet_cells`): the number
 * of the teacher in each class's slot. See ScheduleSheetService for how it
 * becomes the class timetables.
 */
final class ScheduleSheet extends Model
{
    /** A shift's sheet: [class id => [day => [period => teacher number]]] */
    public static function forShift(int $academicYearId, int $shift): array
    {
        $rows = self::fetchAll(
            'SELECT sc.class_id, sc.day_of_week AS day, sc.period_number AS period, sc.teacher_number
               FROM schedule_sheet_cells sc
               JOIN classes c ON c.id = sc.class_id
              WHERE c.academic_year_id = ? AND c.shift = ?
              ORDER BY sc.class_id, sc.day_of_week, sc.period_number',
            [$academicYearId, $shift]
        );

        $cells = [];
        foreach ($rows as $row) {
            $cells[(int) $row['class_id']][(int) $row['day']][(int) $row['period']] = (int) $row['teacher_number'];
        }

        return $cells;
    }

    public static function hasShift(int $academicYearId, int $shift): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM schedule_sheet_cells sc JOIN classes c ON c.id = sc.class_id
              WHERE c.academic_year_id = ? AND c.shift = ? LIMIT 1',
            [$academicYearId, $shift]
        ) !== null;
    }

    /** When the shift's sheet was last saved, or null. */
    public static function lastSaved(int $academicYearId, int $shift): ?string
    {
        $value = self::fetchValue(
            'SELECT MAX(sc.saved_at) FROM schedule_sheet_cells sc JOIN classes c ON c.id = sc.class_id
              WHERE c.academic_year_id = ? AND c.shift = ?',
            [$academicYearId, $shift]
        );

        return $value === null ? null : (string) $value;
    }

    /**
     * Replace the whole sheet of a shift.
     *
     * @param array<int, array<int, array<int, int>>> $cells class id => day => period => teacher number
     */
    public static function replaceShift(int $academicYearId, int $shift, array $cells): void
    {
        self::execute(
            'DELETE sc FROM schedule_sheet_cells sc JOIN classes c ON c.id = sc.class_id
              WHERE c.academic_year_id = ? AND c.shift = ?',
            [$academicYearId, $shift]
        );

        foreach ($cells as $classId => $days) {
            foreach ($days as $day => $periods) {
                foreach ($periods as $period => $number) {
                    self::run(
                        'INSERT INTO schedule_sheet_cells (class_id, day_of_week, period_number, teacher_number) VALUES (?, ?, ?, ?)',
                        [$classId, $day, $period, $number]
                    );
                }
            }
        }
    }
}
