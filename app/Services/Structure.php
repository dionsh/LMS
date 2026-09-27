<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\ClassSubject;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\Format;

/**
 * Changes to the school's structure from the admin panel: grades and their
 * curriculum, subjects, classes and who teaches what, rooms. Every change is
 * one transaction and leaves a line in the activity log.
 *
 * The curriculum drives the classes: a new class gets its grade's subjects,
 * a subject added to a grade is added to that grade's classes, and a subject
 * taken out of a grade leaves its classes unless something already depends on it.
 */
final class Structure
{
    // ------------------------------------------------------------------ grades & curriculum

    public static function createGrade(int $level, int $shift, int $adminId): void
    {
        Database::transaction(static function () use ($level, $shift, $adminId): void {
            GradeLevel::create($level, $shift);
            ActivityLog::record($adminId, 'grade.created', 'Shtoi klasën ' . Format::grade($level) . ' në planin mësimor.', 'grade', $level);
        });
    }

    public static function deleteGrade(int $level, int $adminId): void
    {
        Database::transaction(static function () use ($level, $adminId): void {
            GradeLevel::delete($level);
            ActivityLog::record($adminId, 'grade.deleted', 'Hoqi klasën ' . Format::grade($level) . ' nga plani mësimor.', 'grade', $level);
        });
    }

    /**
     * Save a grade's curriculum and default shift, then bring the grade's
     * classes of the year in line with it.
     *
     * @param array<int, ?int> $hoursBySubject subject_id => weekly hours
     * @return array{added: int, removed: int, moved: int}
     */
    public static function saveCurriculum(int $level, int $shift, bool $moveClasses, array $hoursBySubject, int $academicYearId, int $adminId): array
    {
        return Database::transaction(static function () use ($level, $shift, $moveClasses, $hoursBySubject, $academicYearId, $adminId): array {
            $before = array_map('intval', array_column(Curriculum::forGrade($level), 'subject_id'));

            GradeLevel::updateShift($level, $shift);
            Curriculum::saveGrade($level, $hoursBySubject);

            $added = ClassSubject::addFromCurriculum($academicYearId, $level);
            $removed = ClassSubject::removeUnused($academicYearId, $level, array_values(array_diff($before, array_keys($hoursBySubject))));
            $moved = $moveClasses ? SchoolClass::setShiftForGrade($academicYearId, $level, $shift) : 0;

            ActivityLog::record($adminId, 'curriculum.updated', 'Ndryshoi planin mësimor të klasës ' . Format::grade($level) . '.', 'grade', $level);

            return ['added' => $added, 'removed' => $removed, 'moved' => $moved];
        });
    }

    // ------------------------------------------------------------------ subjects

    /** @param array<int, ?int> $curriculum grade level => weekly hours */
    public static function createSubject(array $data, array $curriculum, int $academicYearId, int $adminId): int
    {
        return Database::transaction(static function () use ($data, $curriculum, $academicYearId, $adminId): int {
            $id = Subject::create($data);
            Curriculum::saveSubject($id, $curriculum);

            foreach (array_keys($curriculum) as $level) {
                ClassSubject::addFromCurriculum($academicYearId, $level);
            }

            ActivityLog::record($adminId, 'subject.created', 'Shtoi lëndën ' . $data['name'] . '.', 'subject', $id);

            return $id;
        });
    }

    /** @param array<int, ?int> $curriculum grade level => weekly hours */
    public static function updateSubject(array $subject, array $data, array $curriculum, int $academicYearId, int $adminId): void
    {
        Database::transaction(static function () use ($subject, $data, $curriculum, $academicYearId, $adminId): void {
            $id = (int) $subject['id'];
            $before = array_keys(Curriculum::forSubject($id));

            Subject::update($id, $data);
            Curriculum::saveSubject($id, $curriculum);

            foreach (array_keys($curriculum) as $level) {
                ClassSubject::addFromCurriculum($academicYearId, $level);
            }
            foreach (array_diff($before, array_keys($curriculum)) as $level) {
                ClassSubject::removeUnused($academicYearId, $level, [$id]);
            }

            ActivityLog::record($adminId, 'subject.updated', 'Ndryshoi lëndën ' . $data['name'] . '.', 'subject', $id);
        });
    }

    public static function deleteSubject(array $subject, int $adminId): void
    {
        Database::transaction(static function () use ($subject, $adminId): void {
            Subject::delete((int) $subject['id']);
            ActivityLog::record($adminId, 'subject.deleted', 'Fshiu lëndën ' . $subject['name'] . '.', 'subject', (int) $subject['id']);
        });
    }

