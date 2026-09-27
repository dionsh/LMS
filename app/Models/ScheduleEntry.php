<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * The weekly timetable (`schedule_entries`): one lesson per class per day and
 * period. A lesson is held in the class's own room unless room_id names
 * another one (the gym, a laboratory).
 *
 * Clashes are judged on real clock times (lesson_periods of each class's
 * shift), never on period numbers alone: period 1 of the morning and period 1
 * of the afternoon are different times.
 */
final class ScheduleEntry extends Model
{
    /** The class's lessons with subject, teacher and room, in day/period order. */
    public static function forClass(int $classId): array
    {
        return self::fetchAll(
            'SELECT se.id, se.day_of_week AS day, se.period_number AS period, se.class_subject_id, se.room_id,
                    s.name AS subject_name, s.short_name,
                    u.id AS teacher_id, u.first_name AS teacher_first_name, u.last_name AS teacher_last_name,
                    tp.title AS teacher_title, tp.timetable_number,
                    r.name AS room_name, hr.name AS home_room_name
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               JOIN class_subjects cs ON cs.id = se.class_subject_id
               JOIN subjects s ON s.id = cs.subject_id
               LEFT JOIN users u ON u.id = cs.teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
               LEFT JOIN rooms r ON r.id = se.room_id
               LEFT JOIN rooms hr ON hr.id = c.home_room_id
              WHERE se.class_id = ?
              ORDER BY se.day_of_week, se.period_number',
            [$classId]
        );
    }

    /** A teacher's lessons in a year, across both shifts, with class, times and room. */
    public static function forTeacher(int $teacherId, int $academicYearId): array
    {
        return self::fetchAll(
            "SELECT se.day_of_week AS day, se.period_number AS period, cs.id AS class_subject_id,
                    c.id AS class_id, c.grade_level, c.section, c.shift,
                    s.name AS subject_name, s.short_name,
                    COALESCE(r.name, hr.name) AS room_name,
                    TIME_FORMAT(p.starts_at, '%H:%i') AS starts_at, TIME_FORMAT(p.ends_at, '%H:%i') AS ends_at
               FROM schedule_entries se
               JOIN class_subjects cs ON cs.id = se.class_subject_id
               JOIN classes c ON c.id = se.class_id
               JOIN subjects s ON s.id = cs.subject_id
               LEFT JOIN rooms r ON r.id = se.room_id
               LEFT JOIN rooms hr ON hr.id = c.home_room_id
               LEFT JOIN lesson_periods p ON p.shift = c.shift AND p.number = se.period_number
              WHERE cs.teacher_id = ? AND c.academic_year_id = ?
              ORDER BY se.day_of_week, p.starts_at, se.period_number",
            [$teacherId, $academicYearId]
        );
    }

    /** Every lesson of a shift in a year (the whole-school timetable sheet). */
    public static function forShift(int $academicYearId, int $shift): array
    {
        return self::fetchAll(
            'SELECT se.class_id, se.day_of_week AS day, se.period_number AS period, se.class_subject_id, se.room_id,
                    s.name AS subject_name, s.short_name,
                    u.id AS teacher_id, u.first_name AS teacher_first_name, u.last_name AS teacher_last_name,
                    tp.title AS teacher_title, tp.timetable_number, r.name AS room_name
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               JOIN class_subjects cs ON cs.id = se.class_subject_id
               JOIN subjects s ON s.id = cs.subject_id
               LEFT JOIN users u ON u.id = cs.teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
               LEFT JOIN rooms r ON r.id = se.room_id
              WHERE c.academic_year_id = ? AND c.shift = ?',
            [$academicYearId, $shift]
        );
    }

    /**
     * What other classes of the year occupy while a class of $shift has lessons:
     * one row per (day, period of $shift) × lesson elsewhere at an overlapping time,
     * with that lesson's teacher and room (its own room unless another is named).
     */
    public static function occupiedElsewhere(int $academicYearId, int $shift, int $exceptClassId): array
    {
        return self::fetchAll(
            'SELECT se.day_of_week AS day, p.number AS period, cs.teacher_id,
                    COALESCE(se.room_id, c2.home_room_id) AS room_id,
                    c2.grade_level, c2.section
               FROM lesson_periods p
              CROSS JOIN schedule_entries se
               JOIN classes c2 ON c2.id = se.class_id
               JOIN lesson_periods p2 ON p2.shift = c2.shift AND p2.number = se.period_number
               JOIN class_subjects cs ON cs.id = se.class_subject_id
              WHERE p.shift = ? AND c2.academic_year_id = ? AND c2.id <> ?
                AND p2.starts_at < p.ends_at AND p.starts_at < p2.ends_at',
            [$shift, $academicYearId, $exceptClassId]
        );
    }

    /** How many lessons each teacher has on each day of a shift: [teacher id => [day => lessons]]. */
    public static function lessonsPerDay(int $academicYearId, int $shift): array
    {
        $rows = self::fetchAll(
            'SELECT cs.teacher_id, se.day_of_week AS day, COUNT(*) AS lessons
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               JOIN class_subjects cs ON cs.id = se.class_subject_id
              WHERE c.academic_year_id = ? AND c.shift = ? AND cs.teacher_id IS NOT NULL
              GROUP BY cs.teacher_id, se.day_of_week',
            [$academicYearId, $shift]
        );

        $lessons = [];
        foreach ($rows as $row) {
            $lessons[(int) $row['teacher_id']][(int) $row['day']] = (int) $row['lessons'];
        }

        return $lessons;
    }

