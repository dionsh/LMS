<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Request;

/**
 * "Who did what" — shown on the admin dashboard and kept as an audit trail
 * (account changes, password changes, grade corrections, deletions …).
 * Sign-ins are not logged here; they are in login_attempts.
 */
final class ActivityLog extends Model
{
    public static function record(
        ?int $userId,
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): void {
        self::run(
            'INSERT INTO activity_log (user_id, action, subject_type, subject_id, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $action, $subjectType, $subjectId, mb_substr($description, 0, 255), Request::current()?->ip()]
        );
    }
}
