<?php

declare(strict_types=1);

/*
 * Prepares the application for both web requests (public/index.php) and
 * command-line scripts: autoloading, configuration, time zone, error
 * handling and the session.
 */

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Session;

define('ROOT_PATH', dirname(__DIR__));

// App\Foo\Bar → app/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require ROOT_PATH . '/app/Support/helpers.php';

Config::load(require ROOT_PATH . '/config/config.php');

date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Belgrade'));
mb_internal_encoding('UTF-8');

ErrorHandler::register();
Session::start();
