<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\AccountForm;
use App\Services\Accounts;
use App\Support\Paginator;

/**
 * /admin/perdoruesit — every account (all roles), adding administrators,
 * and the edit page shared by all roles.
 */
final class UserController extends AccountController
{
    /** GET /admin/perdoruesit */
    public function index(): Response
    {
        $filters = $this->listFilters();
        $role = (string) $this->request->query('roli', '');
        $roles = ['nxenes' => 'student', 'mesimdhenes' => 'teacher', 'administrator' => 'admin'];
        if (isset($roles[$role])) {
            $filters['role'] = $roles[$role];
        }

        $paginator = Paginator::fromRequest($this->request, User::countSearch($this->yearId(), $filters));

        return $this->page('admin/users', [
            'title'     => 'Llogaritë',
            'users'     => User::search($this->yearId(), $filters, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'filters'   => $filters,
            'role'      => $role,
            'query'     => array_filter(['q' => $filters['q'], 'roli' => $role, 'gjendja' => $this->request->query('gjendja')]),
        ], 'users');
    }

    /** GET /admin/perdoruesit/shto — a new administrator */
    public function create(): Response
    {
        return $this->createForm('admin', AccountForm::blank());
    }

    /** POST /admin/perdoruesit/shto */
    public function store(): Response
    {
        return $this->storeAccount('admin');
    }

    /** GET /admin/perdoruesit/{id}/ndrysho */
    public function edit(int $id): Response
    {
        $user = $this->findOrFail($id);

        return $this->editForm($user, AccountForm::fromUser($user));
    }

    /** POST /admin/perdoruesit/{id}/ndrysho */
    public function update(int $id): Response
    {
        $user = $this->findOrFail($id);
        $values = AccountForm::read($this->request);
        $errors = AccountForm::validate($values, $user['role'], $id, $this->yearId());

        if ($errors !== []) {
            return $this->editForm($user, $values, $errors, 422);
        }

        Accounts::update($user, $values, (int) Auth::id(), $this->yearId());
        Session::flash('success', 'Ndryshimet u ruajtën.');

        return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
    }

    /** POST /admin/perdoruesit/{id}/fleta — a new login slip (new temporary password) */
    public function issue(int $id): Response
    {
        $user = $this->findOrFail($id);

        if ($id === Auth::id()) {
            Session::flash('error', 'Për llogarinë tuaj përdorni faqen e profilit për të ndryshuar fjalëkalimin.');
            return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
        }

        if ($user['status'] !== 'active') {
            Session::flash('error', 'Aktivizoni llogarinë para se të lëshoni fletë hyrjeje.');
            return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
        }

        return $this->showSlips([$user], 'Fleta e hyrjes · ' . $user['first_name'] . ' ' . $user['last_name'], '/admin/perdoruesit/' . $id . '/ndrysho');
    }

    /** POST /admin/perdoruesit/{id}/statusi — activate or deactivate */
    public function status(int $id): Response
    {
        $user = $this->findOrFail($id);
        $status = $this->request->string('status');

        if (!in_array($status, ['active', 'inactive'], true) || $status === $user['status']) {
            return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
        }

        if ($status === 'inactive' && $id === Auth::id()) {
            Session::flash('error', 'Nuk mund ta çaktivizoni llogarinë tuaj.');
            return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
        }

        if ($status === 'inactive' && $user['role'] === 'admin' && User::countActiveAdmins() <= 1) {
            Session::flash('error', 'Ky është administratori i fundit aktiv. Shtoni ose aktivizoni një administrator tjetër më parë.');
            return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
        }

        Accounts::setStatus($user, $status, (int) Auth::id());
        Session::flash('success', $status === 'active'
            ? 'Llogaria u aktivizua.'
            : 'Llogaria u çaktivizua. ' . $user['first_name'] . ' nuk mund të hyjë më në portal.');

        return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
    }

    private function findOrFail(int $id): array
    {
        return User::details($id, $this->yearId()) ?? throw new HttpException(404);
    }

    private function editForm(array $user, array $values, array $errors = [], int $status = 200): Response
    {
        return $this->page('admin/account-edit', [
            'title'   => $user['first_name'] . ' ' . $user['last_name'],
            'account' => $user,
            'values'  => $values,
            'errors'  => $errors,
            'classes' => $user['role'] === 'student' ? $this->classOptions() : [],
            'back'    => self::LIST_PATH[$user['role']],
            'isSelf'  => (int) $user['id'] === Auth::id(),
        ], self::NAV[$user['role']], $status);
    }
}
