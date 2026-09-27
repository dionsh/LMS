<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeLevel;
use App\Models\LessonPeriod;
use App\Models\SchoolClass;
use App\Models\User;

final class DashboardController extends PortalController
{
    /** GET /admin — live numbers and the school's setup checklist */
    public function index(): Response
    {
        $year = AcademicYear::current();
        $yearId = (int) ($year['id'] ?? 0);
        $users = User::countActiveByRole();
        $classes = $year !== null ? SchoolClass::countForYear($yearId) : 0;
        $withHomeroom = $year !== null ? SchoolClass::countWithHomeroom($yearId) : 0;
        $periods = count(LessonPeriod::forShift(1)) + count(LessonPeriod::forShift(2));
        $grades = GradeLevel::overview($yearId);
        $gradesReady = count(array_filter($grades, static fn (array $g): bool => (int) $g['subjects'] > 0 && (int) $g['missing_hours'] === 0));
        [$subjects, $assigned] = ClassSubject::assignmentCounts($yearId);

        return $this->page('admin/dashboard', [
            'title'              => 'Paneli',
            'year'               => $year,
            'users'              => $users,
            'classes'            => $classes,
            'neverSigned'        => User::countNeverSignedIn(),
            'withoutCredentials' => User::countWithoutCredentials(),
            'setup'              => [
                ['Viti shkollor', $year !== null ? $year['name'] : 'Mungon', $year !== null, '/admin/vitet-shkollore'],
                ['Orari i orëve', $periods . ' orë në dy ndërrime', $periods > 0, null],
                ['Plani mësimor', $gradesReady . ' nga ' . count($grades) . ' klasa me lëndë dhe orë', $grades !== [] && $gradesReady === count($grades), '/admin/plani-mesimor'],
                ['Mësimdhënësit', $users['teacher'] . ' mësimdhënës', $users['teacher'] > 0, '/admin/mesimdhenesit'],
                ['Klasat', $classes . ' klasa', $classes > 0, '/admin/klasat'],
                ['Kujdestarët e klasave', $withHomeroom . ' nga ' . $classes . ' klasa', $classes > 0 && $withHomeroom === $classes, '/admin/klasat'],
                ['Lëndët me mësimdhënës', $assigned . ' nga ' . $subjects . ' lëndë në klasa', $subjects > 0 && $assigned === $subjects, '/admin/klasat'],
                ['Nxënësit', $users['student'] . ' nxënës', $users['student'] > 0, '/admin/nxenesit'],
            ],
        ], 'dashboard');
    }
}
