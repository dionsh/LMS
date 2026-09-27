<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The curriculum (`grade_subjects`, plani mësimor): which subjects each grade
 * studies and how many lessons a week. It can be edited per grade (the grade's
 * page) or per subject (the subject's page) — it is the same table.
 */
final class Curriculum extends Model
{
    /** The grade's subjects in teaching order: [[subject_id, name, short_name, weekly_hours, is_active], …] */
    public static function forGrade(int $level): array
    {
        return self::fetchAll(
            'SELECT s.id AS subject_id, s.name, s.short_name, s.is_active, gs.weekly_hours
               FROM grade_subjects gs
               JOIN subjects s ON s.id = gs.subject_id
              WHERE gs.grade_level = ?
              ORDER BY s.sort_order, s.name',
            [$level]
        );
    }

    /** Every grade's curriculum: [level => [[subject_id, name, short_name, weekly_hours], …]] */
    public static function allGrades(): array
    {
        $rows = self::fetchAll(
            'SELECT gs.grade_level, s.id AS subject_id, s.name, s.short_name, s.is_active, gs.weekly_hours
               FROM grade_subjects gs
               JOIN subjects s ON s.id = gs.subject_id
              ORDER BY gs.grade_level, s.sort_order, s.name'
        );

        $byGrade = [];
        foreach ($rows as $row) {
            $byGrade[(int) $row['grade_level']][] = $row;
        }

        return $byGrade;
    }

    /** Hours per grade for one subject: [10 => 2, 12 => null] (null = hours not decided yet). */
    public static function forSubject(int $subjectId): array
    {
        $rows = self::fetchAll(
            'SELECT grade_level, weekly_hours FROM grade_subjects WHERE subject_id = ? ORDER BY grade_level',
            [$subjectId]
        );

        $hours = [];
        foreach ($rows as $row) {
            $hours[(int) $row['grade_level']] = $row['weekly_hours'] === null ? null : (int) $row['weekly_hours'];
        }

        return $hours;
    }

    /** Fill in the weekly hours of a grade's subject only where none are set (demo data). */
    public static function setHoursIfEmpty(int $level, int $subjectId, int $hours): void
    {
        self::execute(
            'UPDATE grade_subjects SET weekly_hours = ? WHERE grade_level = ? AND subject_id = ? AND weekly_hours IS NULL',
            [$hours, $level, $subjectId]
        );
    }

    /**
     * Replace a grade's curriculum.
     *
     * @param array<int, ?int> $hoursBySubject subject_id => weekly hours (null = not decided)
     */
    public static function saveGrade(int $level, array $hoursBySubject): void
    {
        self::execute('DELETE FROM grade_subjects WHERE grade_level = ?', [$level]);

        foreach ($hoursBySubject as $subjectId => $hours) {
            self::run(
                'INSERT INTO grade_subjects (grade_level, subject_id, weekly_hours) VALUES (?, ?, ?)',
                [$level, $subjectId, $hours]
            );
        }
    }

    /**
     * Replace the grades that study a subject.
     *
     * @param array<int, ?int> $hoursByGrade grade level => weekly hours (null = not decided)
     */
    public static function saveSubject(int $subjectId, array $hoursByGrade): void
    {
        self::execute('DELETE FROM grade_subjects WHERE subject_id = ?', [$subjectId]);

        foreach ($hoursByGrade as $level => $hours) {
            self::run(
                'INSERT INTO grade_subjects (grade_level, subject_id, weekly_hours) VALUES (?, ?, ?)',
                [$level, $subjectId, $hours]
            );
        }
    }
}
