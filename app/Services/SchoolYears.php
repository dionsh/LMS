<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Term;

/**
 * School years and their two semesters. Exactly one year is current: the
 * portal shows its classes, timetables and marks.
 */
final class SchoolYears
{
    /** @param array $values validated YearForm values */
    public static function create(array $values, int $adminId): int
    {
        return Database::transaction(static function () use ($values, $adminId): int {
            $id = AcademicYear::create($values['name'], $values['starts_on'], $values['ends_on']);

            foreach (YearForm::terms($values) as $order => [$startsOn, $endsOn]) {
                Term::create($id, YearForm::TERM_NAMES[$order], $order, $startsOn, $endsOn);
            }

            ActivityLog::record($adminId, 'year.created', 'Shtoi vitin shkollor ' . $values['name'] . '.', 'year', $id);

            return $id;
        });
    }

    public static function update(array $year, array $values, int $adminId): void
    {
        Database::transaction(static function () use ($year, $values, $adminId): void {
            $id = (int) $year['id'];
            AcademicYear::update($id, $values['name'], $values['starts_on'], $values['ends_on']);

            foreach (YearForm::terms($values) as $order => [$startsOn, $endsOn]) {
                Term::updateDates($id, $order, $startsOn, $endsOn);
            }

            ActivityLog::record($adminId, 'year.updated', 'Ndryshoi datat e vitit shkollor ' . $values['name'] . '.', 'year', $id);
        });
    }

    public static function makeCurrent(array $year, int $adminId): void
    {
        Database::transaction(static function () use ($year, $adminId): void {
            AcademicYear::makeCurrent((int) $year['id']);
            ActivityLog::record($adminId, 'year.current', 'E bëri vitin shkollor ' . $year['name'] . ' vit aktual.', 'year', (int) $year['id']);
        });
    }
}
