<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AccountForm;
use App\Services\Accounts;
use App\Services\Credentials;
use App\Services\SlipStore;
use App\Support\Format;

/**
 * What the admin's student, teacher and account pages share: the add form,
 * saving a new account, and turning issued credentials into printable slips.
 */
abstract class AccountController extends PortalController
{
    /** Navigation key for each role's pages. */
    protected const NAV = ['student' => 'students', 'teacher' => 'teachers', 'admin' => 'users'];

    /** List page of each role, where "Anulo" and the flash messages lead. */
    protected const LIST_PATH = ['student' => '/admin/nxenesit', 'teacher' => '/admin/mesimdhenesit', 'admin' => '/admin/perdoruesit'];

    protected function yearId(): int
    {
        return (int) (AcademicYear::current()['id'] ?? 0);
    }

    /** Classes of the current year, grouped for <optgroup>: ['X' => [[id, label], …], …] */
    protected function classOptions(): array
    {
        $groups = [];
        foreach (SchoolClass::optionsForYear($this->yearId()) as $class) {
            $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
            $groups[explode('/', $label)[0]][] = ['id' => (int) $class['id'], 'label' => $label];
        }

        return $groups;
    }

    protected function createForm(string $role, array $values, array $errors = [], int $status = 200): Response
    {
        return $this->page('admin/account-create', [
            'title'   => match ($role) { 'student' => 'Shto nxënës', 'teacher' => 'Shto mësimdhënës', default => 'Shto administrator' },
            'role'    => $role,
            'values'  => $values,
            'errors'  => $errors,
            'classes' => $role === 'student' ? $this->classOptions() : [],
            'back'    => self::LIST_PATH[$role],
        ], self::NAV[$role], $status);
    }

    /** POST of an add form: validate, create, and (optionally) issue the login slip at once. */
    protected function storeAccount(string $role): Response
    {
        $values = AccountForm::read($this->request);
        $errors = AccountForm::validate($values, $role, null, $this->yearId());

        if ($errors !== []) {
            return $this->createForm($role, $values, $errors, 422);
        }

        $id = Accounts::create($role, $values, (int) Auth::id());
        $name = $values['first_name'] . ' ' . $values['last_name'];

        if ($values['issue_slip']) {
            $person = User::details($id, $this->yearId());
            return $this->showSlips([$person], 'Fleta e hyrjes · ' . $name, '/admin/perdoruesit/' . $id . '/ndrysho');
        }

        Session::flash('success', $name . ' u shtua. Fletën e hyrjes mund ta lëshoni kur të jetë e nevojshme.');

        return redirect('/admin/perdoruesit/' . $id . '/ndrysho');
    }

    /** Issue credentials for these people and open the printable slips. */
    protected function showSlips(array $people, string $title, string $returnTo): Response
    {
        if ($people === []) {
            Session::flash('info', 'Nuk ka njeri për të cilin duhet lëshuar fletë hyrjeje.');
            return redirect($returnTo);
        }

        $slips = Credentials::issue($people, (int) Auth::id());

        return redirect('/admin/fletet-e-hyrjes/' . SlipStore::put($title, $slips, $returnTo));
    }

    /** Filters from the query string, only known values. */
    protected function listFilters(): array
    {
        $status = (string) $this->request->query('gjendja', '');

        return [
            'q'      => mb_substr(trim((string) $this->request->query('q', '')), 0, 100),
            'status' => match ($status) { 'aktiv' => 'active', 'joaktiv' => 'inactive', default => null },
        ];
    }
}
