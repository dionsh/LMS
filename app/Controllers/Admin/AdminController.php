<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Support\Format;

/**
 * Base for the admin area. Lists and forms work on the current school year.
 */
abstract class AdminController extends PortalController
{
    protected function yearId(): int
    {
        return (int) (AcademicYear::current()['id'] ?? 0);
    }

    /** Classes of the current year, grouped for <optgroup>: ['X' => [[id, label], …], …] */
    protected function classOptions(): array
    {
        $groups = [];
        foreach (SchoolClass::optionsForYear($this->yearId()) as $class) {
            $groups[Format::grade((int) $class['grade_level'])][] = [
                'id'    => (int) $class['id'],
                'label' => Format::classLabel((int) $class['grade_level'], (int) $class['section']),
            ];
        }

        return $groups;
    }
}
