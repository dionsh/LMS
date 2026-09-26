<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\SchoolClass;

final class ClassController extends PortalController
{
    /** GET /admin/klasat — every class of the current year, grouped by grade (read-only for now) */
    public function index(): Response
    {
        $year = AcademicYear::current();
        $byGrade = [];

        foreach ($year !== null ? SchoolClass::overview((int) $year['id']) : [] as $class) {
            $byGrade[(int) $class['grade_level']][] = $class;
        }

        return $this->page('admin/classes', ['title' => 'Klasat', 'year' => $year, 'byGrade' => $byGrade], 'classes');
    }
}
