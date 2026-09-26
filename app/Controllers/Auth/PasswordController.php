<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\PasswordPolicy;

/**
 * First sign-in: the temporary password issued by the school must be replaced.
 */
final class PasswordController extends Controller
{
    /** GET /ndrysho-fjalekalimin */
    public function show(): Response
    {
        if (!$this->mustChange()) {
            return redirect(Auth::homePath());
        }

        return $this->form();
    }

    /** POST /ndrysho-fjalekalimin */
    public function update(): Response
    {
        if (!$this->mustChange()) {
            return redirect(Auth::homePath());
        }

        $user = Auth::user();
        $v = new Validator($this->request->all());
        $current = $v->raw('current_password');
        $new = $v->raw('password');

        $v->rule(
            'current_password',
            $current !== '' && password_verify($current, (string) User::passwordHash((int) $user['id'])),
            'Fjalëkalimi i përkohshëm nuk është i saktë.'
        );
        $v->addErrors(PasswordPolicy::validate($new, $v->raw('password_confirmation'), $user, $current));

        if ($v->fails()) {
            return $this->form($v->errors(), 422);
        }

        User::updatePassword((int) $user['id'], $new);
        ActivityLog::record((int) $user['id'], 'auth.password_set', 'Zgjodhi fjalëkalimin e vet në hyrjen e parë.', 'user', (int) $user['id']);

        Session::regenerate();
        Auth::refresh();
        Session::flash('success', 'Fjalëkalimi u ruajt. Mirë se vini në portal!');

        return redirect(Auth::homePath());
    }

    private function mustChange(): bool
    {
        return (int) (Auth::user()['must_change_password'] ?? 0) === 1;
    }

    private function form(array $errors = [], int $status = 200): Response
    {
        return $this->view('auth/password-forced', [
            'title'  => 'Zgjidhni fjalëkalimin',
            'user'   => Auth::user(),
            'errors' => $errors,
        ], 'auth', $status);
    }
}
