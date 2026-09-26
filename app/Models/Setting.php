<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * School information and platform switches (the `settings` table).
 * All rows are loaded once per request.
 */
final class Setting extends Model
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = self::fetchAll('SELECT setting_key, setting_value FROM settings');
            self::$cache = array_column($rows, 'setting_value', 'setting_key');
        }

        return self::$cache;
    }

    /** The value, or $default when the setting is missing or empty. */
    public static function get(string $key, string $default = ''): string
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }
}
