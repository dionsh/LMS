<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Services\TimetableView;
use App\Support\Clock;

final class DashboardController extends PortalController
{
    /** GET /mesimdhenesi — today's lessons now; the full dashboard is built on live data in T15 */
    public function index(): Response
    {
        $year = AcademicYear::current();

        return $this->page('teacher/dashboard', [
            'title' => 'Paneli',
            'view'  => TimetableView::forTeacher((int) Auth::id(), (int) ($year['id'] ?? 0), Clock::now()),
            'now'   => Clock::now(),
        ], 'dashboard');
    }
}
