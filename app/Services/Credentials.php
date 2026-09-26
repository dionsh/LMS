<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\Format;
use App\Support\Labels;

/**
 * Issues login slips: a fresh temporary password per person (which must be
 * changed at first sign-in). Any earlier password or slip stops working and a
 * sign-in lock on the account is lifted. Passwords are never logged.
 */
final class Credentials
{
    /**
     * @param list<array> $people rows from User::search()/details() (id, username, names, role, class/title fields)
     * @return list<array{name: string, username: string, detail: string, password: string}> slip contents
     */
    public static function issue(array $people, int $adminId): array
    {
        return Database::transaction(static function () use ($people, $adminId): array {
            $slips = [];

            foreach ($people as $person) {
                $password = TemporaryPassword::generate();
                User::updatePassword((int) $person['id'], $password, true);
                LoginAttempt::clearFor($person['username']);

                $slips[] = [
                    'name'     => Format::personName($person['role'] === 'teacher' ? ($person['title'] ?? null) : null, $person['first_name'], $person['last_name']),
                    'username' => $person['username'],
                    'detail'   => self::detail($person),
                    'password' => $password,
                ];
            }

            if (count($people) === 1) {
                ActivityLog::record($adminId, 'user.credentials_issued', 'Lëshoi fletë hyrjeje për ' . $people[0]['first_name'] . ' ' . $people[0]['last_name'] . '.', 'user', (int) $people[0]['id']);
            } elseif ($people !== []) {
                ActivityLog::record($adminId, 'user.credentials_issued', 'Lëshoi ' . count($people) . ' fletë hyrjeje.');
            }

            return $slips;
        });
    }

    /** "Klasa XII/1", "Mësimdhënës", "Administrator" — the line under the name on the slip. */
    private static function detail(array $person): string
    {
        $grade = $person['class_grade'] ?? null;

        if ($person['role'] === 'student' && $grade !== null) {
            return 'Nxënës · Klasa ' . Format::classLabel((int) $grade, (int) $person['class_section']);
        }

        return Labels::role($person['role']);
    }
}
