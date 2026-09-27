<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassForm;
use App\Services\Structure;
use App\Services\WeeklyHours;
use App\Support\Format;
use PDOException;

/**
 * /admin/klasat — the classes of the current year: adding and editing them,
 * their students, and which teacher teaches each subject in them.
 */
final class ClassController extends AdminController
{
    /** GET /admin/klasat — every class of the current year, grouped by grade */
    public function index(): Response
    {
        $byGrade = array_fill_keys(array_keys(GradeLevel::shifts()), []);
        foreach (SchoolClass::overview($this->yearId()) as $class) {
            $byGrade[(int) $class['grade_level']][] = $class;
        }

        return $this->page('admin/classes', [
            'title'   => 'Klasat',
            'year'    => AcademicYear::current(),
            'byGrade' => $byGrade,
            'shifts'  => GradeLevel::shifts(),
        ], 'classes');
    }

    /** GET /admin/klasat/shto?niveli=12 */
    public function create(): Response
    {
        $shifts = GradeLevel::shifts();
        if ($shifts === []) {
            Session::flash('info', 'Shtoni së pari klasat (X, XI, XII…) te Plani mësimor.');
            return redirect('/admin/plani-mesimor');
        }

        $requested = (string) $this->request->query('niveli', '');
        $grade = ctype_digit($requested) && isset($shifts[(int) $requested]) ? (int) $requested : (int) array_key_first($shifts);

        return $this->form(null, ClassForm::blank($grade, SchoolClass::nextSection($this->yearId(), $grade), $shifts[$grade]), []);
    }

    /** POST /admin/klasat/shto */
    public function store(): Response
    {
        $values = ClassForm::read($this->request);
        $errors = ClassForm::validate($values, $this->yearId(), null);

        if ($errors !== []) {
            return $this->form(null, $values, $errors, 422);
        }

        $data = ClassForm::data($values, $this->yearId(), null);
        $id = Structure::createClass($data, (int) Auth::id());
        $label = Format::classLabel($data['grade_level'], $data['section']);

        if ($this->request->input('next') === '1') {
            Session::flash('success', 'Klasa ' . $label . ' u shtua. Vazhdoni me paralelen tjetër.');
            return redirect('/admin/klasat/shto?niveli=' . $data['grade_level']);
        }

        Session::flash('success', 'Klasa ' . $label . ' u shtua me lëndët e planit mësimor. Caktoni tani mësimdhënësit.');

        return redirect('/admin/klasat/' . $id);
    }

    /** GET /admin/klasat/{id} — students, subjects and teachers */
    public function show(int $id): Response
    {
        return $this->detail($this->findOrFail($id), [], [], 200);
    }

    /** GET /admin/klasat/{id}/ndrysho */
    public function edit(int $id): Response
    {
        $class = $this->findOrFail($id);

        return $this->form($class, ClassForm::fromClass($class), []);
    }

    /** POST /admin/klasat/{id}/ndrysho */
    public function update(int $id): Response
    {
        $class = $this->findOrFail($id);
        $values = ClassForm::read($this->request);
        $errors = ClassForm::validate($values, (int) $class['academic_year_id'], $class);

        if ($errors !== []) {
            return $this->form($class, $values, $errors, 422);
        }

        Structure::updateClass($class, ClassForm::data($values, (int) $class['academic_year_id'], $class), (int) Auth::id());
        Session::flash('success', 'Ndryshimet u ruajtën.');

        return redirect('/admin/klasat/' . $id);
    }

    /** POST /admin/klasat/{id}/fshij — only a class without students or coursework */
    public function destroy(int $id): Response
    {
        $class = $this->findOrFail($id);
        $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);

        if ((int) $class['students'] > 0) {
            Session::flash('error', 'Klasa ' . $label . ' ka nxënës. Zhvendosini ata në një klasë tjetër para se ta fshini.');
            return redirect('/admin/klasat/' . $id . '/ndrysho');
        }

