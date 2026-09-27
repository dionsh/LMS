<?php

declare(strict_types=1);

/*
 * DEVELOPMENT ONLY — fills the current school year from database/demo/:
 *   - 45 classes (X-1…X-15 and XI-8…XI-15 afternoon; XI-1…XI-7 and
 *     XII-1…XII-15 morning), each with its grade's subjects (seed.sql);
 *   - the school's 72 teachers with their timetable numbers (staff.php),
 *     records without sign-in credentials, and the morning classes' real
 *     homeroom teachers;
 *   - DEMO teachers ("Demo Matematikë 1" …) for every subject whose real
 *     teacher is not known yet, at most 20 lessons a week each, and for the
 *     afternoon classes' homerooms;
 *   - the official morning timetable in numbers (timetable-morning.php), made
 *     the timetable of every class whose teachers' subjects are known;
 *   - a demo timetable for every class that has none;
 *   - the morning's daily duty (duty-morning.php, from the official timetable);
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
use App\Models\Duty;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LessonPeriod;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSheet;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Services\ScheduleSheetService;
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
$staff = require __DIR__ . '/staff.php';
$duty = require __DIR__ . '/duty-morning.php';
$morning = require __DIR__ . '/timetable-morning.php';
$year = AcademicYear::current() ?? exit("Mungon viti shkollor aktual (seed.sql).\n");
$yearId = (int) $year['id'];
$subjectIds = array_column(Subject::options(), 'id', 'name');

/**
 * The teacher's id — an existing record is reused untouched (edits made in the
 * admin panel survive a re-run); otherwise it is created without credentials.
 */
$teacher = static function (string $first, string $last, bool $real, ?int $number = null): int {
    $existing = User::findByName('teacher', $first, $last);
    if ($existing !== null) {
        if ($number !== null) {
            TeacherProfile::setNumberIfMissing((int) $existing['id'], $number);
        }
        return (int) $existing['id'];
    }

    $id = User::create([
        'role'       => 'teacher',
        'username'   => Usernames::suggest($first, $last),
        'first_name' => $first,
        'last_name'  => $last,
        'password'   => null,                    // no sign-in until the admin issues a login slip
    ]);
    TeacherProfile::save($id, $real ? 'Prof.' : null, showOnWebsite: $real, timetableNumber: $number);

    return $id;
};

