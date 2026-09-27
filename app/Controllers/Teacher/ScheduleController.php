<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Services\TimetableView;
use App\Support\Clock;

/**
 * /mesimdhenesi/orari — the teacher's week across all their classes and both
 * shifts. Read-only: the timetable belongs to the administration.
 */
final class ScheduleController extends PortalController
{
    /** GET /mesimdhenesi/orari */
    public function index(): Response
    {
        $year = AcademicYear::current();

        return $this->page('teacher/schedule', [
            'title'  => 'Orari',
            'styles' => ['timetable'],
            'year'   => $year,
            'view'   => TimetableView::forTeacher((int) Auth::id(), (int) ($year['id'] ?? 0), Clock::now()),
        ], 'schedule');
    }
}
