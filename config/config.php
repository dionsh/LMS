<?php

declare(strict_types=1);

/*
 * Default configuration.
 *
 * Machine-specific values (database credentials, environment) belong in
 * config.local.php, which overrides anything below and is never committed.
 * Copy config.local.example.php to get started.
 *
 * School information (name, address, texts…) is NOT configured here —
 * it lives in the `settings` table and is edited from the admin panel.
 */

$config = [
    'app' => [
        // 'production' hides error details; 'development' shows them and enables /_sistemi
        'env'       => 'production',
        // URL prefix of the app. null = auto-detect ('/lms-system' under XAMPP, '' at a domain root)
        'base_path' => null,
        // Kosovo
        'timezone'  => 'Europe/Belgrade',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'kuvendi_lms',
        'user'    => 'kuvendi_app',   // production: a dedicated user with rights on this database only
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name'             => 'kai_session',
        'idle_timeout'     => 3600,    // seconds of inactivity before a signed-in user is logged out
        'absolute_timeout' => 43200,   // maximum length of a signed-in session (12 hours)
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
