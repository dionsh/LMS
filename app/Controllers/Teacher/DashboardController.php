<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\PortalController;
use App\Core\Response;

final class DashboardController extends PortalController
{
    /** GET /mesimdhenesi — the full dashboard is built on live data in T15 */
    public function index(): Response
    {
        return $this->page('teacher/dashboard', ['title' => 'Paneli'], 'dashboard');
    }
}
