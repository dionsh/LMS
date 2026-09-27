<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Which subjects a teacher teaches (`teacher_subjects`). When a subject is
 * assigned in a class, these teachers are offered first.
 */
final class TeacherSubject extends Model
{
    /** @return list<int> subject ids */
    public static function forTeacher(int $teacherId): array
    {
        return array_map('intval', array_column(
            self::fetchAll('SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?', [$teacherId]),
            'subject_id'
        ));
    }

    /** What every teacher teaches: [teacher id => [subject id => true]] */
    public static function all(): array
    {
        $subjects = [];
        foreach (self::fetchAll('SELECT teacher_id, subject_id FROM teacher_subjects') as $row) {
            $subjects[(int) $row['teacher_id']][(int) $row['subject_id']] = true;
        }

        return $subjects;
    }

    /** Replace the teacher's subjects. @param list<int> $subjectIds */
    public static function save(int $teacherId, array $subjectIds): void
    {
        self::execute('DELETE FROM teacher_subjects WHERE teacher_id = ?', [$teacherId]);

        foreach ($subjectIds as $subjectId) {
            self::run('INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)', [$teacherId, $subjectId]);
        }
    }

    /** Add one subject to a teacher (kept if already there). */
    public static function add(int $teacherId, int $subjectId): void
    {
        self::run('INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)', [$teacherId, $subjectId]);
    }
}
