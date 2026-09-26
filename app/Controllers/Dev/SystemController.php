<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

use App\Controllers\Controller;
use App\Core\Config;
use App\Core\Response;
use App\Core\Session;
use App\Models\Diagnostics;
use App\Support\Format;
use DateTimeImmutable;
use PDOException;

/**
 * /_sistemi — a checklist of the environment. Only routed in development.
 */
final class SystemController extends Controller
{
    public function index(): Response
    {
        $checks = [];
        $now = new DateTimeImmutable();

        $checks[] = ['PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=')];

        foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'openssl'] as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = ["Zgjerimi {$extension}", $loaded ? 'i ngarkuar' : 'mungon', $loaded];
        }

        $checks[] = ['Mjedisi', (string) Config::get('app.env'), true];
        $checks[] = ['Rruga bazë e URL-së', Config::basePath() === '' ? '(rrënja e domenit)' : Config::basePath(), true];
        $checks[] = ['Zona kohore e PHP', date_default_timezone_get() . ' (' . $now->format('P') . ')', true];

        try {
            $db = Diagnostics::database();
            $dbNow = new DateTimeImmutable($db['now']);
            $drift = abs($dbNow->getTimestamp() - $now->getTimestamp());

            $checks[] = ['Lidhja me databazën', $db['name'] . ' · ' . $db['version'], true];
            $checks[] = ['Ora: PHP / databaza', $now->format('H:i:s') . ' / ' . $dbNow->format('H:i:s') . " (diferenca {$drift} s)", $drift <= 2];
            $checks[] = ['Zona kohore e lidhjes', $db['time_zone'], $db['time_zone'] === $now->format('P')];
            $checks[] = ['Kodimi i lidhjes', $db['charset'] . ' / ' . $db['collation'], $db['charset'] === 'utf8mb4'];
            $checks[] = ['Modaliteti SQL', $db['sql_mode'], str_contains($db['sql_mode'], 'STRICT_ALL_TABLES')];
            $checks[] = ['Tabelat në databazë', (string) $db['tables'], $db['tables'] > 0];
            $checks[] = ['Emri i shkollës (nga databaza)', setting('school_name'), setting('school_name') !== ''];
        } catch (PDOException $e) {
            $checks[] = ['Lidhja me databazën', $e->getMessage(), false];
        }

        foreach (['storage/logs', 'storage/uploads/assignments', 'storage/uploads/submissions', 'public/uploads'] as $dir) {
            $writable = is_writable(root_path($dir));
            $checks[] = ["E shkrueshme: {$dir}", $writable ? 'po' : 'jo', $writable];
        }

        $checks[] = ['Kufiri i ngarkimit', ini_get('upload_max_filesize') . ' për skedar · ' . ini_get('post_max_size') . ' për formular', true];
        $checks[] = ['Sesioni', session_name() . ' · shtegu i cookie-t ' . session_get_cookie_params()['path'], session_status() === PHP_SESSION_ACTIVE];
        $checks[] = ['Formatimi shqip', Format::ucfirst(Format::date($now, 'long')) . ' · ' . Format::number(4.25, 2), true];

        return $this->view('dev/system', [
            'title'  => 'Gjendja e sistemit',
            'checks' => $checks,
            'status' => Session::flashed('status'),
        ]);
    }

    /** Target of the test form: only reached when the CSRF token was valid. */
    public function csrfCheck(): Response
    {
        Session::flash('status', 'Tokeni CSRF u verifikua me sukses.');

        return redirect('/_sistemi');
    }
}
