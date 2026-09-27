<?php

declare(strict_types=1);

/*
 * DEVELOPMENT ONLY — fills the current school year with the school's structure
 * from database/demo/school-data.php:
 *   - 37 classes (X-1…X-15 afternoon; XI-1…XI-7, XII-1…XII-15 morning), each
 *     with its grade's subjects and a homeroom teacher;
 *   - 80 teachers (records without sign-in credentials): the 22 real homeroom
 *     teachers of the morning shift and 58 placeholders who teach every class;
 *   - demo weekly hours wherever the curriculum has none yet;
 *   - 10 student accounts enrolled in X-13, XI-5 and XII-1.
 *
 *   php database/demo/school.php
 *
 * Safe to run again: existing classes, people and assignments are reused, and
 * anything changed in the admin panel is left as it is.
 */

use App\Core\Database;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LessonPeriod;
use App\Models\ScheduleEntry;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Services\Usernames;
use App\Support\Format;
use App\Support\Labels;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

if (config('app.env') !== 'development') {
    fwrite(STDERR, "Refuzohet: ky skript punon vetëm në mjedisin e zhvillimit (app.env = development).\n");
    exit(1);
}

/**
 * Fill the week of every class without a timetable: each subject as many times
 * as its weekly hours, at most twice a day, and never a teacher in two classes
 * at overlapping clock times. The lesson with the fewest possible slots is
 * placed first; a dead end starts the class again. The random numbers are
 * seeded, so the demo timetable is the same on every machine.
 */
function buildTimetables(int $yearId): void
{
    mt_srand(20260921);   // the date on the school's official timetable

    $busy = [];           // teacher id => day => [[start, end], …]
    foreach (ScheduleEntry::teacherTimes($yearId) as $row) {
        $busy[(int) $row['teacher_id']][(int) $row['day']][] = [$row['starts_at'], $row['ends_at']];
    }
    $free = static function (?int $teacher, int $day, array $period) use (&$busy): bool {
        foreach ($teacher === null ? [] : ($busy[$teacher][$day] ?? []) as [$start, $end]) {
            if ($start < $period['ends_at'] && $period['starts_at'] < $end) {
                return false;
            }
        }
        return true;
    };

    $subjectsByClass = [];
    foreach (ClassSubject::forYear($yearId) as $row) {
        $subjectsByClass[(int) $row['class_id']][] = $row;
    }

    foreach (SchoolClass::overview($yearId) as $class) {
        $classId = (int) $class['id'];
        if ((int) $class['lessons'] > 0) {
            continue;   // already has a timetable (demo or entered by the admin)
        }

        $periods = LessonPeriod::forShift((int) $class['shift']);
        $lessons = [];
        foreach ($subjectsByClass[$classId] ?? [] as $row) {
            for ($i = 0; $i < (int) $row['hours']; $i++) {
                $lessons[] = ['cs' => (int) $row['id'], 'teacher' => $row['teacher_id'] !== null ? (int) $row['teacher_id'] : null];
            }
        }

        for ($attempt = 1; $attempt <= 300; $attempt++) {
            $grid = [];
            $perDay = [];
            $remaining = $lessons;
            while ($remaining !== []) {
                // For each lesson still to place: the slots it could go into
                $best = null;
                foreach ($remaining as $index => $lesson) {
                    $options = [];
                    foreach (Labels::SCHOOL_DAYS as $day) {
                        if (($perDay[$day][$lesson['cs']] ?? 0) >= 2) {
                            continue;
                        }
                        foreach ($periods as $number => $period) {
                            if (!isset($grid[$day][$number]) && $free($lesson['teacher'], $day, $period)) {
                                $options[] = [$day, $number];
                            }
                        }
                    }
                    if ($best === null || count($options) < count($best[1]) || (count($options) === count($best[1]) && mt_rand(0, 1) === 1)) {
                        $best = [$index, $options];
                    }
                }
                if ($best[1] === []) {
                    continue 2;   // dead end: start this class again
                }
                [$day, $number] = $best[1][mt_rand(0, count($best[1]) - 1)];
                $lesson = $remaining[$best[0]];
                $grid[$day][$number] = $lesson;
                $perDay[$day][$lesson['cs']] = ($perDay[$day][$lesson['cs']] ?? 0) + 1;
                unset($remaining[$best[0]]);
            }

            foreach ($grid as $day => $row) {
                foreach ($row as $number => $lesson) {
                    ScheduleEntry::create($classId, $day, $number, $lesson['cs'], null, null);
                    if ($lesson['teacher'] !== null) {
                        $busy[$lesson['teacher']][$day][] = [$periods[$number]['starts_at'], $periods[$number]['ends_at']];
                    }
                }
            }
            continue 2;   // next class
        }

        fwrite(STDERR, 'Kujdes: orari i klasës ' . Format::classLabel((int) $class['grade_level'], (int) $class['section']) . " nuk u plotësua.\n");
    }
}

$data = require __DIR__ . '/school-data.php';
$year = AcademicYear::current() ?? exit("Mungon viti shkollor aktual (seed.sql).\n");
$yearId = (int) $year['id'];
$subjectIds = array_column(Subject::options(), 'id', 'name');

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

