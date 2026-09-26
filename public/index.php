<?php

declare(strict_types=1);

/*
 * Front controller: every page of the website and the LMS starts here.
 */

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;
use App\Middleware\VerifyCsrf;

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $router = new Router([
        'csrf' => VerifyCsrf::class,
    ]);

    require ROOT_PATH . '/app/routes.php';

    $response = $router->dispatch(Request::capture());
} catch (Throwable $e) {
    $response = ErrorHandler::render($e);
}

$response->send();
