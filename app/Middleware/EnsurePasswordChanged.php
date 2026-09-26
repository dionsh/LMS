<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * 'password.changed' — while an account still uses the temporary password
 * issued by the school, every portal page leads to /ndrysho-fjalekalimin.
 */
final class EnsurePasswordChanged implements Middleware
{
    public function handle(Request $request, string ...$arguments): ?Response
    {
        return (int) (Auth::user()['must_change_password'] ?? 0) === 1
            ? redirect('/ndrysho-fjalekalimin')
            : null;
    }
}
