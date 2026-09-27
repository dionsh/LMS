<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\Notification;
use App\Models\ScheduleEntry;
use App\Models\SchoolClass;
use App\Services\TimetableView;
use App\Support\Clock;

/**
 * /nxenesi/orari — the student's class timetable: today and the whole week,
 * with the teacher of every lesson. The class stays in its room; a room is
 * shown only for lessons held elsewhere.
 */
final class ScheduleController extends PortalController
{
    /** GET /nxenesi/orari */
    public function index(): Response
    {
        $studentId = (int) Auth::id();
        $year = AcademicYear::current();
        $class = $year !== null ? SchoolClass::forStudent($studentId, (int) $year['id']) : null;

        return $this->page('student/schedule', [
            'title'       => 'Orari',
            'styles'      => ['timetable'],
            'class'       => $class,
            'view'        => $class !== null ? TimetableView::forClass($class, Clock::now()) : null,
            'changes'     => $class !== null ? Notification::unreadOfType($studentId, 'schedule.changed') : [],
            'lastChanged' => $class !== null ? ScheduleEntry::lastChanged((int) $year['id'], null, (int) $class['id']) : null,
        ], 'schedule');
    }

    /** POST /nxenesi/orari/lexuar — the student has seen that the timetable changed */
    public function dismiss(): Response
    {
        Notification::markTypeRead((int) Auth::id(), 'schedule.changed');

        return redirect('/nxenesi/orari');
    }
}
