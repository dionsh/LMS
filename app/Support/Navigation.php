<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Every navigation menu of the site and the portal, in one place.
 * Items: key (stable id), label (Albanian), path (app-relative URL), icon (sprite id).
 * Pages that are not built yet simply lead to the 404 page until their task is done.
 */
final class Navigation
{
    /** Public website, in menu order. */
    public static function site(): array
    {
        return [
            self::item('home', 'Ballina', '/', 'home', exact: true),
            self::item('about', 'Rreth nesh', '/rreth-nesh', 'building'),
            self::item('news', 'Lajme', '/lajme', 'newspaper'),
            self::item('programs', 'Programet', '/programet', 'book'),
            self::item('staff', 'Stafi', '/stafi', 'users'),
            self::item('contact', 'Kontakti', '/kontakti', 'mail'),
        ];
    }

    /**
     * Portal sidebar for a role, grouped into sections.
     *
     * @return list<array{heading: ?string, items: list<array>}>
     */
    public static function portal(string $role): array
    {
        return match ($role) {
            'student' => [
                self::section(null, [
                    self::item('dashboard', 'Paneli', '/nxenesi', 'dashboard', exact: true),
                    self::item('schedule', 'Orari', '/nxenesi/orari', 'calendar'),
                    self::item('subjects', 'Lëndët', '/nxenesi/lendet', 'book'),
                    self::item('assignments', 'Detyrat', '/nxenesi/detyrat', 'clipboard'),
                    self::item('assessments', 'Vlerësimet', '/nxenesi/vleresimet', 'check-circle'),
                    self::item('grades', 'Notat', '/nxenesi/notat', 'chart'),
                    self::item('announcements', 'Njoftimet', '/nxenesi/njoftimet', 'megaphone'),
                ]),
            ],
            'teacher' => [
                self::section(null, [
                    self::item('dashboard', 'Paneli', '/mesimdhenesi', 'dashboard', exact: true),
                    self::item('schedule', 'Orari', '/mesimdhenesi/orari', 'calendar'),
                    self::item('classes', 'Klasat', '/mesimdhenesi/klasat', 'layers'),
                    self::item('assignments', 'Detyrat', '/mesimdhenesi/detyrat', 'clipboard'),
                    self::item('assessments', 'Vlerësimet', '/mesimdhenesi/vleresimet', 'check-circle'),
                    self::item('announcements', 'Njoftimet', '/mesimdhenesi/njoftimet', 'megaphone'),
                ]),
            ],
            'admin' => [
                self::section(null, [
                    self::item('dashboard', 'Paneli', '/admin', 'dashboard', exact: true),
                ]),
                self::section('Njerëzit', [
                    self::item('students', 'Nxënësit', '/admin/nxenesit', 'graduation'),
                    self::item('teachers', 'Mësimdhënësit', '/admin/mesimdhenesit', 'users'),
                    self::item('users', 'Llogaritë', '/admin/perdoruesit', 'lock'),
                ]),
                self::section('Shkolla', [
                    self::item('classes', 'Klasat', '/admin/klasat', 'layers'),
                    self::item('schedule', 'Orari', '/admin/orari', 'calendar'),
                    self::item('curriculum', 'Plani mësimor', '/admin/plani-mesimor', 'grid'),
                    self::item('subjects', 'Lëndët', '/admin/lendet', 'book'),
                    self::item('rooms', 'Sallat', '/admin/sallat', 'door'),
                    self::item('years', 'Vitet shkollore', '/admin/vitet-shkollore', 'clock'),
                ]),
                self::section('Mësimi', [
                    self::item('assignments', 'Detyrat', '/admin/detyrat', 'clipboard'),
                    self::item('grades', 'Notat', '/admin/notat', 'chart'),
                ]),
                self::section('Komunikimi', [
                    self::item('posts', 'Lajmet', '/admin/lajmet', 'newspaper'),
                    self::item('announcements', 'Njoftimet', '/admin/njoftimet', 'megaphone'),
                    self::item('messages', 'Mesazhet', '/admin/mesazhet', 'message'),
                ]),
                self::section('Sistemi', [
                    self::item('site', 'Faqja e shkollës', '/admin/faqja', 'building'),
                    self::item('settings', 'Cilësimet', '/admin/cilesimet', 'settings'),
                    self::item('activity', 'Aktiviteti', '/admin/aktiviteti', 'activity'),
                ]),
            ],
            default => [],
        };
    }

    /**
     * Bottom tab bar on phones: four destinations + "Më shumë" (opens the sidebar).
     * Admins work on larger screens and use the sidebar only.
     */
    public static function tabbar(string $role): array
    {
        return match ($role) {
            'student' => [
                self::item('dashboard', 'Paneli', '/nxenesi', 'dashboard', exact: true),
                self::item('schedule', 'Orari', '/nxenesi/orari', 'calendar'),
                self::item('assignments', 'Detyrat', '/nxenesi/detyrat', 'clipboard'),
                self::item('grades', 'Notat', '/nxenesi/notat', 'chart'),
            ],
            'teacher' => [
                self::item('dashboard', 'Paneli', '/mesimdhenesi', 'dashboard', exact: true),
                self::item('schedule', 'Orari', '/mesimdhenesi/orari', 'calendar'),
                self::item('classes', 'Klasat', '/mesimdhenesi/klasat', 'layers'),
                self::item('assignments', 'Detyrat', '/mesimdhenesi/detyrat', 'clipboard'),
            ],
            default => [],
        };
    }

    /**
     * Is $item the current page? An explicit $activeKey wins; otherwise the
     * path decides (exact items match only themselves, others also their sub-pages).
     */
    public static function isActive(array $item, string $path, ?string $activeKey = null): bool
    {
        if ($activeKey !== null) {
            return $item['key'] === $activeKey;
        }

        return $path === $item['path']
            || (!$item['exact'] && str_starts_with($path, rtrim($item['path'], '/') . '/'));
    }

    private static function section(?string $heading, array $items): array
    {
        return ['heading' => $heading, 'items' => $items];
    }

    private static function item(string $key, string $label, string $path, string $icon, bool $exact = false): array
    {
        return ['key' => $key, 'label' => $label, 'path' => $path, 'icon' => $icon, 'exact' => $exact];
    }
}
