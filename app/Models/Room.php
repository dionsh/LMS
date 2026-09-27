<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Rooms (sallat). Each class has its own room, where its lessons are held;
 * a timetable slot names a room only when the lesson is somewhere else
 * (e.g. the gym or a laboratory).
 */
final class Room extends Model
{
    /** Every room with the number of timetable lessons held there instead of in a class's own room. */
    public static function overview(): array
    {
        return self::fetchAll(
            'SELECT r.id, r.name, r.capacity, r.is_active,
                    (SELECT COUNT(*) FROM schedule_entries se WHERE se.room_id = r.id) AS lessons
               FROM rooms r
              ORDER BY r.is_active DESC, r.name'
        );
    }

    /** Rooms for <select>s: [[id, name, is_active], …] */
    public static function options(): array
    {
        return self::fetchAll('SELECT id, name, is_active FROM rooms ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT id, name, capacity, is_active FROM rooms WHERE id = ?', [$id]);
    }

    public static function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return self::fetchValue('SELECT 1 FROM rooms WHERE name = ? AND id <> ?', [$name, $exceptId ?? 0]) !== null;
    }

    public static function create(string $name, ?int $capacity): int
    {
        return self::insert('INSERT INTO rooms (name, capacity) VALUES (?, ?)', [$name, $capacity]);
    }

    public static function update(int $id, string $name, ?int $capacity, bool $active): void
    {
        self::execute('UPDATE rooms SET name = ?, capacity = ?, is_active = ? WHERE id = ?', [$name, $capacity, $active, $id]);
    }

    /** Is the room some class's home or named in the timetable? Then deactivate it instead of deleting. */
    public static function isUsed(int $id): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM classes WHERE home_room_id = ? UNION SELECT 1 FROM schedule_entries WHERE room_id = ? LIMIT 1',
            [$id, $id]
        ) !== null;
    }

    public static function delete(int $id): void
    {
        self::execute('DELETE FROM rooms WHERE id = ?', [$id]);
    }
}
