<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class StudentProfile extends Model
{
    /** Create or update a student's profile. */
    public static function save(int $userId, ?string $dateOfBirth, ?string $gender, ?string $studentNumber = null): void
    {
        self::run(
            'INSERT INTO student_profiles (user_id, date_of_birth, gender, student_number)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE date_of_birth = VALUES(date_of_birth), gender = VALUES(gender),
                                     student_number = VALUES(student_number)',
            [$userId, $dateOfBirth, $gender, $studentNumber]
        );
    }

    /** Is this student number (numri i amzës) already used by another student? */
    public static function numberTaken(string $studentNumber, ?int $exceptUserId = null): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM student_profiles WHERE student_number = ? AND user_id <> ?',
            [$studentNumber, $exceptUserId ?? 0]
        ) !== null;
    }
}
