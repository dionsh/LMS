<?php

declare(strict_types=1);

/*
 * Every URL of the application, in one place.
 *
 * - URLs are Albanian without diacritics; controllers are English.
 * - Role areas (/nxenesi, /mesimdhenesi, /admin) are route GROUPS whose
 *   middleware guards every page inside them (added in T04).
 * - All POST routes are CSRF-protected automatically by the router.
 */

use App\Controllers\Dev\SystemController;
use App\Controllers\Site\HomeController;
use App\Core\Router;

/** @var Router $router */

// ---------------------------------------------------------------------
// Public website
// ---------------------------------------------------------------------
$router->get('/', [HomeController::class, 'index'], 'home');

// ---------------------------------------------------------------------
// Development tools — never registered in production
// ---------------------------------------------------------------------
if (config('app.env') === 'development') {
    $router->get('/_sistemi', [SystemController::class, 'index'], 'dev.system');
    $router->post('/_sistemi/csrf', [SystemController::class, 'csrfCheck'], 'dev.csrf');
}
