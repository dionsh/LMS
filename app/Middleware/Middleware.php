<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Runs before a controller. Return a Response to stop the request
 * (e.g. a redirect to the login page), or null to continue.
 * Arguments come from the route spec: 'role:admin,teacher' → handle($request, 'admin', 'teacher').
 */
interface Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response;
}
