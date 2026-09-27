<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The grades the school teaches (`grade_levels`): X, XI, XII.
 * The label is the Roman numeral of `level` (Format::grade()).
 */
final class GradeLevel extends Model
{
    /**
     * Every grade with its default shift and, for the given year, its number
     * of classes and students, plus the size of its curriculum.
     */
    public static function overview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT g.level, g.shift,
                    (SELECT COUNT(*) FROM classes c WHERE c.grade_level = g.level AND c.academic_year_id = :year1) AS classes,
                    (SELECT COUNT(*) FROM enrollments en JOIN classes c ON c.id = en.class_id
                      WHERE c.grade_level = g.level AND en.academic_year_id = :year2) AS students,
                    (SELECT COUNT(*) FROM grade_subjects gs WHERE gs.grade_level = g.level) AS subjects,
                    (SELECT SUM(gs.weekly_hours) FROM grade_subjects gs WHERE gs.grade_level = g.level) AS weekly_hours,
                    (SELECT COUNT(*) FROM grade_subjects gs WHERE gs.grade_level = g.level AND gs.weekly_hours IS NULL) AS missing_hours
               FROM grade_levels g
              ORDER BY g.level',
            ['year1' => $academicYearId, 'year2' => $academicYearId]
        );
    }

    /** @return array{level: int, shift: int}|null */
    public static function find(int $level): ?array
    {
        return self::fetchOne('SELECT level, shift FROM grade_levels WHERE level = ?', [$level]);
    }

    /** Grade levels for <select>s and checks: [10 => shift, 11 => shift, …] */
    public static function shifts(): array
    {
        $rows = self::fetchAll('SELECT level, shift FROM grade_levels ORDER BY level');

        return array_map('intval', array_column($rows, 'shift', 'level'));
    }

    public static function create(int $level, int $shift): void
    {
        self::run('INSERT INTO grade_levels (level, shift) VALUES (?, ?)', [$level, $shift]);
    }

    public static function updateShift(int $level, int $shift): void
    {
        self::execute('UPDATE grade_levels SET shift = ? WHERE level = ?', [$shift, $level]);
    }

    /** Does the grade have classes in any school year? (Then it cannot be removed.) */
    public static function hasClasses(int $level): bool
    {
        return self::fetchValue('SELECT 1 FROM classes WHERE grade_level = ? LIMIT 1', [$level]) !== null;
    }

    /** Removes the grade and its curriculum. Only for a grade without classes. */
    public static function delete(int $level): void
    {
        self::execute('DELETE FROM grade_levels WHERE level = ?', [$level]);
    }
}
