<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\User;

final class StudentController extends PortalController
{
    /** GET /admin/nxenesit — every student and their class this year (read-only for now) */
    public function index(): Response
    {
        $year = AcademicYear::current();

        return $this->page('admin/students', [
            'title'    => 'Nxënësit',
            'year'     => $year,
            'students' => $year !== null ? User::studentsOverview((int) $year['id']) : [],
        ], 'students');
    }
}
