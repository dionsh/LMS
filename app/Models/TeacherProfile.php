<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherProfile extends Model
{
    /**
     * Create or update a teacher's profile.
     * $showOnWebsite controls whether the teacher appears on the public staff page;
     * $timetableNumber is their number on the school's printed timetable (null = none).
     */
    public static function save(
        int $userId,
        ?string $title,
        bool $showOnWebsite,
        ?string $specialization = null,
        ?string $bio = null,
        ?int $timetableNumber = null,
    ): void {
        self::run(
            'INSERT INTO teacher_profiles (user_id, title, specialization, bio, show_on_website, timetable_number)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), specialization = VALUES(specialization),
                                     bio = VALUES(bio), show_on_website = VALUES(show_on_website),
                                     timetable_number = VALUES(timetable_number)',
            [$userId, $title, $specialization, $bio, $showOnWebsite, $timetableNumber]
        );
    }

    /** Is this timetable number already another teacher's? */
    public static function numberTaken(int $number, ?int $exceptUserId = null): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM teacher_profiles WHERE timetable_number = ? AND user_id <> ?',
            [$number, $exceptUserId ?? 0]
        ) !== null;
    }
}
