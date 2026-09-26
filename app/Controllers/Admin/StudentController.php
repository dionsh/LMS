<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AccountForm;
use App\Support\Format;
use App\Support\Paginator;

final class StudentController extends AccountController
{
    /** GET /admin/nxenesit — search, filter by class and status, paginate */
    public function index(): Response
    {
        $filters = $this->listFilters() + ['role' => 'student'];
        $classId = (string) $this->request->query('klasa', '');
        $class = ctype_digit($classId) ? SchoolClass::findInYear((int) $classId, $this->yearId()) : null;

        if ($class !== null) {
            $filters['class_id'] = (int) $class['id'];
        }

        $paginator = Paginator::fromRequest($this->request, User::countSearch($this->yearId(), $filters));

        return $this->page('admin/students', [
            'title'     => 'Nxënësit',
            'students'  => User::search($this->yearId(), $filters, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'filters'   => $filters,
            'query'     => array_filter(['q' => $filters['q'], 'klasa' => $class['id'] ?? null, 'gjendja' => $this->request->query('gjendja')]),
            'class'     => $class,
            'classes'   => $this->classOptions(),
            'waiting'   => $class !== null
                ? User::countSearch($this->yearId(), ['role' => 'student', 'class_id' => (int) $class['id'], 'status' => 'active', 'never_signed_in' => true])
                : 0,
        ], 'students');
    }

    /** GET /admin/nxenesit/shto */
    public function create(): Response
    {
        $values = AccountForm::blank();
        $classId = (string) $this->request->query('klasa', '');
        $values['class_id'] = ctype_digit($classId) ? $classId : '';

        return $this->createForm('student', $values);
    }

    /** POST /admin/nxenesit/shto */
    public function store(): Response
    {
        return $this->storeAccount('student');
    }

    /** POST /admin/nxenesit/fletet — slips for every active student of a class who has not signed in yet */
    public function issueForClass(): Response
    {
        $classId = $this->request->string('class_id');
        $class = ctype_digit($classId) ? SchoolClass::findInYear((int) $classId, $this->yearId()) : null;

        if ($class === null) {
            return redirect('/admin/nxenesit');
        }

        $students = User::search($this->yearId(), [
            'role' => 'student', 'class_id' => (int) $class['id'], 'status' => 'active', 'never_signed_in' => true,
        ], 1000, 0);

        $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);

        return $this->showSlips($students, 'Fletët e hyrjes · Klasa ' . $label, '/admin/nxenesit?klasa=' . $class['id']);
    }
}
