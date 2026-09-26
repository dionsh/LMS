<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\LessonPeriod;
use App\Models\SchoolClass;
use App\Models\User;

final class DashboardController extends PortalController
{
    /** GET /admin — live numbers and the school's setup checklist */
    public function index(): Response
    {
        $year = AcademicYear::current();
        $users = User::countActiveByRole();
        $classes = $year !== null ? SchoolClass::countForYear((int) $year['id']) : 0;
        $withHomeroom = $year !== null ? SchoolClass::countWithHomeroom((int) $year['id']) : 0;
        $periods = count(LessonPeriod::forShift(1)) + count(LessonPeriod::forShift(2));

        return $this->page('admin/dashboard', [
            'title'              => 'Paneli',
            'year'               => $year,
            'users'              => $users,
            'classes'            => $classes,
            'neverSigned'        => User::countNeverSignedIn(),
            'withoutCredentials' => User::countWithoutCredentials(),
            'setup'              => [
                ['Viti shkollor', $year !== null ? $year['name'] : 'Mungon', $year !== null],
                ['Orari i orëve', $periods . ' orë në dy ndërrime', $periods > 0],
                ['Mësimdhënësit', $users['teacher'] . ' mësimdhënës', $users['teacher'] > 0],
                ['Klasat', $classes . ' klasa', $classes > 0],
                ['Kujdestarët e klasave', $withHomeroom . ' nga ' . $classes . ' klasa', $classes > 0 && $withHomeroom === $classes],
                ['Nxënësit', $users['student'] . ' nxënës', $users['student'] > 0],
            ],
        ], 'dashboard');
    }
}
