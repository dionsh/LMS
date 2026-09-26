<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

use App\Controllers\Controller;
use App\Core\Response;
use App\Models\LessonPeriod;

/**
 * /_stilet — the design system, every component in context (development only).
 * Sample content comes from DemoData; lesson times come from the real bell schedule.
 */
final class StyleGuideController extends Controller
{
    private const ROLES = ['nxenes' => 'student', 'mesimdhenes' => 'teacher', 'admin' => 'admin'];

    public function index(): Response
    {
        return $this->view('dev/styleguide', [
            'title'    => 'Sistemi i dizajnit',
            'styles'   => ['styleguide'],
            'scripts'  => ['styleguide'],
            'periods'  => LessonPeriod::forShift(1),
            'week'     => DemoData::week(),
            'homework' => DemoData::homework(),
            'grades'   => DemoData::grades(),
            'students' => DemoData::students(),
            'stories'  => DemoData::stories(),
            'icons'    => self::iconNames(),
        ]);
    }

    /** The portal shell with a sample student dashboard. ?roli=nxenes|mesimdhenes|admin switches the navigation. */
    public function portal(): Response
    {
        $roleParam = (string) $this->request->query('roli', 'nxenes');
        $role = self::ROLES[$roleParam] ?? 'student';

        return $this->view('dev/portal-demo', [
            'title'         => 'Paneli',
            'user'          => DemoData::user($role),
            'active'        => 'dashboard',
            'unread'        => 3,
            'context'       => 'Shembull · E premte, 2 tetor 2026',
            'roleParam'     => array_search($role, self::ROLES, true),
            'periods'       => LessonPeriod::forShift(1),
            'week'          => DemoData::week(),
            'homework'      => DemoData::homework(),
            'grades'        => DemoData::grades(),
            'announcements' => DemoData::announcements(),
        ], 'portal');
    }

    /** The sign-in layout. ?gabim shows the error state. */
    public function auth(): Response
    {
        return $this->view('dev/auth-demo', [
            'title'     => 'Hyr',
            'showError' => $this->request->query('gabim') !== null,
        ], 'auth');
    }

    /** @return list<string> symbol ids in the icon sprite */
    private static function iconNames(): array
    {
        $sprite = (string) file_get_contents(root_path('public/assets/img/icons.svg'));
        preg_match_all('/<symbol id="([a-z0-9\-]+)"/', $sprite, $matches);

        return $matches[1];
    }
}
