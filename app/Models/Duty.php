<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Daily duty (kujdestaria e ditës): in each shift, which teachers keep watch
 * in the hall and on each floor on each school day. The posts (`duty_posts`)
 * are the school's own places; `places` says how many teachers keep each one.
 */
final class Duty extends Model
{
    /** The posts in order, with how many duty days use each (all years). */
    public static function posts(): array
    {
        return self::fetchAll(
            'SELECT p.id, p.name, p.places, p.sort_order,
                    (SELECT COUNT(*) FROM duty_assignments a WHERE a.duty_post_id = p.id) AS used
               FROM duty_posts p
              ORDER BY p.sort_order, p.name'
        );
    }

    public static function findPost(int $id): ?array
    {
        return self::fetchOne('SELECT id, name, places, sort_order FROM duty_posts WHERE id = ?', [$id]);
    }

    public static function postNameTaken(string $name, ?int $exceptId = null): bool
    {
        return self::fetchValue('SELECT 1 FROM duty_posts WHERE name = ? AND id <> ?', [$name, $exceptId ?? 0]) !== null;
    }

    public static function createPost(string $name, int $places): int
    {
        return self::insert(
            'INSERT INTO duty_posts (name, places, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order), 0) + 1 FROM duty_posts',
            [$name, $places]
        );
    }

    /** Rename a post or change its places; teachers in places that no longer exist are taken off. */
    public static function updatePost(int $id, string $name, int $places): void
    {
        self::execute('UPDATE duty_posts SET name = ?, places = ? WHERE id = ?', [$name, $places, $id]);
        self::execute('DELETE FROM duty_assignments WHERE duty_post_id = ? AND place > ?', [$id, $places]);
    }

    public static function deletePost(int $id): void
    {
        self::execute('DELETE FROM duty_posts WHERE id = ?', [$id]);
    }

    /**
     * A shift's duty in a year: [day => [post id => [place => teacher row]]],
     * each teacher row with id, names, title and timetable number.
     */
    public static function forShift(int $academicYearId, int $shift): array
    {
        $rows = self::fetchAll(
            'SELECT a.day_of_week, a.duty_post_id, a.place, u.id, u.first_name, u.last_name, tp.title, tp.timetable_number
               FROM duty_assignments a
               JOIN users u ON u.id = a.teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
              WHERE a.academic_year_id = ? AND a.shift = ?',
            [$academicYearId, $shift]
        );

        $grid = [];
        foreach ($rows as $row) {
            $grid[(int) $row['day_of_week']][(int) $row['duty_post_id']][(int) $row['place']] = $row;
        }

        return $grid;
    }

    /** A teacher's duty days in a year: [[shift, day, post name], …] in week order. */
    public static function forTeacher(int $teacherId, int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT a.shift, a.day_of_week AS day, p.name AS post
               FROM duty_assignments a
               JOIN duty_posts p ON p.id = a.duty_post_id
              WHERE a.teacher_id = ? AND a.academic_year_id = ?
              ORDER BY a.day_of_week, a.shift',
            [$teacherId, $academicYearId]
        );
    }

    /**
     * Replace a shift's duty for the year.
     *
     * @param array<int, array<int, array<int, int>>> $cells day => post id => place => teacher id
     */
    public static function replaceShift(int $academicYearId, int $shift, array $cells): void
    {
        self::execute('DELETE FROM duty_assignments WHERE academic_year_id = ? AND shift = ?', [$academicYearId, $shift]);

        foreach ($cells as $day => $posts) {
            foreach ($posts as $postId => $places) {
                foreach ($places as $place => $teacherId) {
                    self::run(
                        'INSERT INTO duty_assignments (academic_year_id, shift, day_of_week, duty_post_id, place, teacher_id)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [$academicYearId, $shift, $day, $postId, $place, $teacherId]
                    );
                }
            }
        }
    }

    /** Does the shift have any duty set for the year? */
    public static function hasShift(int $academicYearId, int $shift): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM duty_assignments WHERE academic_year_id = ? AND shift = ? LIMIT 1',
            [$academicYearId, $shift]
        ) !== null;
    }
}
