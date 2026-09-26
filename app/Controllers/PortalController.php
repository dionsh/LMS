<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;

/**
 * Base for pages inside the portal (student, teacher, admin, profile).
 * Renders with the portal layout and the signed-in user.
 */
abstract class PortalController extends Controller
{
    /**
     * @param string|null $active navigation key to highlight (null = derived from the URL)
     */
    protected function page(string $view, array $data = [], ?string $active = null, int $status = 200): Response
    {
        return $this->view($view, $data + ['user' => Auth::user(), 'active' => $active], 'portal', $status);
    }

    /** The signed-in user (routes using this base are always behind 'auth'). */
    protected function user(): array
    {
        return Auth::user() ?? [];
    }
}
