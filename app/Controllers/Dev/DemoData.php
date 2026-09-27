<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

/**
 * SAMPLE content for the development style guide only (/_stilet).
 * Names, rooms and events here are fictional placeholders, not school data.
 * Real pages read everything from the database.
 */
final class DemoData
{
    /** "Now" in the demos: a Friday during the 3rd lesson. */
    public const DAY = 5;
    public const PERIOD = 3;
    public const MINUTES_INTO_LESSON = 7;

    private const LESSONS = [
        'mat' => ['Matematikë', 'Prof. Arben Krasniqi', 'Salla 204'],
        'shq' => ['Gjuhë shqipe dhe letërsi', 'Prof. Drita Berisha', 'Salla 105'],
        'ang' => ['Gjuhë angleze', 'Prof. Valbona Hoxha', 'Salla 105'],
        'inf' => ['Informatikë', 'Prof. Blerim Morina', 'Salla 301'],
        'fiz' => ['Fizikë', 'Prof. Ilir Gashi', 'Laboratori i fizikës'],
        'kim' => ['Kimi', 'Prof. Mimoza Rexhepi', 'Laboratori i kimisë'],
        'bio' => ['Biologji', 'Prof. Kushtrim Bytyqi', 'Salla 210'],
        'his' => ['Histori', 'Prof. Teuta Shala', 'Salla 108'],
        'gjg' => ['Gjeografi', 'Prof. Agron Hasani', 'Salla 108'],
        'fil' => ['Filozofi', 'Prof. Luljeta Kelmendi', 'Salla 106'],
        'art' => ['Art figurativ', 'Prof. Faton Ramadani', 'Atelieja'],
        'eds' => ['Edukatë fizike dhe sport', 'Prof. Besnik Rama', 'Palestra'],
        'gje' => ['Gjuhë gjermane', 'Prof. Arta Sylejmani', 'Salla 107'],
        'edq' => ['Edukatë qytetare', 'Prof. Teuta Shala', 'Salla 108'],
    ];

    private const WEEK = [
        1 => ['shq', 'mat', 'fiz', 'ang', 'kim', 'his'],
        2 => ['mat', 'bio', 'shq', 'inf', 'eds', 'gjg'],
        3 => ['fiz', 'mat', 'ang', 'kim', 'fil', 'art'],
        4 => ['shq', 'bio', 'mat', 'gje', 'inf', 'edq'],
        5 => ['mat', 'ang', 'inf', 'fiz', 'his', null],
    ];

    /** @return array<int, array<int, array{subject: string, meta: list<string>}|null>> day => period => lesson (teacher, room) */
    public static function week(): array
    {
        $week = [];
        foreach (self::WEEK as $day => $codes) {
            foreach ($codes as $index => $code) {
                $week[$day][$index + 1] = $code === null ? null : [
                    'subject' => self::LESSONS[$code][0],
                    'meta'    => [self::LESSONS[$code][1], self::LESSONS[$code][2]],
                ];
            }
        }

        return $week;
    }

    public static function user(string $role): array
    {
        return match ($role) {
            'teacher' => ['first_name' => 'Arben', 'last_name' => 'Krasniqi', 'role' => 'teacher'],
            'admin'   => ['first_name' => 'Lirie', 'last_name' => 'Maloku', 'role' => 'admin'],
            default   => ['first_name' => 'Arta', 'last_name' => 'Gashi', 'role' => 'student'],
        };
    }

    public static function homework(): array
    {
        return [
            ['subject' => 'Matematikë', 'title' => 'Ekuacionet kuadratike — ushtrimet 1–12', 'due' => '2026-10-05 23:59', 'status' => ['E re', 'info']],
            ['subject' => 'Gjuhë angleze', 'title' => 'Ese: libri që më ka ndryshuar mendimin', 'due' => '2026-10-06 20:00', 'status' => ['E dorëzuar', 'plain']],
            ['subject' => 'Kimi', 'title' => 'Raporti i laboratorit: titrimi acid–bazë', 'due' => '2026-10-07 23:59', 'status' => ['Kthyer për përmirësim', 'warning']],
        ];
    }

    public static function grades(): array
    {
        return [
            ['grade' => 5, 'subject' => 'Matematikë', 'type' => 'Test', 'date' => '2026-10-01'],
            ['grade' => 4, 'subject' => 'Fizikë', 'type' => 'Përgjigje me gojë', 'date' => '2026-09-30'],
            ['grade' => 4, 'subject' => 'Gjuhë shqipe dhe letërsi', 'type' => 'Detyrë shtëpie', 'date' => '2026-09-29'],
            ['grade' => 3, 'subject' => 'Kimi', 'type' => 'Test', 'date' => '2026-09-25'],
        ];
    }

    public static function announcements(): array
    {
        return [
            ['title' => 'Takimi me prindërit', 'body' => 'E enjte, 8 tetor, ora 17:00, në sallën e madhe.', 'by' => 'Administrata', 'date' => '2026-10-01'],
            ['title' => 'Gara e matematikës — regjistrimi', 'body' => 'Nxënësit e interesuar regjistrohen te mësimdhënësi deri më 12 tetor.', 'by' => 'Prof. Arben Krasniqi', 'date' => '2026-09-30'],
        ];
    }

    public static function students(): array
    {
        return [
            ['name' => 'Arta Gashi', 'username' => 'arta.gashi', 'class' => 'XI-5', 'status' => 'active', 'login' => '2026-10-02 07:41'],
            ['name' => 'Blend Hoxha', 'username' => 'blend.hoxha', 'class' => 'XI-5', 'status' => 'active', 'login' => '2026-10-01 18:12'],
            ['name' => 'Dea Morina', 'username' => 'dea.morina', 'class' => 'X-13', 'status' => 'active', 'login' => null],
            ['name' => 'Endrit Shala', 'username' => 'endrit.shala', 'class' => 'XII-1', 'status' => 'inactive', 'login' => '2026-06-20 10:03'],
        ];
    }

    public static function stories(): array
    {
        return [
            ['category' => 'Arritje', 'title' => 'Dy nxënës të shkollës fitojnë Hackathonin Kombëtar', 'excerpt' => 'Një aplikacion për ndarjen e librave shkollorë mes nxënësve fitoi vendin e parë pas 36 orësh pune.', 'date' => '2026-09-24', 'image' => true],
            ['category' => 'Aktivitete', 'title' => 'Java e shkencës: laboratorët hapen për prindërit', 'excerpt' => '', 'date' => '2026-09-18', 'image' => false],
            ['category' => 'Shpallje', 'title' => 'Orari i konsultimeve për gjysmëvjetorin e parë', 'excerpt' => '', 'date' => '2026-09-12', 'image' => false],
        ];
    }
}
