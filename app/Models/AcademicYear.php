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
}
