<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * 'guest' — pages for signed-out visitors only (the sign-in page).
 * A signed-in user is taken to their dashboard instead.
 */
final class GuestOnly implements Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response
    {
        return Auth::check() ? redirect(Auth::homePath()) : null;
    }
}
