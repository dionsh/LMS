<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;

/**
 * Creating and changing accounts from the admin panel. Every change is one
 * transaction (user + profile + class) and leaves a line in the activity log.
 * New accounts start WITHOUT credentials; Credentials::issue() gives them a slip.
 */
final class Accounts
{
    /** @param array $values AccountForm::read() values, already validated */
    public static function create(string $role, array $values, int $adminId): int
    {
        return Database::transaction(static function () use ($role, $values, $adminId): int {
            $id = User::create([
                'role'       => $role,
                'username'   => Usernames::suggest($values['first_name'], $values['last_name']),
                'first_name' => $values['first_name'],
                'last_name'  => $values['last_name'],
                'email'      => $values['email'] !== '' ? $values['email'] : null,
                'password'   => null,
            ]);

            if ($values['phone'] !== '') {
                User::updateContact($id, $values['email'] !== '' ? $values['email'] : null, $values['phone']);
            }

            self::saveRoleData($id, $role, $values, null);

            ActivityLog::record($adminId, 'user.created', 'Shtoi ' . self::roleNoun($role) . ' ' . $values['first_name'] . ' ' . $values['last_name'] . '.', 'user', $id);

            return $id;
        });
    }

    /** @param array $user User::details() of the account being edited */
    public static function update(array $user, array $values, int $adminId, int $academicYearId): void
    {
        Database::transaction(static function () use ($user, $values, $adminId, $academicYearId): void {
            $id = (int) $user['id'];

            User::updateDetails(
                $id,
                $values['first_name'],
                $values['last_name'],
                $values['email'] !== '' ? $values['email'] : null,
                $values['phone'] !== '' ? $values['phone'] : null,
            );

            self::saveRoleData($id, $user['role'], $values, $academicYearId);

            ActivityLog::record($adminId, 'user.updated', 'Ndryshoi të dhënat e ' . $values['first_name'] . ' ' . $values['last_name'] . '.', 'user', $id);
        });
    }

    public static function setStatus(array $user, string $status, int $adminId): void
    {
        User::setStatus((int) $user['id'], $status);

        ActivityLog::record(
            $adminId,
            $status === 'active' ? 'user.activated' : 'user.deactivated',
            ($status === 'active' ? 'Aktivizoi' : 'Çaktivizoi') . ' llogarinë e ' . $user['first_name'] . ' ' . $user['last_name'] . '.',
            'user',
            (int) $user['id'],
        );
    }

    /** "nxënësin" / "mësimdhënësin" / "administratorin" — accusative, for log sentences. */
    private static function roleNoun(string $role): string
    {
        return match ($role) {
            'student' => 'nxënësin',
            'teacher' => 'mësimdhënësin',
            default   => 'administratorin',
        };
    }

    /**
     * Profile and class for the role. $academicYearId is only needed when a
     * student may be taken out of their class (editing with "Pa klasë").
     */
    private static function saveRoleData(int $id, string $role, array $values, ?int $academicYearId): void
    {
        if ($role === 'student') {
            StudentProfile::save(
                $id,
                $values['date_of_birth'] !== '' ? $values['date_of_birth'] : null,
                $values['gender'] !== '' ? $values['gender'] : null,
                $values['student_number'] !== '' ? $values['student_number'] : null,
            );

            if ($values['class_id'] !== '') {
                Enrollment::enroll($id, (int) $values['class_id']);
            } elseif ($academicYearId !== null) {
                Enrollment::remove($id, $academicYearId);
            }
        }

        if ($role === 'teacher') {
            TeacherProfile::save(
                $id,
                $values['title'] !== '' ? $values['title'] : null,
                $values['show_on_website'],
                $values['specialization'] !== '' ? $values['specialization'] : null,
                $values['bio'] !== '' ? $values['bio'] : null,
            );
        }
    }
}
