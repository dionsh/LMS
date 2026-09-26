<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Read-only access to config/config.php (+ config.local.php).
 */
final class Config
{
    private static array $items = [];
    private static ?string $basePath = null;

    public static function load(array $items): void
    {
        self::$items = $items;
        self::$basePath = null;
    }

    /** Dot notation: Config::get('db.host'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function isDevelopment(): bool
    {
        return self::get('app.env') === 'development';
    }

    /**
     * URL prefix of the application without a trailing slash:
     * '/lms-system' when served from the XAMPP subfolder, '' at a domain root.
     */
    public static function basePath(): string
    {
        if (self::$basePath === null) {
            $configured = self::get('app.base_path');
            self::$basePath = is_string($configured)
                ? rtrim($configured, '/')
                : self::detectBasePath();
        }

        return self::$basePath;
    }

    private static function detectBasePath(): string
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }

        // SCRIPT_NAME is e.g. /lms-system/public/index.php (XAMPP) or /index.php (docroot = public/)
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(dirname($script), '/');

        if (str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -strlen('/public'));
        }

        return $dir;
    }
}
