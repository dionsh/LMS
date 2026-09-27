<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Services\Structure;
use App\Services\SubjectForm;

/**
 * /admin/lendet — the school's subjects and which grades study them.
 */
final class SubjectController extends AdminController
{
    /** GET /admin/lendet */
    public function index(): Response
    {
        return $this->page('admin/subjects', [
            'title'    => 'Lëndët',
            'subjects' => Subject::overview($this->yearId()),
        ], 'subjects');
    }

    /** GET /admin/lendet/shto */
    public function create(): Response
    {
        return $this->form(null, SubjectForm::blank(), []);
    }

    /** POST /admin/lendet/shto */
    public function store(): Response
    {
        $values = SubjectForm::read($this->request, $this->gradeLevels());
        $errors = SubjectForm::validate($values, null);

        if ($errors !== []) {
            return $this->form(null, $values, $errors, 422);
        }

        Structure::createSubject(SubjectForm::data($values), SubjectForm::curriculum($values), $this->yearId(), (int) Auth::id());
        Session::flash('success', 'Lënda ' . $values['name'] . ' u shtua.');

        return redirect('/admin/lendet');
    }

    /** GET /admin/lendet/{id}/ndrysho */
    public function edit(int $id): Response
    {
        $subject = $this->findOrFail($id);

        return $this->form($subject, SubjectForm::fromSubject($subject, Curriculum::forSubject($id)), []);
    }

    /** POST /admin/lendet/{id}/ndrysho */
    public function update(int $id): Response
    {
        $subject = $this->findOrFail($id);
        $values = SubjectForm::read($this->request, $this->gradeLevels());
        $errors = SubjectForm::validate($values, $id);

        if ($errors !== []) {
            return $this->form($subject, $values, $errors, 422);
        }

        Structure::updateSubject($subject, SubjectForm::data($values), SubjectForm::curriculum($values), $this->yearId(), (int) Auth::id());
        Session::flash('success', 'Ndryshimet u ruajtën.');

        return redirect('/admin/lendet/' . $id . '/ndrysho');
    }

    /** POST /admin/lendet/{id}/fshij — only a subject that has never been taught in a class */
    public function destroy(int $id): Response
    {
        $subject = $this->findOrFail($id);

        if (Subject::isTaught($id)) {
            Session::flash('error', 'Lënda ' . $subject['name'] . ' mësohet në klasa, prandaj nuk mund të fshihet. Mund ta bëni joaktive.');
            return redirect('/admin/lendet/' . $id . '/ndrysho');
        }

        Structure::deleteSubject($subject, (int) Auth::id());
        Session::flash('success', 'Lënda ' . $subject['name'] . ' u fshi.');

        return redirect('/admin/lendet');
    }

    private function findOrFail(int $id): array
    {
        return Subject::find($id) ?? throw new HttpException(404);
    }

    /** @return list<int> */
    private function gradeLevels(): array
    {
        return array_keys(GradeLevel::shifts());
    }

    private function form(?array $subject, array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/subject-form', [
            'title'    => $subject !== null ? $subject['name'] : 'Shto lëndë',
            'subject'  => $subject,
            'values'   => $values,
            'errors'   => $errors,
            'grades'   => $this->gradeLevels(),
            'teachers' => $subject !== null ? Subject::teachers((int) $subject['id']) : [],
            'taught'   => $subject !== null && Subject::isTaught((int) $subject['id']),
        ], 'subjects', $status);
    }
}