        try {
            Structure::deleteClass($class, (int) Auth::id());
        } catch (PDOException) {
            // Homework, tests or marks refer to the class's subjects (ON DELETE RESTRICT)
            Session::flash('error', 'Klasa ' . $label . ' ka detyra, vlerësime ose nota, prandaj nuk mund të fshihet.');
            return redirect('/admin/klasat/' . $id . '/ndrysho');
        }

        Session::flash('success', 'Klasa ' . $label . ' u fshi.');

        return redirect('/admin/klasat');
    }

    /** POST /admin/klasat/{id}/lendet — the teacher and weekly hours of every subject */
    public function saveSubjects(int $id): Response
    {
        $class = $this->findOrFail($id);
        $subjects = ClassSubject::forClass($id);
        $teacherIds = array_map('intval', array_column(User::teacherOptions($this->assignedTeachers($subjects)), 'id'));
        $teachers = $this->request->keyed('teacher');
        $hours = $this->request->keyed('hours');
        $chosen = $this->request->keyed('subject');
        $choices = Subject::electiveChoices();
        $present = array_map('intval', array_column($subjects, 'subject_id'));

        $rows = [];
        $errors = [];
        foreach ($subjects as $subject) {
            $csId = (int) $subject['id'];
            $teacher = $teachers[$csId] ?? '';
            $weekly = $hours[$csId] ?? '';

            // A subject with electives: which one the class takes (Mësim zgjedhor or Orientim në karrierë …)
            $subjectId = (int) $subject['subject_id'];
            $place = (int) ($subject['fills_subject_id'] ?? $subjectId);
            $choice = $chosen[$csId] ?? '';
            if ($choice !== '' && isset($choices[$place]) && $choice !== (string) $subjectId) {
                if (!ctype_digit($choice) || !isset($choices[$place][(int) $choice])) {
                    $errors['subject-' . $csId] = 'Zgjidhni lëndën nga lista.';
                } elseif (in_array((int) $choice, $present, true)) {
                    $errors['subject-' . $csId] = 'Klasa e ka tashmë këtë lëndë.';
                } else {
                    $subjectId = (int) $choice;
                }
            }

            if ($teacher !== '' && !(ctype_digit($teacher) && in_array((int) $teacher, $teacherIds, true))) {
                $errors['teacher-' . $csId] = 'Zgjidhni mësimdhënësin nga lista.';
            }
            if (!WeeklyHours::valid($weekly)) {
                $errors['hours-' . $csId] = WeeklyHours::MESSAGE;
            }

            $rows[$csId] = [
                'subject_id'   => $subjectId !== (int) $subject['subject_id'] ? $subjectId : null,
                'teacher_id'   => $teacher !== '' && ctype_digit($teacher) ? (int) $teacher : null,
                'weekly_hours' => WeeklyHours::valid($weekly) ? WeeklyHours::parse($weekly) : null,
            ];
        }

        if ($errors !== []) {
            return $this->detail($class, ['subject' => $chosen, 'teacher' => $teachers, 'hours' => $hours], $errors, 422);
        }

        Structure::saveClassSubjects($class, $rows, (int) Auth::id());
        Session::flash('success', 'Lëndët dhe mësimdhënësit e klasës ' . Format::classLabel((int) $class['grade_level'], (int) $class['section']) . ' u ruajtën.');

        return redirect('/admin/klasat/' . $id);
    }

    /** POST /admin/klasat/{id}/lendet/shto — a subject outside the grade's curriculum */
    public function addSubject(int $id): Response
    {
        $class = $this->findOrFail($id);
        $subjectId = $this->request->string('subject_id');
        $subject = ctype_digit($subjectId) ? Subject::find((int) $subjectId) : null;
        $taken = array_map('intval', array_column(ClassSubject::forClass($id), 'subject_id'));

        if ($subject === null || (int) $subject['is_active'] === 0 || in_array((int) $subject['id'], $taken, true)) {
            Session::flash('error', 'Zgjidhni një lëndë aktive që klasa nuk e ka ende.');
            return redirect('/admin/klasat/' . $id);
        }

        Structure::addClassSubject($class, $subject, (int) Auth::id());
        Session::flash('success', 'Lënda ' . $subject['name'] . ' iu shtua klasës. Caktoni mësimdhënësin e saj.');

        return redirect('/admin/klasat/' . $id);
    }

    /** POST /admin/klasat/{id}/lendet/hiq — a subject outside the curriculum that nothing depends on yet */
    public function removeSubject(int $id): Response
    {
        $class = $this->findOrFail($id);
        $csId = $this->request->string('class_subject_id');
        $row = null;
        foreach (ClassSubject::forClass($id) as $subject) {
            if ((string) $subject['id'] === $csId) {
                $row = $subject;
            }
        }

        if ($row === null || (int) $row['in_curriculum'] === 1) {
            Session::flash('error', 'Zgjidhni një lëndë jashtë planit mësimor.');
        } elseif (ClassSubject::isInUse((int) $row['id'])) {
            Session::flash('error', 'Lënda ' . $row['subject_name'] . ' ka orë në orar ose nota, prandaj nuk mund të hiqet.');
        } else {
            Structure::removeClassSubject($class, $row, (int) Auth::id());
            Session::flash('success', 'Lënda ' . $row['subject_name'] . ' u hoq nga klasa.');
        }

        return redirect('/admin/klasat/' . $id);
    }

    private function findOrFail(int $id): array
    {
        return SchoolClass::find($id) ?? throw new HttpException(404);
    }

    /** @return list<int> teachers currently assigned (kept in the lists even if deactivated) */
    private function assignedTeachers(array $subjects): array
    {
        return array_values(array_unique(array_map('intval', array_filter(array_column($subjects, 'teacher_id')))));
    }

    /**
     * The class page. $posted holds the submitted teacher/hours after a failed save.
     */
    private function detail(array $class, array $posted, array $errors, int $status): Response
    {
        $id = (int) $class['id'];
        $subjects = ClassSubject::forClass($id);
        $taken = array_map('intval', array_column($subjects, 'subject_id'));

        return $this->page('admin/class', [
            'title'     => 'Klasa ' . Format::classLabel((int) $class['grade_level'], (int) $class['section']),
            'class'     => $class,
            'subjects'  => $subjects,
            'teachers'  => User::teacherOptions($this->assignedTeachers($subjects), (int) $class['academic_year_id']),
            'students'  => SchoolClass::students($id),
            'addable'   => array_values(array_filter(Subject::options(true), static fn (array $s): bool => $s['fills_subject_id'] === null && !in_array((int) $s['id'], $taken, true))),
            'choices'   => Subject::electiveChoices(),
            'posted'    => $posted,
            'errors'    => $errors,
        ], 'classes', $status);
    }

    private function form(?array $class, array $values, array $errors, int $status = 200): Response
    {
        $yearId = $class !== null ? (int) $class['academic_year_id'] : $this->yearId();
        $homerooms = [];
        foreach (SchoolClass::overview($yearId) as $other) {
            if ($other['homeroom_teacher_id'] !== null && ($class === null || (int) $other['id'] !== (int) $class['id'])) {
                $homerooms[(int) $other['homeroom_teacher_id']] = Format::classLabel((int) $other['grade_level'], (int) $other['section']);
            }
        }

        $current = $class !== null && $class['homeroom_teacher_id'] !== null ? [(int) $class['homeroom_teacher_id']] : [];

        return $this->page('admin/class-form', [
            'title'     => $class !== null ? 'Ndrysho klasën ' . Format::classLabel((int) $class['grade_level'], (int) $class['section']) : 'Shto klasë',
            'class'     => $class,
            'values'    => $values,
            'errors'    => $errors,
            'grades'    => GradeLevel::shifts(),
            'teachers'  => User::teacherOptions($current),
            'homerooms' => $homerooms,
            'rooms'     => Room::options(),
        ], 'classes', $status);
    }
}
