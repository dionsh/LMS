<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Base controller. Keep controllers thin: validate input, call models or
 * services, render a view or redirect.
 */
abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    /** Render app/Views/{$view}.php inside app/Views/layouts/{$layout}.php. */
    protected function view(string $view, array $data = [], string $layout = 'site', int $status = 200): Response
    {
        return Response::html(View::render($view, $data, $layout), $status);
    }
}
