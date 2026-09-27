<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AcademicYear extends Model
{
    private static ?array $current = null;
    private static bool $loaded = false;

    /** The year marked as current, e.g. ['id' => 1, 'name' => '2026/2027', …], or null. */
    public static function current(): ?array
    {
        if (!self::$loaded) {
            self::$current = self::fetchOne(
                'SELECT id, name, starts_on, ends_on FROM academic_years WHERE is_current = 1 LIMIT 1'
            );
            self::$loaded = true;
        }

        return self::$current;
    }

    /** Every year, newest first, with its number of classes and students. */
    public static function overview(): array
    {
        return self::fetchAll(
            'SELECT y.id, y.name, y.starts_on, y.ends_on, y.is_current,
                    (SELECT COUNT(*) FROM classes c WHERE c.academic_year_id = y.id) AS classes,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.academic_year_id = y.id) AS students
               FROM academic_years y
              ORDER BY y.starts_on DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT id, name, starts_on, ends_on, is_current FROM academic_years WHERE id = ?', [$id]);
    }

    public static function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return self::fetchValue('SELECT 1 FROM academic_years WHERE name = ? AND id <> ?', [$name, $exceptId ?? 0]) !== null;
    }

    public static function create(string $name, string $startsOn, string $endsOn): int
    {
        return self::insert(
            'INSERT INTO academic_years (name, starts_on, ends_on, is_current) VALUES (?, ?, ?, 0)',
            [$name, $startsOn, $endsOn]
        );
    }

    public static function update(int $id, string $name, string $startsOn, string $endsOn): void
    {
        self::execute(
            'UPDATE academic_years SET name = ?, starts_on = ?, ends_on = ? WHERE id = ?',
            [$name, $startsOn, $endsOn, $id]
        );
    }

    /** Make this the one current year. Call inside a transaction. */
    public static function makeCurrent(int $id): void
    {
        self::execute('UPDATE academic_years SET is_current = (id = ?)', [$id]);
        self::$loaded = false;
    }
}
