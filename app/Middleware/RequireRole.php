<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/**
 * 'role:admin' or 'role:teacher,admin' — only these roles may open the page.
 * Anyone else gets 403, whatever URL they type. Always used after 'auth'.
 */
final class RequireRole implements Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response
    {
        if (!Auth::hasRole(...$arguments)) {
            throw new HttpException(403);
        }

        return null;
    }
}
