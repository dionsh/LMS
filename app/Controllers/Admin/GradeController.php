<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\LessonPeriod;
use App\Models\Subject;
use App\Services\Structure;
use App\Services\WeeklyHours;
use App\Support\Format;
use App\Support\Labels;

/**
 * /admin/plani-mesimor — the grades (X, XI, XII) and their curriculum:
 * which subjects each grade studies and how many lessons a week.
 */
final class GradeController extends AdminController
{
    /** GET /admin/plani-mesimor */
    public function index(): Response
    {
        return $this->overview(['level' => '', 'shift' => '1'], []);
    }

    /** POST /admin/plani-mesimor/shto — a grade the school starts teaching */
    public function store(): Response
    {
        $values = ['level' => $this->request->string('level'), 'shift' => $this->request->string('shift')];
        $level = ctype_digit($values['level']) ? (int) $values['level'] : 0;

        $errors = (new Validator($values))
            ->rule('level', $level >= 1 && $level <= 13, 'Shkruani klasën si numër nga 1 deri në 13, p.sh. 9 për klasën IX.')
            ->rule('level', $level === 0 || GradeLevel::find($level) === null, 'Klasa ' . Format::grade(max($level, 1)) . ' është tashmë në planin mësimor.')
            ->rule('shift', isset(Labels::SHIFTS[(int) $values['shift']]), 'Zgjidhni ndërrimin.')
            ->errors();

        if ($errors !== []) {
            return $this->overview($values, $errors, 422);
        }

        Structure::createGrade($level, (int) $values['shift'], (int) Auth::id());
        Session::flash('success', 'Klasa ' . Format::grade($level) . ' u shtua. Zgjidhni tani lëndët e saj.');

        return redirect('/admin/plani-mesimor/' . $level);
    }

    /** GET /admin/plani-mesimor/{level} */
    public function edit(int $level): Response
    {
        $grade = $this->findOrFail($level);
        $selected = [];
        foreach (Curriculum::forGrade($level) as $row) {
            $selected[(int) $row['subject_id']] = $row['weekly_hours'] !== null ? (string) $row['weekly_hours'] : '';
        }

        return $this->form($grade, ['shift' => (string) $grade['shift'], 'move_classes' => false, 'selected' => $selected], []);
    }

    /** POST /admin/plani-mesimor/{level} */
    public function update(int $level): Response
    {
        $grade = $this->findOrFail($level);
        $hours = $this->request->keyed('hours');
        $selected = [];
        foreach ($this->request->ids('subjects') as $subjectId) {
            $selected[$subjectId] = $hours[$subjectId] ?? '';
        }

        $values = [
            'shift'        => $this->request->string('shift'),
            'move_classes' => $this->request->input('move_classes') === '1',
            'selected'     => $selected,
        ];

        $v = (new Validator($values))
            ->rule('shift', isset(Labels::SHIFTS[(int) $values['shift']]), 'Zgjidhni ndërrimin.')
            ->rule('subjects', array_diff(array_keys($selected), Subject::ids()) === [], 'Zgjidhni lëndët nga lista.');
        foreach ($selected as $subjectId => $value) {
            $v->rule('hours-' . $subjectId, WeeklyHours::valid($value), WeeklyHours::MESSAGE);
        }

        if ($v->fails()) {
            return $this->form($grade, $values, $v->errors(), 422);
        }

        $result = Structure::saveCurriculum(
            $level,
            (int) $values['shift'],
            $values['move_classes'],
            array_map([WeeklyHours::class, 'parse'], $selected),
            $this->yearId(),
            (int) Auth::id(),
        );

        $notes = array_filter([
            $result['added'] > 0 ? $result['added'] . ' lëndë iu shtuan paraleleve' : '',
            $result['removed'] > 0 ? $result['removed'] . ' lëndë u hoqën nga paralelet' : '',
            $result['moved'] > 0 ? $result['moved'] . ' paralele kaluan në ndërrimin ' . Labels::shift((int) $values['shift'], true) : '',
        ]);
        Session::flash('success', 'Plani mësimor i klasës ' . Format::grade($level) . ' u ruajt.' . ($notes !== [] ? ' ' . Format::ucfirst(implode(', ', $notes)) . '.' : ''));

        return redirect('/admin/plani-mesimor');
    }

    /** POST /admin/plani-mesimor/{level}/fshij — only a grade without classes */
    public function destroy(int $level): Response
    {
        $this->findOrFail($level);

        if (GradeLevel::hasClasses($level)) {
            Session::flash('error', 'Klasa ' . Format::grade($level) . ' ka paralele, prandaj nuk mund të hiqet nga plani mësimor.');
            return redirect('/admin/plani-mesimor/' . $level);
        }

        Structure::deleteGrade($level, (int) Auth::id());
        Session::flash('success', 'Klasa ' . Format::grade($level) . ' u hoq nga plani mësimor.');

        return redirect('/admin/plani-mesimor');
    }

    private function findOrFail(int $level): array
    {
        return GradeLevel::find($level) ?? throw new HttpException(404);
    }

    private function overview(array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/grades', [
            'title'      => 'Plani mësimor',
            'grades'     => GradeLevel::overview($this->yearId()),
            'curriculum' => Curriculum::allGrades(),
            'slots'      => $this->weeklySlots(),
            'values'     => $values,
            'errors'     => $errors,
        ], 'curriculum', $status);
    }

    private function form(array $grade, array $values, array $errors, int $status = 200): Response
    {
        $level = (int) $grade['level'];
        $overview = array_column(GradeLevel::overview($this->yearId()), null, 'level')[$level] ?? [];

        return $this->page('admin/grade-edit', [
            'title'    => 'Plani mësimor · Klasa ' . Format::grade($level),
            'grade'    => $grade,
            'overview' => $overview,
            'subjects' => Subject::options(),
            'slots'    => $this->weeklySlots(),
            'values'   => $values,
            'errors'   => $errors,
        ], 'curriculum', $status);
    }

    /** Lesson slots in a week per shift: periods × 5 school days. */
    private function weeklySlots(): array
    {
        $slots = [];
        foreach (array_keys(Labels::SHIFTS) as $shift) {
            $slots[$shift] = count(LessonPeriod::forShift($shift)) * count(Labels::SCHOOL_DAYS);
        }

        return $slots;
    }
}
