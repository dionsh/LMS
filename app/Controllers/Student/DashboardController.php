<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\SchoolClass;

final class DashboardController extends PortalController
{
    /** GET /nxenesi — the full dashboard is built on live data in T15 */
    public function index(): Response
    {
        $year = AcademicYear::current();

        return $this->page('student/dashboard', [
            'title' => 'Paneli',
            'class' => $year !== null ? SchoolClass::forStudent((int) Auth::id(), (int) $year['id']) : null,
        ], 'dashboard');
    }
}