Database::transaction(static function () use ($data, $staff, $duty, $morning, $yearId, $teacher, $subjectIds): void {
    // 1. Classes in their shift, each with its grade's subjects
    $classes = [];
    foreach ($data['classes'] as [$grade, $from, $to, $shift]) {
        for ($section = $from; $section <= $to; $section++) {
            $classes[$grade][$section] = SchoolClass::findOrCreate($yearId, $grade, $section, $shift);
        }
    }
    ClassSubject::addFromCurriculum($yearId);

    // …and the elective some classes take in place of Mësim zgjedhor (same lessons and teacher slot)
    $subjectRows = array_column(Subject::options(), null, 'name');
    foreach ($data['electives'] as $name => $classList) {
        $elective = $subjectRows[$name];
        foreach ($classList as [$grade, $section]) {
            foreach (ClassSubject::forClass($classes[$grade][$section]) as $row) {
                if ((int) $row['subject_id'] !== (int) $elective['fills_subject_id']) {
                    continue;
                }
                ClassSubject::setSubject((int) $row['id'], (int) $elective['id']);
                if ($row['teacher_id'] !== null && $row['teacher_first_name'] === 'Demo') {
                    TeacherSubject::add((int) $row['teacher_id'], (int) $elective['id']);   // the demo teacher keeps it
                }
            }
        }
    }

    // 2. The school's teachers with their timetable numbers (and subjects, once known)
    $byNumber = [];
    foreach ($staff as $number => [$first, $last, $subjects]) {
        $id = TeacherProfile::teacherByNumber($number) ?? $teacher($first, $last, true, $number);
        if ($subjects !== [] && TeacherSubject::forTeacher($id) === []) {
            TeacherSubject::save($id, array_map(static fn (string $name): int => (int) $subjectIds[$name], $subjects));
        }
        $byNumber[$number] = $id;
    }

    // 3. Homeroom teachers of the morning classes (official timetable)
    foreach ($data['homerooms'] as [$grade, $section, $number]) {
        SchoolClass::setHomeroomTeacher($classes[$grade][$section], $byNumber[$number]);
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

    // …then every subject still without a teacher goes to a DEMO teacher of that subject
    // with room left under the norm ("Demo Matematikë 1", "Demo Matematikë 2", …)
    $load = [];
    foreach ($rows as $row) {
        if ($row['teacher_id'] !== null) {
            $load[(int) $row['teacher_id']] = ($load[(int) $row['teacher_id']] ?? 0) + (int) $row['hours'];
        }
    }
    $demo = [];   // subject id => [teacher id, …]
    foreach ($rows as $row) {
        if ($row['teacher_id'] !== null) {
            continue;
        }
        $subjectId = (int) $row['subject_id'];
        $chosen = null;
        for ($n = 1; $chosen === null; $n++) {
            $id = $demo[$subjectId][$n - 1] ?? null;
            if ($id === null) {
                $id = $teacher('Demo', $row['subject_name'] . ' ' . $n, false);
                TeacherSubject::save($id, [$subjectId]);
                $demo[$subjectId][] = $id;
            }
            if (($load[$id] ?? 0) + (int) $row['hours'] <= $data['demo_norm']) {
                $chosen = $id;
            }
        }
        ClassSubject::setTeacher((int) $row['id'], $chosen);
        $load[$chosen] = ($load[$chosen] ?? 0) + (int) $row['hours'];
    }

    // 5. Classes still without a homeroom teacher (the afternoon ones) get one of their demo teachers
    $homeroomOf = [];
    foreach (SchoolClass::overview($yearId) as $class) {
        if ($class['teacher_id'] !== null) {
            $homeroomOf[(int) $class['teacher_id']] = true;
        }
    }
    foreach (SchoolClass::overview($yearId) as $class) {
        if ($class['teacher_id'] !== null) {
            continue;
        }
        foreach (ClassSubject::forClass((int) $class['id']) as $subject) {
            $candidate = $subject['teacher_id'] !== null ? (int) $subject['teacher_id'] : null;
            if ($candidate !== null && $subject['teacher_first_name'] === 'Demo' && !isset($homeroomOf[$candidate])) {
                SchoolClass::setHomeroomTeacher((int) $class['id'], $candidate);
                $homeroomOf[$candidate] = true;
                break;
            }
        }
    }

    // 6. The official morning timetable in numbers (unless already there). It becomes the
    //    timetable of every class whose numbers can all be read as subjects, where the class
    //    has no timetable yet or only the demo one (lessons of teachers without a number)
    if (!ScheduleSheet::hasShift($yearId, 1)) {
        $grades = ['X' => 10, 'XI' => 11, 'XII' => 12];
        $cells = [];
        foreach ($morning as $label => $days) {
            [$grade, $section] = explode('-', $label);
            foreach (array_values($days) as $day => $line) {
                foreach (preg_split('/\s+/', trim($line)) as $period => $number) {
                    $cells[$classes[$grades[$grade]][(int) $section]][$day + 1][$period + 1] = (int) $number;
                }
            }
        }
        ScheduleSheet::replaceShift($yearId, 1, $cells);
    }
    ScheduleSheetService::apply($yearId, 1, null, static function (int $classId): bool {
        foreach (ScheduleEntry::forClass($classId) as $lesson) {
            if ($lesson['timetable_number'] !== null) {
                return false;
            }
        }
        return true;
    });

    // 7. A demo timetable for every class that has none yet
    buildTimetables($yearId);

    // 8. The morning's daily duty, as on the official timetable (unless already set)
    if (!Duty::hasShift($yearId, 1)) {
        $postIds = array_column(Duty::posts(), 'id', 'name');
        $cells = [];
        foreach ($duty as $day => $posts) {
            foreach ($posts as $post => $numbers) {
                if (!isset($postIds[$post])) {
                    fwrite(STDERR, "Kujdes: vendi i kujdestarisë \"{$post}\" nuk ekziston; u anashkalua.\n");
                    continue;
                }
                foreach (array_values($numbers) as $index => $number) {
                    $cells[$day][(int) $postIds[$post]][$index + 1] = $byNumber[$number];
                }
            }
        }
        Duty::replaceShift($yearId, 1, $cells);
    }

    // 9. Student accounts, enrolled in their class
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
echo '  Orari:         ' . array_sum(array_column($overview, 'lessons')) . ' orë në javë (përplasje: ' . count(ScheduleEntry::clashes($yearId)) . ")\n";
$sheet = ScheduleSheetService::analyse($yearId, 1);
$states = array_count_values($sheet['states']);
echo '  Orari me numra (paradite): ' . $sheet['filled'] . ' orë, ' . count($sheet['teachers']) . ' mësimdhënës, ' . count($sheet['errors']) . ' probleme; '
    . 'klasa në orar: ' . ($states[ScheduleSheetService::APPLIED] ?? 0) . ', gati: ' . ($states[ScheduleSheetService::READY] ?? 0)
    . ', presin lëndët: ' . ($states[ScheduleSheetService::WAITING] ?? 0) . "\n\n";

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
