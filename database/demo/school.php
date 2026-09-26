<?php

declare(strict_types=1);

/*
 * DEVELOPMENT ONLY — fills the current school year with the school's structure
 * from database/demo/school-data.php: 45 classes, 80 teachers (records without
 * sign-in credentials, every class with a homeroom teacher) and 10 student
 * accounts enrolled in X/13, XI/5 and XII/1.
 *
 *   php database/demo/school.php
 *
 * Safe to run again: existing classes and people are reused, not duplicated.
 */

use App\Core\Database;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Usernames;
use App\Support\Format;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

if (config('app.env') !== 'development') {
    fwrite(STDERR, "Refuzohet: ky skript punon vetëm në mjedisin e zhvillimit (app.env = development).\n");
    exit(1);
}

$data = require __DIR__ . '/school-data.php';
$year = AcademicYear::current() ?? exit("Mungon viti shkollor aktual (seed.sql).\n");
$yearId = (int) $year['id'];

/**
 * The teacher's id — an existing record is reused untouched (edits made in the
 * admin panel survive a re-run); otherwise it is created without credentials.
 */
$teacher = static function (string $first, string $last, bool $real): int {
    $existing = User::findByName('teacher', $first, $last);
    if ($existing !== null) {
        return (int) $existing['id'];
    }

    $id = User::create([
        'role'       => 'teacher',
        'username'   => Usernames::suggest($first, $last),
        'first_name' => $first,
        'last_name'  => $last,
        'password'   => null,                    // no sign-in until the admin issues a login slip
    ]);
    TeacherProfile::save($id, 'Prof.', showOnWebsite: $real);

    return $id;
};

Database::transaction(static function () use ($data, $yearId, $teacher): void {
    // 1. Classes: X/1 … XII/15
    $classes = [];
    foreach ([10, 11, 12] as $grade) {
        for ($section = 1; $section <= 15; $section++) {
            $classes[$grade][$section] = SchoolClass::findOrCreate($yearId, $grade, $section, $data['shifts'][$grade]);
        }
    }

    // 2. Homeroom teachers: the five real ones, then placeholders for the other 40 classes
    foreach ($data['real_homerooms'] as [$first, $last, $grade, $section]) {
        SchoolClass::setHomeroomTeacher($classes[$grade][$section], $teacher($first, $last, true));
    }

    $realSlots = array_map(static fn (array $h): string => $h[2] . '/' . $h[3], $data['real_homerooms']);
    $openSlots = [];
    foreach ($classes as $grade => $sections) {
        foreach (array_keys($sections) as $section) {
            if (!in_array($grade . '/' . $section, $realSlots, true)) {
                $openSlots[] = [$grade, $section];
            }
        }
    }

    foreach ($data['placeholder_teachers'] as $index => [$first, $last]) {
        $id = $teacher($first, $last, false);
        if (isset($openSlots[$index])) {
            [$grade, $section] = $openSlots[$index];
            SchoolClass::setHomeroomTeacher($classes[$grade][$section], $id);
        }
    }

    // 3. Student accounts, enrolled in their class
    foreach ($data['students'] as [$first, $last, $grade, $section, $born, $gender]) {
        $existing = User::findByName('student', $first, $last);
        if ($existing === null) {
            $id = User::create([
                'role'                 => 'student',
                'username'             => Usernames::suggest($first, $last),
                'first_name'           => $first,
                'last_name'            => $last,
                'password'             => $data['student_password'],
                'must_change_password' => false,
            ]);
        } else {
            $id = (int) $existing['id'];
            User::updatePassword($id, $data['student_password']);
            User::setStatus($id, 'active');
        }
        StudentProfile::save($id, $born, $gender);
        Enrollment::enroll($id, $classes[$grade][$section]);
    }
});

// Summary
$overview = SchoolClass::overview($yearId);
$teachers = User::teachersOverview($yearId);
$withHomeroom = count(array_filter($teachers, static fn (array $t): bool => $t['homeroom_class_id'] !== null));
$noCredentials = count(array_filter($teachers, static fn (array $t): bool => (int) $t['has_credentials'] === 0));

echo "Viti shkollor {$year['name']}\n";
echo '  Klasa:         ' . count($overview) . ' (me kujdestar: ' . SchoolClass::countWithHomeroom($yearId) . ")\n";
echo '  Mësimdhënës:   ' . count($teachers) . " (kujdestarë: {$withHomeroom}; pa fletë hyrjeje: {$noCredentials})\n\n";

foreach ($overview as $class) {
    if ((int) $class['grade_level'] === 12 && (int) $class['section'] <= 5) {
        printf("  %-7s %s\n", Format::classLabel((int) $class['grade_level'], (int) $class['section']),
            Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name']));
    }
}

echo "\n  Nxënës (llogari):\n";
foreach (User::studentsOverview($yearId) as $student) {
    if ($student['class_id'] !== null) {
        printf("  %-18s %-7s %s\n", $student['username'],
            Format::classLabel((int) $student['grade_level'], (int) $student['section']),
            $student['first_name'] . ' ' . $student['last_name']);
    }
}

echo "\nFjalëkalimi i nxënësve: database/demo/README.md\n";
