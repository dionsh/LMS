<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Classes (paralelet), e.g. X/13. Named SchoolClass because "Class" is reserved in PHP.
 * The label "X/13" is never stored; see Format::classLabel().
 */
final class SchoolClass extends Model
{
    public static function countForYear(int $academicYearId): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM classes WHERE academic_year_id = ?', [$academicYearId]);
    }

    public static function countWithHomeroom(int $academicYearId): int
    {
        return (int) self::fetchValue(
            'SELECT COUNT(*) FROM classes WHERE academic_year_id = ? AND homeroom_teacher_id IS NOT NULL',
            [$academicYearId]
        );
    }

    /** The class's id, creating it first if it does not exist yet. */
    public static function findOrCreate(int $academicYearId, int $gradeLevel, int $section, int $shift): int
    {
        $id = self::fetchValue(
            'SELECT id FROM classes WHERE academic_year_id = ? AND grade_level = ? AND section = ?',
            [$academicYearId, $gradeLevel, $section]
        );

        if ($id !== null) {
            return (int) $id;
        }

        return self::insert(
            'INSERT INTO classes (academic_year_id, grade_level, section, shift) VALUES (?, ?, ?, ?)',
            [$academicYearId, $gradeLevel, $section, $shift]
        );
    }

    public static function setHomeroomTeacher(int $classId, ?int $teacherId): void
    {
        self::execute('UPDATE classes SET homeroom_teacher_id = ? WHERE id = ?', [$teacherId, $classId]);
    }

    /** Every class of the year with its homeroom teacher and number of enrolled students. */
    public static function overview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT c.id, c.grade_level, c.section, c.stream, c.shift,
                    t.id AS teacher_id, t.first_name AS teacher_first_name, t.last_name AS teacher_last_name,
                    tp.title AS teacher_title,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.class_id = c.id) AS students
               FROM classes c
               LEFT JOIN users t ON t.id = c.homeroom_teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = t.id
              WHERE c.academic_year_id = ?
              ORDER BY c.grade_level, c.section',
            [$academicYearId]
        );
    }

    /** The class a student is enrolled in this year, with its homeroom teacher — or null. */
    public static function forStudent(int $studentId, int $academicYearId): ?array
    {
        return self::fetchOne(
            'SELECT c.id, c.grade_level, c.section, c.shift,
                    t.first_name AS teacher_first_name, t.last_name AS teacher_last_name,
                    tp.title AS teacher_title
               FROM enrollments en
               JOIN classes c ON c.id = en.class_id
               LEFT JOIN users t ON t.id = c.homeroom_teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = t.id
              WHERE en.student_id = ? AND en.academic_year_id = ?',
            [$studentId, $academicYearId]
        );
    }
}
