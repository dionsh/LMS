<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/**
 * Applied automatically by the router to every non-GET route.
 */
final class VerifyCsrf implements Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response
    {
        // An oversized upload empties $_POST — report that instead of a misleading "session expired".
        if ($request->exceedsPostMaxSize()) {
            throw new HttpException(413);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

        if (!Csrf::isValid($token)) {
            throw new HttpException(419);
        }

        return null;
    }
}
