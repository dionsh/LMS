<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Albanian labels for fixed values, kept in one place so the same concept
 * is always spelled the same way (see docs/ARCHITECTURE.md §12).
 */
final class Labels
{
    /** ISO-8601 day number (1 = Monday) → name. Lowercase, as written mid-sentence. */
    public const DAYS = [
        1 => 'e hënë',
        2 => 'e martë',
        3 => 'e mërkurë',
        4 => 'e enjte',
        5 => 'e premte',
        6 => 'e shtunë',
        7 => 'e diel',
    ];

    /** Short day names for tight spaces (day tabs on phones). */
    public const DAYS_SHORT = [
        1 => 'Hën',
        2 => 'Mar',
        3 => 'Mër',
        4 => 'Enj',
        5 => 'Pre',
        6 => 'Sht',
        7 => 'Die',
    ];

    public const MONTHS = [
        1  => 'janar',
        2  => 'shkurt',
        3  => 'mars',
        4  => 'prill',
        5  => 'maj',
        6  => 'qershor',
        7  => 'korrik',
        8  => 'gusht',
        9  => 'shtator',
        10 => 'tetor',
        11 => 'nëntor',
        12 => 'dhjetor',
    ];

    /** The Kosovo 1–5 scale. */
    public const GRADES = [
        5 => 'Shkëlqyeshëm',
        4 => 'Shumë mirë',
        3 => 'Mirë',
        2 => 'Mjaftueshëm',
        1 => 'Pamjaftueshëm',
    ];

    public const ROLES = [
        'admin'   => 'Administrator',
        'teacher' => 'Mësimdhënës',
        'student' => 'Nxënës',
    ];

    public const USER_STATUSES = [
        'active'   => 'Aktiv',
        'inactive' => 'Joaktiv',
    ];

    /** Grade level → Roman numeral used in class labels (X/13, XI/5, XII/1). */
    public const GRADE_ROMAN = [
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    public const SHIFTS = [
        1 => 'Paradite',
        2 => 'Pasdite',
    ];

    /** Error pages: status => [heading, explanation]. */
    public const HTTP_ERRORS = [
        403 => ['Nuk keni qasje', 'Kjo faqe nuk është e hapur për llogarinë tuaj. Nëse mendoni se është gabim, kontaktoni administratën e shkollës.'],
        404 => ['Faqja nuk u gjet', 'Faqja që kërkuat nuk ekziston ose është zhvendosur.'],
        405 => ['Veprimi nuk lejohet', 'Kjo faqe nuk e pranon këtë lloj kërkese.'],
        413 => ['Skedari është shumë i madh', 'Të dhënat që dërguat e tejkalojnë madhësinë e lejuar. Ju lutemi provoni me skedarë më të vegjël.'],
        419 => ['Sesioni ka skaduar', 'Për sigurinë tuaj, ky formular nuk është më i vlefshëm. Ju lutemi rifreskoni faqen dhe provoni përsëri.'],
        429 => ['Shumë përpjekje', 'Keni bërë shumë përpjekje brenda një kohe të shkurtër. Ju lutemi prisni pak dhe provoni përsëri.'],
        500 => ['Diçka shkoi keq', 'Ndodhi një gabim i papritur. Ju lutemi provoni përsëri më vonë.'],
        503 => ['Platforma është përkohësisht jashtë funksionit', 'Po punojmë për ta rikthyer sa më shpejt. Ju lutemi provoni përsëri më vonë.'],
    ];

    /** @return array{0: string, 1: string} */
    public static function httpError(int $status): array
    {
        return self::HTTP_ERRORS[$status] ?? self::HTTP_ERRORS[500];
    }

    public static function day(int $isoDay): string
    {
        return self::DAYS[$isoDay] ?? '';
    }

    public static function month(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function grade(int $grade): string
    {
        return self::GRADES[$grade] ?? '';
    }

    public static function role(string $role): string
    {
        return self::ROLES[$role] ?? $role;
    }
}
