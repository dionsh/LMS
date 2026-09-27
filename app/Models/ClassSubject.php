<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * A subject taught in a class by a teacher (`class_subjects`) — the hub that
 * timetable slots, homework, tests and marks hang off.
 *
 * Weekly hours: a class follows its grade's curriculum (grade_subjects) unless
 * it has its own number (class_subjects.weekly_hours); `hours` below is the
 * number that applies.
 */
final class ClassSubject extends Model
{
    private const HOURS = 'COALESCE(cs.weekly_hours, gs.weekly_hours)';

    private const CURRICULUM_JOIN = 'LEFT JOIN grade_subjects gs ON gs.grade_level = c.grade_level AND gs.subject_id = cs.subject_id';

    /**
     * The subjects of a class with their teacher, hours (own / curriculum / applying),
     * lessons already in the timetable, and whether the subject is in the curriculum.
     */
    public static function forClass(int $classId): array
    {
        return self::fetchAll(
            'SELECT cs.id, cs.class_id, cs.subject_id, cs.teacher_id,
                    cs.weekly_hours AS own_hours, gs.weekly_hours AS plan_hours, ' . self::HOURS . ' AS hours,
                    gs.subject_id IS NOT NULL AS in_curriculum,
                    s.name AS subject_name, s.short_name, s.is_active AS subject_active,
                    u.first_name AS teacher_first_name, u.last_name AS teacher_last_name, u.status AS teacher_status,
                    tp.title AS teacher_title, tp.timetable_number,
                    (SELECT COUNT(*) FROM schedule_entries se WHERE se.class_subject_id = cs.id) AS lessons
               FROM class_subjects cs
               JOIN classes c ON c.id = cs.class_id
               JOIN subjects s ON s.id = cs.subject_id
               ' . self::CURRICULUM_JOIN . '
               LEFT JOIN users u ON u.id = cs.teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
              WHERE cs.class_id = ?
              ORDER BY s.sort_order, s.name',
            [$classId]
        );
    }

    /** One class-subject with its class and subject — or null. */
    public static function find(int $id): ?array
    {
        return self::fetchOne(
            'SELECT cs.id, cs.class_id, cs.subject_id, cs.teacher_id, ' . self::HOURS . ' AS hours,
                    c.academic_year_id, c.grade_level, c.section, c.shift, c.home_room_id,
                    s.name AS subject_name, s.short_name
               FROM class_subjects cs
               JOIN classes c ON c.id = cs.class_id
               JOIN subjects s ON s.id = cs.subject_id
               ' . self::CURRICULUM_JOIN . '
              WHERE cs.id = ?',
            [$id]
        );
    }

    /**
     * Give classes every subject of their grade's curriculum they do not have yet
     * (no teacher, hours as in the curriculum). Limited to one grade or one class
     * when given. Returns how many subjects were added.
     */
    public static function addFromCurriculum(int $academicYearId, ?int $gradeLevel = null, ?int $classId = null): int
    {
        $sql = 'INSERT INTO class_subjects (class_id, subject_id)
                SELECT c.id, gs.subject_id
                  FROM classes c
                  JOIN grade_subjects gs ON gs.grade_level = c.grade_level
                  LEFT JOIN class_subjects cs ON cs.class_id = c.id AND cs.subject_id = gs.subject_id
                 WHERE c.academic_year_id = :year AND cs.id IS NULL';
        $params = ['year' => $academicYearId];

        if ($gradeLevel !== null) {
            $sql .= ' AND c.grade_level = :grade';
            $params['grade'] = $gradeLevel;
        }
        if ($classId !== null) {
            $sql .= ' AND c.id = :class';
            $params['class'] = $classId;
        }

        return self::execute($sql, $params);
    }

