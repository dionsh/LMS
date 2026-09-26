<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherProfile extends Model
{
    /** Create or update a teacher's profile. $showOnWebsite controls the public staff page. */
    public static function save(int $userId, ?string $title, bool $showOnWebsite, ?string $specialization = null): void
    {
        self::run(
            'INSERT INTO teacher_profiles (user_id, title, specialization, show_on_website)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), specialization = VALUES(specialization),
                                     show_on_website = VALUES(show_on_website)',
            [$userId, $title, $specialization, $showOnWebsite]
        );
    }
}
