<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * The single PDO connection, created on first use.
 *
 * Every connection is configured identically:
 * - exceptions on error, associative arrays, real (non-emulated) prepared statements;
 * - utf8mb4 with the same collation as the tables;
 * - MySQL's time zone aligned with PHP's (Europe/Belgrade, DST-aware), so NOW() and date() agree;
 * - strict SQL mode, so invalid or too-long values are rejected instead of silently truncated.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private const SQL_MODE = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                Config::get('db.host'),
                (int) Config::get('db.port', 3306),
                Config::get('db.name'),
                Config::get('db.charset', 'utf8mb4'),
            );

            $pdo = new PDO($dsn, (string) Config::get('db.user'), (string) Config::get('db.pass'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);

            $offset = (new DateTimeImmutable())->format('P'); // e.g. +02:00
            $pdo->exec(sprintf(
                "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = %s, sql_mode = %s",
                $pdo->quote($offset),
                $pdo->quote(self::SQL_MODE),
            ));

            self::$pdo = $pdo;
        }

        return self::$pdo;
    }

    /**
     * Run $callback inside a transaction; commits on success, rolls back on any error.
     * Called while a transaction is already open, it runs as part of that one.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