    /** When each teacher is busy in a year: [teacher_id, class_id, day, starts_at, ends_at] per lesson. */
    public static function teacherTimes(int $academicYearId): array
    {
        return self::fetchAll(
            "SELECT cs.teacher_id, se.class_id, se.day_of_week AS day,
                    TIME_FORMAT(p.starts_at, '%H:%i') AS starts_at, TIME_FORMAT(p.ends_at, '%H:%i') AS ends_at
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               JOIN class_subjects cs ON cs.id = se.class_subject_id
               JOIN lesson_periods p ON p.shift = c.shift AND p.number = se.period_number
              WHERE c.academic_year_id = ? AND cs.teacher_id IS NOT NULL",
            [$academicYearId]
        );
    }

    /**
     * Replace a class's whole week.
     *
     * @param array<int, array<int, array{class_subject_id: int, room_id: ?int}>> $cells day => period => lesson
     */
    public static function replaceForClass(int $classId, array $cells, ?int $updatedBy): void
    {
        self::execute('DELETE FROM schedule_entries WHERE class_id = ?', [$classId]);

        foreach ($cells as $day => $periods) {
            foreach ($periods as $period => $cell) {
                self::create($classId, $day, $period, $cell['class_subject_id'], $cell['room_id'], $updatedBy);
            }
        }
    }

    public static function create(int $classId, int $day, int $period, int $classSubjectId, ?int $roomId, ?int $updatedBy): void
    {
        self::run(
            'INSERT INTO schedule_entries (class_id, class_subject_id, day_of_week, period_number, room_id, updated_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$classId, $classSubjectId, $day, $period, $roomId, $updatedBy]
        );
    }

    /**
     * Every clash in the year: a teacher or a room in two places at overlapping
     * times. Rows name both classes; kind is 'teacher' or 'room'.
     */
    public static function clashes(int $academicYearId): array
    {
        $lessons = self::fetchAll(
            "SELECT se.id, se.day_of_week AS day, se.period_number AS period,
                    c.id AS class_id, c.grade_level, c.section,
                    TIME_FORMAT(p.starts_at, '%H:%i') AS starts_at, TIME_FORMAT(p.ends_at, '%H:%i') AS ends_at,
                    cs.teacher_id, u.first_name AS teacher_first_name, u.last_name AS teacher_last_name,
                    COALESCE(se.room_id, c.home_room_id) AS room_id, r.name AS room_name
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               JOIN lesson_periods p ON p.shift = c.shift AND p.number = se.period_number
               JOIN class_subjects cs ON cs.id = se.class_subject_id
               LEFT JOIN users u ON u.id = cs.teacher_id
               LEFT JOIN rooms r ON r.id = COALESCE(se.room_id, c.home_room_id)
              WHERE c.academic_year_id = ?
              ORDER BY se.day_of_week, p.starts_at, se.id",
            [$academicYearId]
        );

        // Only lessons with the same teacher, or in the same room, on the same day can clash
        $groups = [];
        foreach ($lessons as $index => $lesson) {
            if ($lesson['teacher_id'] !== null) {
                $groups['teacher'][$lesson['teacher_id'] . '-' . $lesson['day']][] = $index;
            }
            if ($lesson['room_id'] !== null) {
                $groups['room'][$lesson['room_id'] . '-' . $lesson['day']][] = $index;
            }
        }

        $pairs = [];
        foreach (['room', 'teacher'] as $kind) {          // a pair that is both is reported as the teacher's
            foreach ($groups[$kind] ?? [] as $members) {
                foreach ($members as $i => $first) {
                    foreach (array_slice($members, $i + 1) as $second) {
                        [$a, $b] = [$lessons[$first], $lessons[$second]];
                        if ($a['starts_at'] < $b['ends_at'] && $b['starts_at'] < $a['ends_at']) {
                            $pairs[$first . '-' . $second] = [$a, $b, $kind];
                        }
                    }
                }
            }
        }
        ksort($pairs, SORT_NATURAL);

        $clashes = [];
        foreach ($pairs as [$a, $b, $kind]) {
            $clashes[] = [
                'day' => $a['day'], 'kind' => $kind,
                'class_a' => $a['class_id'], 'grade_a' => $a['grade_level'], 'section_a' => $a['section'], 'period_a' => $a['period'],
                'class_b' => $b['class_id'], 'grade_b' => $b['grade_level'], 'section_b' => $b['section'], 'period_b' => $b['period'],
                'teacher_first_name' => $a['teacher_first_name'], 'teacher_last_name' => $a['teacher_last_name'],
                'room_name' => $a['room_name'],
            ];
        }

        return $clashes;
    }

    /** Lessons whose period no longer exists in their shift's bell schedule (e.g. after removing period 7). */
    public static function outsideBellSchedule(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT c.id AS class_id, c.grade_level, c.section, se.day_of_week AS day, se.period_number AS period
               FROM schedule_entries se
               JOIN classes c ON c.id = se.class_id
               LEFT JOIN lesson_periods p ON p.shift = c.shift AND p.number = se.period_number
              WHERE c.academic_year_id = ? AND p.id IS NULL
              ORDER BY c.grade_level, c.section, se.day_of_week, se.period_number',
            [$academicYearId]
        );
    }

    /** When the timetable of a shift (or of one class) last changed, or null. */
    public static function lastChanged(int $academicYearId, ?int $shift = null, ?int $classId = null): ?string
    {
        $sql = 'SELECT MAX(COALESCE(se.updated_at, se.created_at))
                  FROM schedule_entries se JOIN classes c ON c.id = se.class_id
                 WHERE c.academic_year_id = :year';
        $params = ['year' => $academicYearId];

        if ($shift !== null) {
            $sql .= ' AND c.shift = :shift';
            $params['shift'] = $shift;
        }
        if ($classId !== null) {
            $sql .= ' AND c.id = :class';
            $params['class'] = $classId;
        }

        $value = self::fetchValue($sql, $params);

        return $value === null ? null : (string) $value;
    }
}
