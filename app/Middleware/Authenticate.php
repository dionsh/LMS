<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * 'auth' — the page requires a signed-in user. Guests are sent to /hyr and,
 * after signing in, brought back to the page they asked for.
 */
final class Authenticate implements Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        if ($request->isGet()) {
            $query = $request->queryString();
            Session::set('_intended', $request->path . ($query !== '' ? '?' . $query : ''));
        }

        return redirect('/hyr');
    }
}
