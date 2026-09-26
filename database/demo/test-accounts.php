<?php

declare(strict_types=1);

/*
 * DEVELOPMENT ONLY — creates (or resets) one test account per role, so the
 * portal can be tried before the admin screens for accounts exist (T05).
 * The logins are listed in database/demo/README.md.
 *
 *   php database/demo/test-accounts.php
 *
 * Refuses to run unless config says app.env = development.
 */

use App\Models\LoginAttempt;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

if (config('app.env') !== 'development') {
    fwrite(STDERR, "Refuzohet: ky skript punon vetëm në mjedisin e zhvillimit (app.env = development).\n");
    exit(1);
}

// username => [role, first name, last name, password, status, must change password]
$accounts = [
    'prove.admin'       => ['admin', 'Provë', 'Administrator', 'Prove-Admin-2026', 'active', false],
    'prove.mesimdhenes' => ['teacher', 'Provë', 'Mësimdhënëse', 'Prove-Mesimdhenes-2026', 'active', false],
    'prove.nxenes'      => ['student', 'Provë', 'Nxënëse', 'Prove-Nxenes-2026', 'active', false],
    'prove.fillestar'   => ['student', 'Provë', 'Fillestar', 'mali-libri-deti-47', 'active', true],
    'prove.joaktiv'     => ['student', 'Provë', 'Joaktiv', 'Prove-Joaktiv-2026', 'inactive', false],
];

foreach ($accounts as $username => [$role, $first, $last, $password, $status, $mustChange]) {
    $existing = User::findForLogin($username);

    if ($existing === null) {
        User::create([
            'role' => $role, 'username' => $username, 'first_name' => $first, 'last_name' => $last,
            'password' => $password, 'status' => $status, 'must_change_password' => $mustChange,
        ]);
        $action = 'u krijua';
    } else {
        User::updatePassword((int) $existing['id'], $password, $mustChange);
        User::setStatus((int) $existing['id'], $status);
        $action = 'u rivendos';
    }

    LoginAttempt::clearFor($username);   // start every test run without old failed attempts
    printf("  %-20s %-8s %s\n", $username, $role, $action);
}

echo "\nFjalëkalimet: database/demo/README.md\n";
