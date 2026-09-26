<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\User;

final class TeacherController extends PortalController
{
    /** GET /admin/mesimdhenesit — every teacher and the class they are homeroom teacher of (read-only for now) */
    public function index(): Response
    {
        $year = AcademicYear::current();

        return $this->page('admin/teachers', [
            'title'    => 'Mësimdhënësit',
            'year'     => $year,
            'teachers' => $year !== null ? User::teachersOverview((int) $year['id']) : [],
        ], 'teachers');
    }
}
