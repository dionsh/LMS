<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\PortalController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\PasswordPolicy;

/**
 * /profili — every signed-in user can see their account and change their
 * e-mail, phone and password. Names are set by the school and are read-only.
 */
final class ProfileController extends PortalController
{
    /** GET /paneli — the right dashboard for the signed-in role */
    public function home(): Response
    {
        return redirect(Auth::homePath());
    }

    /** GET /profili */
    public function show(): Response
    {
        $user = $this->user();

        return $this->profile(['email' => (string) $user['email'], 'phone' => (string) $user['phone']]);
    }

    /** POST /profili */
    public function update(): Response
    {
        $user = $this->user();
        $v = new Validator($this->request->all());
        $email = mb_strtolower($v->value('email'));
        $phone = $v->value('phone');

        $v->maxLength('email', 190, 'Email-i është shumë i gjatë.')
          ->email('email', 'Shkruani një adresë të vlefshme, p.sh. emri@shembull.com.')
          ->rule('email', $email === '' || !User::emailTaken($email, (int) $user['id']), 'Ky email përdoret nga një llogari tjetër.')
          ->rule('phone', $phone === '' || preg_match('/^\+?[0-9 ()\-]{6,30}$/', $phone) === 1, 'Shkruani numrin me shifra, p.sh. +383 44 123 456.');

        if ($v->fails()) {
            return $this->profile(['email' => $email, 'phone' => $phone], $v->errors(), [], 422);
        }

        User::updateContact((int) $user['id'], $email !== '' ? $email : null, $phone !== '' ? $phone : null);
        Session::flash('success', 'Të dhënat u ruajtën.');

        return redirect('/profili');
    }

    /** POST /profili/fjalekalimi */
    public function updatePassword(): Response
    {
        $user = $this->user();
        $v = new Validator($this->request->all());
        $current = $v->raw('current_password');
        $new = $v->raw('password');

        $v->rule(
            'current_password',
            $current !== '' && password_verify($current, (string) User::passwordHash((int) $user['id'])),
            'Fjalëkalimi aktual nuk është i saktë.'
        );
        $v->addErrors(PasswordPolicy::validate($new, $v->raw('password_confirmation'), $user, $current));

        if ($v->fails()) {
            return $this->profile(['email' => (string) $user['email'], 'phone' => (string) $user['phone']], [], $v->errors(), 422);
        }

        User::updatePassword((int) $user['id'], $new);
        ActivityLog::record((int) $user['id'], 'auth.password_changed', 'Ndryshoi fjalëkalimin nga profili.', 'user', (int) $user['id']);
        Session::regenerate();
        Session::flash('success', 'Fjalëkalimi u ndryshua.');

        return redirect('/profili');
    }

    private function profile(array $values, array $errors = [], array $passwordErrors = [], int $status = 200): Response
    {
        return $this->page('account/profile', [
            'title'          => 'Profili',
            'values'         => $values,
            'errors'         => $errors,
            'passwordErrors' => $passwordErrors,
        ], null, $status);
    }
}