    // ------------------------------------------------------------------ classes

    /** Create a class and give it its grade's subjects. */
    public static function createClass(array $data, int $adminId): int
    {
        return Database::transaction(static function () use ($data, $adminId): int {
            $id = SchoolClass::create($data);
            ClassSubject::addFromCurriculum($data['academic_year_id'], null, $id);

            ActivityLog::record($adminId, 'class.created', 'Shtoi klasën ' . Format::classLabel($data['grade_level'], $data['section']) . '.', 'class', $id);

            return $id;
        });
    }

    public static function updateClass(array $class, array $data, int $adminId): void
    {
        Database::transaction(static function () use ($class, $data, $adminId): void {
            SchoolClass::update((int) $class['id'], $data);
            ActivityLog::record($adminId, 'class.updated', 'Ndryshoi klasën ' . Format::classLabel((int) $class['grade_level'], $data['section']) . '.', 'class', (int) $class['id']);
        });
    }

    public static function deleteClass(array $class, int $adminId): void
    {
        Database::transaction(static function () use ($class, $adminId): void {
            SchoolClass::delete((int) $class['id']);
            ActivityLog::record($adminId, 'class.deleted', 'Fshiu klasën ' . self::label($class) . '.', 'class', (int) $class['id']);
        });
    }

    /**
     * Save who teaches each subject of a class, the class's own weekly hours, and
     * which elective it takes (subject_id: the new subject, null = unchanged).
     *
     * @param array<int, array{subject_id: ?int, teacher_id: ?int, weekly_hours: ?int}> $rows class-subject id => values
     */
    public static function saveClassSubjects(array $class, array $rows, int $adminId): void
    {
        Database::transaction(static function () use ($class, $rows, $adminId): void {
            foreach ($rows as $id => $row) {
                if (($row['subject_id'] ?? null) !== null) {
                    ClassSubject::setSubject($id, $row['subject_id']);
                }
                ClassSubject::update($id, $row['teacher_id'], $row['weekly_hours']);
            }

            ActivityLog::record($adminId, 'class.subjects_updated', 'Caktoi lëndët dhe mësimdhënësit e klasës ' . self::label($class) . '.', 'class', (int) $class['id']);
        });
    }

    public static function addClassSubject(array $class, array $subject, int $adminId): void
    {
        Database::transaction(static function () use ($class, $subject, $adminId): void {
            ClassSubject::add((int) $class['id'], (int) $subject['id']);
            ActivityLog::record($adminId, 'class.subject_added', 'Shtoi lëndën ' . $subject['name'] . ' te klasa ' . self::label($class) . '.', 'class', (int) $class['id']);
        });
    }

    public static function removeClassSubject(array $class, array $classSubject, int $adminId): void
    {
        Database::transaction(static function () use ($class, $classSubject, $adminId): void {
            ClassSubject::delete((int) $classSubject['id']);
            ActivityLog::record($adminId, 'class.subject_removed', 'Hoqi lëndën ' . $classSubject['subject_name'] . ' nga klasa ' . self::label($class) . '.', 'class', (int) $class['id']);
        });
    }

    // ------------------------------------------------------------------ rooms

    public static function createRoom(string $name, ?int $capacity, int $adminId): int
    {
        return Database::transaction(static function () use ($name, $capacity, $adminId): int {
            $id = Room::create($name, $capacity);
            ActivityLog::record($adminId, 'room.created', 'Shtoi sallën ' . $name . '.', 'room', $id);

            return $id;
        });
    }

    public static function updateRoom(array $room, string $name, ?int $capacity, bool $active, int $adminId): void
    {
        Database::transaction(static function () use ($room, $name, $capacity, $active, $adminId): void {
            Room::update((int) $room['id'], $name, $capacity, $active);
            ActivityLog::record($adminId, 'room.updated', 'Ndryshoi sallën ' . $name . '.', 'room', (int) $room['id']);
        });
    }

    public static function deleteRoom(array $room, int $adminId): void
    {
        Database::transaction(static function () use ($room, $adminId): void {
            Room::delete((int) $room['id']);
            ActivityLog::record($adminId, 'room.deleted', 'Fshiu sallën ' . $room['name'] . '.', 'room', (int) $room['id']);
        });
    }

    private static function label(array $class): string
    {
        return Format::classLabel((int) $class['grade_level'], (int) $class['section']);
    }
}
