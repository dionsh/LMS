<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Grading periods of a school year: the two semesters (gjysmëvjetorët).
 */
final class Term extends Model
{
    /** Terms of every year: [year id => [[id, name, sort_order, starts_on, ends_on], …]] */
    public static function byYear(): array
    {
        $byYear = [];
        foreach (self::fetchAll('SELECT id, academic_year_id, name, sort_order, starts_on, ends_on FROM terms ORDER BY academic_year_id, sort_order') as $term) {
            $byYear[(int) $term['academic_year_id']][] = $term;
        }

        return $byYear;
    }

    /** The year's terms in order. */
    public static function forYear(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT id, name, sort_order, starts_on, ends_on FROM terms WHERE academic_year_id = ? ORDER BY sort_order',
            [$academicYearId]
        );
    }

    public static function create(int $academicYearId, string $name, int $sortOrder, string $startsOn, string $endsOn): void
    {
        self::run(
            'INSERT INTO terms (academic_year_id, name, sort_order, starts_on, ends_on) VALUES (?, ?, ?, ?, ?)',
            [$academicYearId, $name, $sortOrder, $startsOn, $endsOn]
        );
    }

    public static function updateDates(int $academicYearId, int $sortOrder, string $startsOn, string $endsOn): void
    {
        self::execute(
            'UPDATE terms SET starts_on = ?, ends_on = ? WHERE academic_year_id = ? AND sort_order = ?',
            [$startsOn, $endsOn, $academicYearId, $sortOrder]
        );
    }
}