Database::transaction(static function () use ($data, $yearId, $teacher, $subjectIds): void {
    // 1. Demo weekly hours, only where the curriculum has none yet
    foreach ($data['demo_hours'] as $grade => $hours) {
        foreach ($hours as $subject => $count) {
            Curriculum::setHoursIfEmpty($grade, (int) $subjectIds[$subject], $count);
        }
    }

    // 2. Classes in their grade's shift, each with the grade's subjects
    $shifts = GradeLevel::shifts();
    $classes = [];
    foreach ($data['classes'] as $grade => $count) {
        for ($section = 1; $section <= $count; $section++) {
            $classes[$grade][$section] = SchoolClass::findOrCreate($yearId, $grade, $section, $shifts[$grade]);
        }
    }
    ClassSubject::addFromCurriculum($yearId);

    // 3. Homeroom teachers: the 22 real ones of the morning shift…
    foreach ($data['real_homerooms'] as [$first, $last, $grade, $section]) {
        SchoolClass::setHomeroomTeacher($classes[$grade][$section], $teacher($first, $last, true));
    }

    // …and placeholders (with their subjects) for the classes still without one
    $open = [];
    foreach ($classes as $grade => $sections) {
        foreach ($sections as $section => $classId) {
            if (!in_array([$grade, $section], array_map(static fn (array $h): array => [$h[2], $h[3]], $data['real_homerooms']), true)) {
                $open[] = $classId;
            }
        }
    }

    $placeholders = [];
    foreach ($data['placeholder_teachers'] as $index => [$first, $last, $subjects]) {
        $id = $teacher($first, $last, false);
        if (TeacherSubject::forTeacher($id) === []) {
            TeacherSubject::save($id, array_map(static fn (string $name): int => (int) $subjectIds[$name], $subjects));
        }
        if (isset($open[$index])) {
            SchoolClass::setHomeroomTeacher($open[$index], $id);
        }
        $placeholders[] = $id;
    }

    // 4. Who teaches what. The test teacher account first (if it exists)…
    $test = User::findForLogin($data['test_teacher']['username']);
    $rows = ClassSubject::forYear($yearId);
    if ($test !== null) {
        TeacherSubject::add((int) $test['id'], (int) $subjectIds[$data['test_teacher']['subject']]);
        foreach ($rows as &$row) {
            if ($row['teacher_id'] === null && $row['subject_name'] === $data['test_teacher']['subject']
                && in_array([(int) $row['grade_level'], (int) $row['section']], $data['test_teacher']['classes'], true)) {
                ClassSubject::setTeacher((int) $row['id'], (int) $test['id']);
                $row['teacher_id'] = (int) $test['id'];
            }
        }
        unset($row);
    }

    // …then every subject still without a teacher goes to the least-loaded placeholder who teaches it
    $load = array_fill_keys($placeholders, 0);
    $teaches = [];
    foreach ($placeholders as $id) {
        foreach (TeacherSubject::forTeacher($id) as $subjectId) {
            $teaches[$subjectId][] = $id;
        }
    }
    foreach ($rows as $row) {
        if ($row['teacher_id'] !== null && isset($load[(int) $row['teacher_id']])) {
            $load[(int) $row['teacher_id']] += (int) $row['hours'];
        }
    }
    foreach ($rows as $row) {
        $candidates = $teaches[(int) $row['subject_id']] ?? [];
        if ($row['teacher_id'] !== null || $candidates === []) {
            continue;
        }
        usort($candidates, static fn (int $a, int $b): int => [$load[$a], $a] <=> [$load[$b], $b]);
        ClassSubject::setTeacher((int) $row['id'], $candidates[0]);
        $load[$candidates[0]] += (int) $row['hours'];
    }

    // 5. A demo timetable for every class that has none yet
    buildTimetables($yearId);

    // 6. Student accounts, enrolled in their class
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
$noCredentials = count(array_filter($teachers, static fn (array $t): bool => (int) $t['has_credentials'] === 0));
[$subjectsTotal, $subjectsAssigned] = ClassSubject::assignmentCounts($yearId);

echo "Viti shkollor {$year['name']}\n";
echo '  Klasa:         ' . count($overview) . ' (me kujdestar: ' . SchoolClass::countWithHomeroom($yearId) . ")\n";
echo '  Mësimdhënës:   ' . count($teachers) . " (pa fletë hyrjeje: {$noCredentials})\n";
echo "  Lëndë në klasa: {$subjectsTotal} (me mësimdhënës: {$subjectsAssigned})\n";
echo '  Orari:         ' . array_sum(array_column($overview, 'lessons')) . ' orë në javë (përplasje: ' . count(ScheduleEntry::clashes($yearId)) . ")\n\n";

foreach ($overview as $class) {
    if ((int) $class['grade_level'] >= 11) {
        printf("  %-7s %-26s %2d orë në javë\n", Format::classLabel((int) $class['grade_level'], (int) $class['section']),
            Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name']),
            (int) $class['planned_hours']);
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
