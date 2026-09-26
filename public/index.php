<?php

declare(strict_types=1);

/*
 * Front controller: every page of the website and the LMS starts here.
 */

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\Authenticate;
use App\Middleware\EnsurePasswordChanged;
use App\Middleware\GuestOnly;
use App\Middleware\RequireRole;
use App\Middleware\VerifyCsrf;

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $router = new Router([
        'csrf'             => VerifyCsrf::class,        // added automatically to every POST route
        'auth'             => Authenticate::class,
        'guest'            => GuestOnly::class,
        'role'             => RequireRole::class,       // 'role:admin', 'role:teacher,admin'
        'password.changed' => EnsurePasswordChanged::class,
    ]);

    require ROOT_PATH . '/app/routes.php';

    $response = $router->dispatch(Request::capture());
} catch (Throwable $e) {
    $response = ErrorHandler::render($e);
}

// Pages seen while signed in must never be kept by the browser or a proxy
// (e.g. shown again with the Back button after signing out on a shared computer).
// Only the session is read here, so this also works when the database is down.
if (Session::has(Session::USER_KEY)) {
    $response->withHeader('Cache-Control', 'no-store');
}

$response->send();
