<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Classes (paralelet), e.g. XII-1. Named SchoolClass because "Class" is reserved in PHP.
 * The label "XII-1" is never stored; see Format::classLabel().
 */
final class SchoolClass extends Model
{
    /** Columns every "one class" query returns (c = classes, t = homeroom teacher, r = home room). */
    private const DETAIL_COLUMNS = 'c.id, c.academic_year_id, c.grade_level, c.section, c.stream, c.shift,
                                    c.homeroom_teacher_id, c.home_room_id, r.name AS room_name,
                                    t.first_name AS teacher_first_name, t.last_name AS teacher_last_name,
                                    tp.title AS teacher_title';

    private const DETAIL_JOINS = 'LEFT JOIN users t ON t.id = c.homeroom_teacher_id
                                  LEFT JOIN teacher_profiles tp ON tp.user_id = t.id
                                  LEFT JOIN rooms r ON r.id = c.home_room_id';

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

    /** Classes of a year for <select> lists: [['id', 'grade_level', 'section'], …] */
    public static function optionsForYear(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT id, grade_level, section FROM classes WHERE academic_year_id = ? ORDER BY grade_level, section',
            [$academicYearId]
        );
    }

    /** The class, only if it belongs to the given year (validates ids coming from forms). */
    public static function findInYear(int $classId, int $academicYearId): ?array
    {
        return self::fetchOne(
            'SELECT id, grade_level, section, shift FROM classes WHERE id = ? AND academic_year_id = ?',
            [$classId, $academicYearId]
        );
    }

    /** One class with its homeroom teacher, room and year — or null. */
    public static function find(int $id): ?array
    {
        return self::fetchOne(
            'SELECT ' . self::DETAIL_COLUMNS . ', y.name AS year_name, y.is_current AS year_is_current,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.class_id = c.id) AS students
               FROM classes c
               JOIN academic_years y ON y.id = c.academic_year_id
               ' . self::DETAIL_JOINS . '
              WHERE c.id = ?',
            [$id]
        );
    }

    public static function setHomeroomTeacher(int $classId, ?int $teacherId): void
    {
        self::execute('UPDATE classes SET homeroom_teacher_id = ? WHERE id = ?', [$teacherId, $classId]);
    }

    /**
     * @param array{academic_year_id: int, grade_level: int, section: int, shift: int, stream: ?string,
     *              homeroom_teacher_id: ?int, home_room_id: ?int} $data
     */
    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO classes (academic_year_id, grade_level, section, shift, stream, homeroom_teacher_id, home_room_id)
             VALUES (:year, :grade, :section, :shift, :stream, :homeroom, :room)',
            [
                'year'     => $data['academic_year_id'],
                'grade'    => $data['grade_level'],
                'section'  => $data['section'],
                'shift'    => $data['shift'],
                'stream'   => $data['stream'],
                'homeroom' => $data['homeroom_teacher_id'],
                'room'     => $data['home_room_id'],
            ]
        );
    }

    /** @param array{section: int, shift: int, stream: ?string, homeroom_teacher_id: ?int, home_room_id: ?int} $data */
    public static function update(int $id, array $data): void
    {
        self::execute(
            'UPDATE classes SET section = :section, shift = :shift, stream = :stream,
                                homeroom_teacher_id = :homeroom, home_room_id = :room
              WHERE id = :id',
            [
                'section'  => $data['section'],
                'shift'    => $data['shift'],
                'stream'   => $data['stream'],
                'homeroom' => $data['homeroom_teacher_id'],
                'room'     => $data['home_room_id'],
                'id'       => $id,
            ]
        );
    }

    /** Move every class of a grade in a year to another shift (the timetable keeps its periods). */
    public static function setShiftForGrade(int $academicYearId, int $gradeLevel, int $shift): int
    {
        return self::execute(
            'UPDATE classes SET shift = ? WHERE academic_year_id = ? AND grade_level = ?',
            [$shift, $academicYearId, $gradeLevel]
        );
    }

    /** Deletes an empty class; its subjects and timetable go with it. The database refuses if coursework exists. */
    public static function delete(int $id): void
    {
        self::execute('DELETE FROM classes WHERE id = ?', [$id]);
    }

    public static function sectionTaken(int $academicYearId, int $gradeLevel, int $section, ?int $exceptId = null): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM classes WHERE academic_year_id = ? AND grade_level = ? AND section = ? AND id <> ?',
            [$academicYearId, $gradeLevel, $section, $exceptId ?? 0]
        ) !== null;
    }

    /** The lowest free section number of a grade (fills gaps: 1, 2, 4 → 3). */
    public static function nextSection(int $academicYearId, int $gradeLevel): int
    {
        $taken = array_map('intval', array_column(self::fetchAll(
            'SELECT section FROM classes WHERE academic_year_id = ? AND grade_level = ?',
            [$academicYearId, $gradeLevel]
        ), 'section'));

        $section = 1;
        while (in_array($section, $taken, true)) {
            $section++;
        }

        return $section;
    }

    /** The class a teacher is homeroom teacher of in a year (other than $exceptId), or null. */
    public static function homeroomOf(int $teacherId, int $academicYearId, ?int $exceptId = null): ?array
    {
        return self::fetchOne(
            'SELECT id, grade_level, section FROM classes
              WHERE homeroom_teacher_id = ? AND academic_year_id = ? AND id <> ? LIMIT 1',
            [$teacherId, $academicYearId, $exceptId ?? 0]
        );
    }

    /**
     * Another class of the year that has this room as its own in the same shift, or null.
     * (A morning class and an afternoon class may share a room; two morning classes may not.)
     */
    public static function sharingRoom(int $roomId, int $shift, int $academicYearId, ?int $exceptId = null): ?array
    {
        return self::fetchOne(
            'SELECT id, grade_level, section FROM classes
              WHERE home_room_id = ? AND shift = ? AND academic_year_id = ? AND id <> ? LIMIT 1',
            [$roomId, $shift, $academicYearId, $exceptId ?? 0]
        );
    }

    /**
     * Every class of the year with its homeroom teacher, room, students, and how
     * far its subjects and timetable are set up.
     */
    public static function overview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT ' . self::DETAIL_COLUMNS . ', t.id AS teacher_id,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.class_id = c.id) AS students,
                    (SELECT COUNT(*) FROM class_subjects cs WHERE cs.class_id = c.id) AS subjects,
                    (SELECT COUNT(*) FROM class_subjects cs WHERE cs.class_id = c.id AND cs.teacher_id IS NOT NULL) AS subjects_with_teacher,
                    (SELECT SUM(COALESCE(cs.weekly_hours, gs.weekly_hours))
                       FROM class_subjects cs
                       JOIN subjects s ON s.id = cs.subject_id
                       LEFT JOIN grade_subjects gs ON gs.grade_level = c.grade_level AND gs.subject_id = COALESCE(s.fills_subject_id, cs.subject_id)
                      WHERE cs.class_id = c.id) AS planned_hours,
                    (SELECT COUNT(*) FROM schedule_entries se WHERE se.class_id = c.id) AS lessons
               FROM classes c
               ' . self::DETAIL_JOINS . '
              WHERE c.academic_year_id = ?
              ORDER BY c.grade_level, c.section',
            [$academicYearId]
        );
    }

    /** The students enrolled in a class, by surname. */
    public static function students(int $classId): array
    {
        return self::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.username, u.status, u.last_login_at,
                    u.password_hash IS NOT NULL AS has_credentials
               FROM enrollments en
               JOIN users u ON u.id = en.student_id
              WHERE en.class_id = ?
              ORDER BY u.last_name, u.first_name',
            [$classId]
        );
    }

    /** The class a student is enrolled in this year, with its homeroom teacher and room — or null. */
    public static function forStudent(int $studentId, int $academicYearId): ?array
    {
        return self::fetchOne(
            'SELECT ' . self::DETAIL_COLUMNS . '
               FROM enrollments en
               JOIN classes c ON c.id = en.class_id
               ' . self::DETAIL_JOINS . '
              WHERE en.student_id = ? AND en.academic_year_id = ?',
            [$studentId, $academicYearId]
        );
    }
}
