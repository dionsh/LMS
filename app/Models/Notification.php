<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Personal notifications (lajmërimet, the bell). `url` is always an internal path.
 */
final class Notification extends Model
{
    /**
     * Notify every student of a class. A student who still has an unread
     * notification of the same type is not notified again, so saving a
     * timetable several times in a row does not pile them up.
     *
     * @return int how many students were notified
     */
    public static function notifyClass(int $classId, string $type, string $title, ?string $body, ?string $url): int
    {
        return self::execute(
            "INSERT INTO notifications (user_id, type, title, body, url)
             SELECT en.student_id, :type1, :title, :body, :url
               FROM enrollments en
               JOIN users u ON u.id = en.student_id AND u.status = 'active'
              WHERE en.class_id = :class
                AND NOT EXISTS (SELECT 1 FROM notifications n
                                 WHERE n.user_id = en.student_id AND n.type = :type2 AND n.read_at IS NULL)",
            ['type1' => $type, 'title' => $title, 'body' => $body, 'url' => $url, 'class' => $classId, 'type2' => $type]
        );
    }

    /** Mark a user's unread notifications of one type as read (e.g. on opening the timetable). */
    public static function markTypeRead(int $userId, string $type): void
    {
        self::execute(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND type = ? AND read_at IS NULL',
            [$userId, $type]
        );
    }

    /** The user's unread notifications of one type, newest first. */
    public static function unreadOfType(int $userId, string $type): array
    {
        return self::fetchAll(
            'SELECT id, title, body, url, created_at FROM notifications
              WHERE user_id = ? AND type = ? AND read_at IS NULL ORDER BY created_at DESC',
            [$userId, $type]
        );
    }
}
