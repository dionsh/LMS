<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\Format;

/**
 * Reads and validates the admin's add/edit class form.
 */
final class ClassForm
{
    public static function read(Request $request): array
    {
        return [
            'grade_level'         => $request->string('grade_level'),
            'section'             => $request->string('section'),
            'shift'               => $request->string('shift'),
            'stream'              => $request->string('stream'),
            'homeroom_teacher_id' => $request->string('homeroom_teacher_id'),
            'home_room_id'        => $request->string('home_room_id'),
        ];
    }

    /** A new class of a grade: the next free section, the grade's shift. */
    public static function blank(int $gradeLevel, int $section, int $shift): array
    {
        return [
            'grade_level' => (string) $gradeLevel, 'section' => (string) $section, 'shift' => (string) $shift,
            'stream' => '', 'homeroom_teacher_id' => '', 'home_room_id' => '',
        ];
    }

    public static function fromClass(array $class): array
    {
        return [
            'grade_level'         => (string) $class['grade_level'],
            'section'             => (string) $class['section'],
            'shift'               => (string) $class['shift'],
            'stream'              => (string) $class['stream'],
            'homeroom_teacher_id' => $class['homeroom_teacher_id'] !== null ? (string) $class['homeroom_teacher_id'] : '',
            'home_room_id'        => $class['home_room_id'] !== null ? (string) $class['home_room_id'] : '',
        ];
    }

    /**
     * @param array|null $class the class being edited (its grade cannot change), null when adding
     * @return array<string, string> errors by field
     */
    public static function validate(array $values, int $academicYearId, ?array $class): array
    {
        $v = new Validator($values);
        $grade = $class !== null ? (int) $class['grade_level'] : (ctype_digit($values['grade_level']) ? (int) $values['grade_level'] : 0);
        $section = ctype_digit($values['section']) ? (int) $values['section'] : 0;
        $classId = $class !== null ? (int) $class['id'] : null;

        if ($class === null) {
            $v->rule('grade_level', GradeLevel::find($grade) !== null, 'Zgjidhni klasën (X, XI, XII…) nga lista.');
        }

        $v->required('section', 'Shkruani numrin e paraleles.')
          ->rule('section', $section >= 1 && $section <= 30, 'Numri i paraleles duhet të jetë nga 1 deri në 30.')
          ->rule('section', $grade === 0 || !SchoolClass::sectionTaken($academicYearId, $grade, $section, $classId),
                 'Klasa ' . Format::classLabel($grade, $section) . ' ekziston tashmë këtë vit shkollor.')
          ->rule('shift', in_array($values['shift'], ['1', '2'], true), 'Zgjidhni ndërrimin.')
          ->maxLength('stream', 80, 'Drejtimi mund të ketë deri në 80 karaktere.');

        $teacherId = $values['homeroom_teacher_id'];
        if ($teacherId !== '') {
            $teacher = ctype_digit($teacherId) ? User::findById((int) $teacherId) : null;
            $isTeacher = $teacher !== null && $teacher['role'] === 'teacher';
            $v->rule('homeroom_teacher_id', $isTeacher, 'Zgjidhni kujdestarin nga lista e mësimdhënësve.');

            $other = $isTeacher ? SchoolClass::homeroomOf((int) $teacherId, $academicYearId, $classId) : null;
            $v->rule('homeroom_teacher_id', $other === null,
                'Ky mësimdhënës është tashmë kujdestar i klasës ' . ($other !== null ? Format::classLabel((int) $other['grade_level'], (int) $other['section']) : '') . '.');
        }

        $roomId = $values['home_room_id'];
        if ($roomId !== '') {
            $room = ctype_digit($roomId) ? Room::find((int) $roomId) : null;
            $v->rule('home_room_id', $room !== null, 'Zgjidhni sallën nga lista.');

            // Two classes can share a room only if they are in different shifts.
            $sharing = $room !== null && in_array($values['shift'], ['1', '2'], true)
                ? SchoolClass::sharingRoom((int) $roomId, (int) $values['shift'], $academicYearId, $classId)
                : null;
            $v->rule('home_room_id', $sharing === null,
                'Kjo sallë është e klasës ' . ($sharing !== null ? Format::classLabel((int) $sharing['grade_level'], (int) $sharing['section']) : '') . ' në të njëjtin ndërrim.');
        }

        return $v->errors();
    }

    /** Typed values for SchoolClass::create()/update(). */
    public static function data(array $values, int $academicYearId, ?array $class): array
    {
        return [
            'academic_year_id'    => $academicYearId,
            'grade_level'         => $class !== null ? (int) $class['grade_level'] : (int) $values['grade_level'],
            'section'             => (int) $values['section'],
            'shift'               => (int) $values['shift'],
            'stream'              => $values['stream'] !== '' ? $values['stream'] : null,
            'homeroom_teacher_id' => $values['homeroom_teacher_id'] !== '' ? (int) $values['homeroom_teacher_id'] : null,
            'home_room_id'        => $values['home_room_id'] !== '' ? (int) $values['home_room_id'] : null,
        ];
    }
}
