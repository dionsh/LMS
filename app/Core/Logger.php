<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Appends one line per event to storage/logs/app-YYYY-MM-DD.log.
 * Never log passwords, tokens or other secrets.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            str_replace(["\r", "\n"], ' ', $message),
            $context === [] ? '' : ' ' . json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
            ),
        );

        $file = ROOT_PATH . '/storage/logs/app-' . date('Y-m-d') . '.log';

        // Logging must never break the page it is reporting on.
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
