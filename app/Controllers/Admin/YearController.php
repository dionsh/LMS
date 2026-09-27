<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\SchoolYears;
use App\Services\YearForm;

/**
 * /admin/vitet-shkollore — school years, their two semesters, and which year is current.
 */
final class YearController extends AdminController
{
    /** GET /admin/vitet-shkollore */
    public function index(): Response
    {
        $years = AcademicYear::overview();
        $latest = $years[0] ?? null;

        return $this->overview($years, YearForm::following($latest, $latest !== null ? Term::forYear((int) $latest['id']) : []), []);
    }

    /** POST /admin/vitet-shkollore/shto */
    public function store(): Response
    {
        $values = YearForm::read($this->request);
        $errors = YearForm::validate($values, null);

        if ($errors !== []) {
            return $this->overview(AcademicYear::overview(), $values, $errors, 422);
        }

        SchoolYears::create($values, (int) Auth::id());
        Session::flash('success', 'Viti shkollor ' . $values['name'] . ' u shtua. Ai bëhet vit aktual vetëm kur ta zgjidhni.');

        return redirect('/admin/vitet-shkollore');
    }

    /** GET /admin/vitet-shkollore/{id}/ndrysho */
    public function edit(int $id): Response
    {
        $year = $this->findOrFail($id);

        return $this->form($year, YearForm::fromYear($year, Term::forYear($id)), []);
    }

    /** POST /admin/vitet-shkollore/{id}/ndrysho */
    public function update(int $id): Response
    {
        $year = $this->findOrFail($id);
        $values = YearForm::read($this->request);
        $errors = YearForm::validate($values, $id);

        if ($errors !== []) {
            return $this->form($year, $values, $errors, 422);
        }

        SchoolYears::update($year, $values, (int) Auth::id());
        Session::flash('success', 'Datat e vitit shkollor ' . $values['name'] . ' u ruajtën.');

        return redirect('/admin/vitet-shkollore');
    }

    /** POST /admin/vitet-shkollore/{id}/aktual — the portal switches to this year */
    public function makeCurrent(int $id): Response
    {
        $year = $this->findOrFail($id);

        if ((int) $year['is_current'] === 1) {
            return redirect('/admin/vitet-shkollore');
        }

        SchoolYears::makeCurrent($year, (int) Auth::id());
        Session::flash('success', 'Viti shkollor ' . $year['name'] . ' është tani viti aktual.');

        return redirect('/admin/vitet-shkollore');
    }

    private function findOrFail(int $id): array
    {
        return AcademicYear::find($id) ?? throw new HttpException(404);
    }

    private function overview(array $years, array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/years', [
            'title'  => 'Vitet shkollore',
            'years'  => $years,
            'terms'  => Term::byYear(),
            'values' => $values,
            'errors' => $errors,
        ], 'years', $status);
    }

    private function form(array $year, array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/year-edit', [
            'title'  => 'Viti shkollor ' . $year['name'],
            'year'   => $year,
            'values' => $values,
            'errors' => $errors,
        ], 'years', $status);
    }
}
