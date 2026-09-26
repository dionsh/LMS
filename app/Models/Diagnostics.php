<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Read-only facts about the database connection, for the development
 * system-check page (/_sistemi).
 */
final class Diagnostics extends Model
{
    /** @return array{name: string, version: string, now: string, time_zone: string, sql_mode: string, charset: string, collation: string, tables: int} */
    public static function database(): array
    {
        $info = self::fetchOne(
            'SELECT DATABASE()                  AS name,
                    VERSION()                   AS version,
                    NOW()                       AS now,
                    @@session.time_zone         AS time_zone,
                    @@session.sql_mode          AS sql_mode,
                    @@character_set_connection  AS charset,
                    @@collation_connection      AS collation'
        ) ?? [];

        $info['tables'] = (int) self::fetchValue(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        );

        return $info;
    }
}
