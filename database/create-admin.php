<?php

declare(strict_types=1);

/*
 * Creates an administrator account — used once, to create the school's first
 * admin (after that, admins manage accounts from the portal).
 *
 *   php database/create-admin.php
 *   php database/create-admin.php --first=Lirie --last=Maloku --email=lirie@shkolla.edu
 *
 * A username and a temporary password are generated and shown ONCE. At the
 * first sign-in the admin must choose their own password.
 */

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TemporaryPassword;
use App\Services\Usernames;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

/** Read a line from the terminal (or return the value given as an option). */
function ask(string $label, ?string $given, bool $optional = false): string
{
    if ($given !== null) {
        return trim($given);
    }

    do {
        echo $label . ($optional ? ' (opsionale, Enter për ta kapërcyer)' : '') . ': ';
        $value = trim((string) fgets(STDIN));
    } while ($value === '' && !$optional);

    return $value;
}

function fail(string $message): never
{
    fwrite(STDERR, 'Gabim: ' . $message . PHP_EOL);
    exit(1);
}

$options = getopt('', ['first:', 'last:', 'email:']);

echo PHP_EOL . 'Krijimi i një llogarie administratori — ' . setting('school_name') . PHP_EOL . PHP_EOL;

$first = ask('Emri', $options['first'] ?? null);
$last = ask('Mbiemri', $options['last'] ?? null);
$email = mb_strtolower(ask('Email-i', $options['email'] ?? null, true));

if (mb_strlen($first) > 60 || mb_strlen($last) > 60) {
    fail('Emri dhe mbiemri mund të kenë deri në 60 karaktere.');
}
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fail('Email-i nuk është i vlefshëm.');
}
if ($email !== '' && User::emailTaken($email)) {
    fail('Ky email përdoret nga një llogari tjetër.');
}

$username = Usernames::suggest($first, $last);
$password = TemporaryPassword::generate();

$id = User::create([
    'role'                 => 'admin',
    'username'             => $username,
    'first_name'           => $first,
    'last_name'            => $last,
    'email'                => $email !== '' ? $email : null,
    'password'             => $password,
    'must_change_password' => true,
]);

ActivityLog::record(null, 'user.created', "Llogaria e administratorit {$first} {$last} u krijua nga rreshti i komandave.", 'user', $id);

echo PHP_EOL . 'Llogaria u krijua.' . PHP_EOL . PHP_EOL;
echo '  Emri i përdoruesit:       ' . $username . PHP_EOL;
echo '  Fjalëkalimi i përkohshëm: ' . $password . PHP_EOL . PHP_EOL;
echo 'Ruajeni tani: fjalëkalimi nuk shfaqet më. Në hyrjen e parë (' . url('/hyr') . ')' . PHP_EOL;
echo 'do t’ju kërkohet të zgjidhni fjalëkalimin tuaj.' . PHP_EOL . PHP_EOL;
