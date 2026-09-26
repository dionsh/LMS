<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Classes (paralelet), e.g. X/13. Named SchoolClass because "Class" is reserved in PHP.
 */
final class SchoolClass extends Model
{
    public static function countForYear(int $academicYearId): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM classes WHERE academic_year_id = ?', [$academicYearId]);
    }
}
