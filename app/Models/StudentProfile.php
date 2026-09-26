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
}
