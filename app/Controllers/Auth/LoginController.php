<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Services\Authenticator;
use App\Services\LoginResult;

final class LoginController extends Controller
{
    /** GET /hyr */
    public function show(): Response
    {
        return $this->form();
    }

    /** POST /hyr */
    public function login(): Response
    {
        $login = $this->request->string('login');
        $password = $this->request->input('password');
        $password = is_string($password) ? $password : '';

        if ($login === '' || $password === '') {
            return $this->form($login, 'Shkruani emrin e përdoruesit (ose email-in) dhe fjalëkalimin.', 422);
        }

        if (mb_strlen($login) > 190 || strlen($password) > 1024) {
            return $this->form($login, 'Emri i përdoruesit ose fjalëkalimi është i pasaktë.', 422);
        }

        $result = Authenticator::attempt($login, $password, $this->request->ip());

        return match ($result->outcome) {
            LoginResult::LOCKED => $this->form(
                $login,
                'Shumë përpjekje të pasuksesshme. Për sigurinë e llogarisë, provoni përsëri pas '
                    . $result->retryMinutes . ' ' . ($result->retryMinutes === 1 ? 'minute' : 'minutash') . '.',
                429
            ),
            LoginResult::INACTIVE => $this->form(
                $login,
                'Llogaria juaj është çaktivizuar. Për ndihmë drejtohuni administratës së shkollës.',
                403
            ),
            LoginResult::SUCCESS => $this->signIn($result->user),
            default => $this->form($login, 'Emri i përdoruesit ose fjalëkalimi është i pasaktë.', 422),
        };
    }

    /** POST /dil */
    public function logout(): Response
    {
        $wasSignedIn = Auth::check();
        Auth::logout();

        if ($wasSignedIn) {
            Session::flash('success', 'Dolët nga llogaria juaj. Mirupafshim!');
        }

        return redirect('/hyr');
    }

    private function signIn(array $user): Response
    {
        $intended = Session::pull('_intended');
        Auth::login($user);

        if ((int) $user['must_change_password'] === 1) {
            return redirect('/ndrysho-fjalekalimin');
        }

        return redirect(self::safePath($intended) ?? Auth::homePath($user['role']));
    }

    private function form(string $login = '', ?string $error = null, int $status = 200): Response
    {
        return $this->view('auth/login', [
            'title'       => 'Hyr',
            'login'       => $login,
            'error'       => $error,
            'expired'     => Session::flashed('session_expired') === true,
            'hasIntended' => Session::has('_intended'),
        ], 'auth', $status);
    }

    /** Only same-site paths like "/nxenesi/detyrat" — never "//evil.example" or a full URL. */
    private static function safePath(mixed $path): ?string
    {
        return is_string($path) && preg_match('#^/(?![/\\\\])[^\s]*$#', $path) === 1 ? $path : null;
    }
}
