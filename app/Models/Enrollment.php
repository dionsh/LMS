<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Which class a student belongs to in a school year (one class per year).
 */
final class Enrollment extends Model
{
    /** Put a student in a class for the class's year (moves them if already enrolled that year). */
    public static function enroll(int $studentId, int $classId): void
    {
        self::run(
            'INSERT INTO enrollments (student_id, class_id, academic_year_id)
             SELECT ?, id, academic_year_id FROM classes WHERE id = ?
             ON DUPLICATE KEY UPDATE class_id = VALUES(class_id)',
            [$studentId, $classId]
        );
    }
}
