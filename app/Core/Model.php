<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeInterface;
use PDO;
use PDOStatement;

/**
 * Base class for models. ALL SQL in the application lives in subclasses of
 * this class and goes through run(), which always uses a prepared statement
 * with bound parameters — never string interpolation of values.
 *
 * Parameters may be positional (`?`) or named (`:email`):
 *   self::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
 *   self::fetchOne('SELECT * FROM users WHERE email = :email', ['email' => $email]);
 */
abstract class Model
{
    protected static function db(): PDO
    {
        return Database::connection();
    }

    protected static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = static::db()->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');

            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $value = (int) $value;
            }

            $type = match (true) {
                $value === null => PDO::PARAM_NULL,
                is_int($value)  => PDO::PARAM_INT,
                default         => PDO::PARAM_STR,
            };

            $statement->bindValue($parameter, $value, $type);
        }

        $statement->execute();

        return $statement;
    }

    /** First row, or null when there is none. */
    protected static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = static::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    protected static function fetchAll(string $sql, array $params = []): array
    {
        return static::run($sql, $params)->fetchAll();
    }

    /** First column of the first row, or null. */
    protected static function fetchValue(string $sql, array $params = []): mixed
    {
        $value = static::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** INSERT and return the new id. */
    protected static function insert(string $sql, array $params = []): int
    {
        static::run($sql, $params);

        return (int) static::db()->lastInsertId();
    }

    /** UPDATE/DELETE and return the number of affected rows. */
    protected static function execute(string $sql, array $params = []): int
    {
        return static::run($sql, $params)->rowCount();
    }
}
