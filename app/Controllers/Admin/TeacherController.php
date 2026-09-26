<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\User;
use App\Services\AccountForm;
use App\Support\Paginator;

final class TeacherController extends AccountController
{
    /** GET /admin/mesimdhenesit — search, filter by status and credentials, paginate */
    public function index(): Response
    {
        $filters = $this->listFilters() + ['role' => 'teacher'];
        $access = (string) $this->request->query('llogaria', '');
        if ($access === 'pa-flete') {
            $filters['credentials'] = 'none';
        } elseif ($access === 'me-flete') {
            $filters['credentials'] = 'issued';
        }

        $paginator = Paginator::fromRequest($this->request, User::countSearch($this->yearId(), $filters));

        return $this->page('admin/teachers', [
            'title'     => 'Mësimdhënësit',
            'teachers'  => User::search($this->yearId(), $filters, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'filters'   => $filters,
            'access'    => $access,
            'query'     => array_filter(['q' => $filters['q'], 'gjendja' => $this->request->query('gjendja'), 'llogaria' => $access]),
            'waiting'   => User::countSearch($this->yearId(), ['role' => 'teacher', 'status' => 'active', 'credentials' => 'none']),
        ], 'teachers');
    }

    /** GET /admin/mesimdhenesit/shto */
    public function create(): Response
    {
        return $this->createForm('teacher', AccountForm::blank());
    }

    /** POST /admin/mesimdhenesit/shto */
    public function store(): Response
    {
        return $this->storeAccount('teacher');
    }

    /** POST /admin/mesimdhenesit/fletet — slips for every active teacher who has no credentials yet */
    public function issueMissing(): Response
    {
        $teachers = User::search($this->yearId(), ['role' => 'teacher', 'status' => 'active', 'credentials' => 'none'], 1000, 0);

        return $this->showSlips($teachers, 'Fletët e hyrjes · Mësimdhënësit', '/admin/mesimdhenesit');
    }
}
