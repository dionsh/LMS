<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\PortalController;
use App\Core\Response;

final class DashboardController extends PortalController
{
    /** GET /nxenesi — the full dashboard is built on live data in T15 */
    public function index(): Response
    {
        return $this->page('student/dashboard', ['title' => 'Paneli'], 'dashboard');
    }
}
