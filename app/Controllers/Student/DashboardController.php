<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Services\TimetableView;
use App\Support\Clock;

final class DashboardController extends PortalController
{
    /** GET /nxenesi — today's lessons now; the full dashboard is built on live data in T15 */
    public function index(): Response
    {
        $year = AcademicYear::current();
        $class = $year !== null ? SchoolClass::forStudent((int) Auth::id(), (int) $year['id']) : null;

        return $this->page('student/dashboard', [
            'title' => 'Paneli',
            'class' => $class,
            'view'  => $class !== null ? TimetableView::forClass($class, Clock::now()) : null,
            'now'   => Clock::now(),
        ], 'dashboard');
    }
}
