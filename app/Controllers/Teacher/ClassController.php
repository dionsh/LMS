<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\LessonPeriod;
use App\Models\ScheduleEntry;
use App\Models\SchoolClass;
use App\Policies\ClassSubjectPolicy;

/**
 * /mesimdhenesi/klasat — the subjects a teacher teaches in each class this
 * year, and one class-subject's page (its lessons in the week and students).
 */
final class ClassController extends PortalController
{
    /** GET /mesimdhenesi/klasat */
    public function index(): Response
    {
        $yearId = (int) (AcademicYear::current()['id'] ?? 0);
        $teacherId = (int) Auth::id();

        return $this->page('teacher/classes', [
            'title'    => 'Klasat',
            'teaching' => ClassSubject::forTeacher($teacherId, $yearId),
            'homeroom' => SchoolClass::homeroomOf($teacherId, $yearId),
        ], 'classes');
    }

    /** GET /mesimdhenesi/klasat/{id} — only for the teacher who teaches it (404 otherwise) */
    public function show(int $id): Response
    {
        $classSubject = ClassSubject::find($id);
        ClassSubjectPolicy::authorize(ClassSubjectPolicy::teaches($this->user(), $classSubject));

        $class = SchoolClass::find((int) $classSubject['class_id']);
        $periods = LessonPeriod::forShift((int) $class['shift']);
        $lessons = [];
        foreach (ScheduleEntry::forClass((int) $class['id']) as $entry) {
            if ((int) $entry['class_subject_id'] === $id) {
                $period = $periods[(int) $entry['period']] ?? null;
                $lessons[] = $entry + ['starts_at' => $period['starts_at'] ?? null, 'ends_at' => $period['ends_at'] ?? null];
            }
        }

        return $this->page('teacher/class', [
            'title'        => $classSubject['subject_name'],
            'classSubject' => $classSubject,
            'class'        => $class,
            'lessons'      => $lessons,
            'students'     => SchoolClass::students((int) $class['id']),
        ], 'classes');
    }
}