    /**
     * After subjects leave a grade's curriculum: remove them from that grade's
     * classes where nothing depends on them yet (no teacher, no timetable, no
     * coursework). Rows that are in use stay and are flagged on the class page.
     *
     * @param list<int> $subjectIds
     */
    public static function removeUnused(int $academicYearId, int $gradeLevel, array $subjectIds): int
    {
        if ($subjectIds === []) {
            return 0;
        }

        $in = implode(', ', array_fill(0, count($subjectIds), '?'));

        return self::execute(
            'DELETE cs FROM class_subjects cs
               JOIN classes c ON c.id = cs.class_id
              WHERE c.academic_year_id = ? AND c.grade_level = ? AND cs.subject_id IN (' . $in . ')
                AND cs.teacher_id IS NULL
                AND NOT EXISTS (SELECT 1 FROM schedule_entries se WHERE se.class_subject_id = cs.id)
                AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.class_subject_id = cs.id)
                AND NOT EXISTS (SELECT 1 FROM assessments a WHERE a.class_subject_id = cs.id)
                AND NOT EXISTS (SELECT 1 FROM grades g WHERE g.class_subject_id = cs.id)
                AND NOT EXISTS (SELECT 1 FROM term_grades tg WHERE tg.class_subject_id = cs.id)',
            array_merge([$academicYearId, $gradeLevel], $subjectIds)
        );
    }

    /** Every class-subject of a year with its class, teacher and hours, in class order. */
    public static function forYear(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT cs.id, cs.class_id, cs.subject_id, cs.teacher_id, ' . self::HOURS . ' AS hours,
                    c.grade_level, c.section, c.shift, s.name AS subject_name
               FROM class_subjects cs
               JOIN classes c ON c.id = cs.class_id
               JOIN subjects s ON s.id = cs.subject_id
               ' . self::CURRICULUM_JOIN . '
              WHERE c.academic_year_id = ?
              ORDER BY c.grade_level, c.section, s.sort_order',
            [$academicYearId]
        );
    }

    public static function setTeacher(int $id, ?int $teacherId): void
    {
        self::execute('UPDATE class_subjects SET teacher_id = ? WHERE id = ?', [$teacherId, $id]);
    }

    public static function add(int $classId, int $subjectId): int
    {
        return self::insert('INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)', [$classId, $subjectId]);
    }

    /** Set the teacher (null = not assigned) and the class's own weekly hours (null = as in the curriculum). */
    public static function update(int $id, ?int $teacherId, ?int $weeklyHours): void
    {
        self::execute('UPDATE class_subjects SET teacher_id = ?, weekly_hours = ? WHERE id = ?', [$teacherId, $weeklyHours, $id]);
    }

    /** Is anything attached to it (timetable lessons, homework, tests, marks)? Then it cannot be removed. */
    public static function isInUse(int $id): bool
    {
        return (bool) self::fetchValue(
            'SELECT EXISTS (SELECT 1 FROM schedule_entries WHERE class_subject_id = :a)
                 OR EXISTS (SELECT 1 FROM assignments WHERE class_subject_id = :b)
                 OR EXISTS (SELECT 1 FROM assessments WHERE class_subject_id = :c)
                 OR EXISTS (SELECT 1 FROM grades WHERE class_subject_id = :d)
                 OR EXISTS (SELECT 1 FROM term_grades WHERE class_subject_id = :e)',
            ['a' => $id, 'b' => $id, 'c' => $id, 'd' => $id, 'e' => $id]
        );
    }

    public static function delete(int $id): void
    {
        self::execute('DELETE FROM class_subjects WHERE id = ?', [$id]);
    }

    /** Everything a teacher teaches in a year: class, subject, hours, lessons in the timetable, students. */
    public static function forTeacher(int $teacherId, int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT cs.id, cs.subject_id, c.id AS class_id, c.grade_level, c.section, c.shift, c.homeroom_teacher_id,
                    s.name AS subject_name, s.short_name, ' . self::HOURS . ' AS hours,
                    (SELECT COUNT(*) FROM schedule_entries se WHERE se.class_subject_id = cs.id) AS lessons,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.class_id = c.id) AS students
               FROM class_subjects cs
               JOIN classes c ON c.id = cs.class_id
               JOIN subjects s ON s.id = cs.subject_id
               ' . self::CURRICULUM_JOIN . '
              WHERE cs.teacher_id = ? AND c.academic_year_id = ?
              ORDER BY c.grade_level, c.section, s.sort_order',
            [$teacherId, $academicYearId]
        );
    }

    /** [total subjects in classes of the year, of which with a teacher] */
    public static function assignmentCounts(int $academicYearId): array
    {
        $row = self::fetchOne(
            'SELECT COUNT(*) AS total, COALESCE(SUM(cs.teacher_id IS NOT NULL), 0) AS assigned
               FROM class_subjects cs JOIN classes c ON c.id = cs.class_id
              WHERE c.academic_year_id = ?',
            [$academicYearId]
        );

        return [(int) ($row['total'] ?? 0), (int) ($row['assigned'] ?? 0)];
    }
}
