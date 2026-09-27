<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Duty;
use App\Models\User;
use App\Support\Format;
use App\Support\Labels;

/**
 * The daily duty roster (kujdestaria e ditës) of a shift: reading the admin's
 * grid, checking it, saving it, and the posts it is made of.
 */
final class DutyRoster
{
    /**
     * Read and check the submitted grid.
     *
     * @param array<string, string> $input field "d{day}-p{post}-{place}" => teacher id ('' = nobody)
     * @return array{cells: array<int, array<int, array<int, int>>>, errors: array<string, string>}
     */
    public static function read(array $input, array $posts): array
    {
        $teachers = [];
        foreach (User::teacherOptions() as $teacher) {
            $teachers[(int) $teacher['id']] = $teacher;
        }

        $cells = [];
        $errors = [];
        $seen = [];   // day => teacher id => field
        foreach (Labels::SCHOOL_DAYS as $day) {
            foreach ($posts as $post) {
                for ($place = 1; $place <= (int) $post['places']; $place++) {
                    $field = self::field($day, (int) $post['id'], $place);
                    $value = $input[$field] ?? '';
                    if ($value === '') {
                        continue;
                    }
                    if (!ctype_digit($value) || !isset($teachers[(int) $value])) {
                        $errors[$field] = 'Zgjidhni mësimdhënësin nga lista.';
                        continue;
                    }
                    $teacherId = (int) $value;
                    if (isset($seen[$day][$teacherId])) {
                        $name = $teachers[$teacherId]['first_name'] . ' ' . $teachers[$teacherId]['last_name'];
                        $errors[$field] = $name . ' ka tashmë kujdestari ' . Labels::DAYS_ON[$day] . '.';
                        continue;
                    }
                    $seen[$day][$teacherId] = true;
                    $cells[$day][(int) $post['id']][$place] = $teacherId;
                }
            }
        }

        return ['cells' => $cells, 'errors' => $errors];
    }

    public static function save(int $academicYearId, int $shift, array $cells, int $adminId): void
    {
        Database::transaction(static function () use ($academicYearId, $shift, $cells, $adminId): void {
            Duty::replaceShift($academicYearId, $shift, $cells);
            ActivityLog::record($adminId, 'duty.updated', 'Ndryshoi kujdestarinë e ditës të ndërrimit ' . Labels::shift($shift, true) . '.', 'shift', $shift);
        });
    }

    public static function createPost(string $name, int $places, int $adminId): void
    {
        Database::transaction(static function () use ($name, $places, $adminId): void {
            $id = Duty::createPost($name, $places);
            ActivityLog::record($adminId, 'duty.post_created', 'Shtoi vendin e kujdestarisë “' . $name . '”.', 'duty_post', $id);
        });
    }

    public static function updatePost(array $post, string $name, int $places, int $adminId): void
    {
        Database::transaction(static function () use ($post, $name, $places, $adminId): void {
            Duty::updatePost((int) $post['id'], $name, $places);
            ActivityLog::record($adminId, 'duty.post_updated', 'Ndryshoi vendin e kujdestarisë “' . $name . '”.', 'duty_post', (int) $post['id']);
        });
    }

    public static function deletePost(array $post, int $adminId): void
    {
        Database::transaction(static function () use ($post, $adminId): void {
            Duty::deletePost((int) $post['id']);
            ActivityLog::record($adminId, 'duty.post_deleted', 'Hoqi vendin e kujdestarisë “' . $post['name'] . '”.', 'duty_post', (int) $post['id']);
        });
    }

    /** Form field name of one place on one day: "d1-p3-2". */
    public static function field(int $day, int $postId, int $place): string
    {
        return 'd' . $day . '-p' . $postId . '-' . $place;
    }

    /** "Naser Tahiri" — how a teacher is written on the roster. */
    public static function name(array $teacher): string
    {
        return Format::personName(null, $teacher['first_name'], $teacher['last_name']);
    }
}
