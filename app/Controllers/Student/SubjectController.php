<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\ScheduleEntry;
use App\Models\SchoolClass;

/**
 * /nxenesi/lendet — the student's subjects this year, who teaches each one,
 * and on which days.
 */
final class SubjectController extends PortalController
{
    /** GET /nxenesi/lendet */
    public function index(): Response
    {
        $year = AcademicYear::current();
        $class = $year !== null ? SchoolClass::forStudent((int) Auth::id(), (int) $year['id']) : null;

        $days = [];
        if ($class !== null) {
            foreach (ScheduleEntry::forClass((int) $class['id']) as $entry) {
                $days[(int) $entry['class_subject_id']][(int) $entry['day']] = true;
            }
        }

        return $this->page('student/subjects', [
            'title'    => 'Lëndët',
            'class'    => $class,
            'subjects' => $class !== null ? ClassSubject::forClass((int) $class['id']) : [],
            'days'     => $days,
        ], 'subjects');
    }
}
