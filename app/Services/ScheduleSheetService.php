<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\ClassSubject;
use App\Models\LessonPeriod;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSheet;
use App\Models\SchoolClass;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Support\Format;
use App\Support\Labels;

/**
 * The timetable in numbers (orari me numra): the school's printed timetable,
 * where each cell holds the number of the teacher, not a subject.
 *
 * The admin keeps the sheet exactly as printed. It is checked on its own
 * terms: every number belongs to a teacher, and no teacher is in two classes
 * at once. Then it is read the way the school reads it: 29 in XI-1 means the
 * subject of XI-1 that teacher 29 teaches. How a number becomes a subject:
 *   1. the class's subjects already given to that teacher (the admin's choice);
 *   2. otherwise the class's subjects among the teacher's own subjects;
 *   3. never a subject the class has given to, or matched with, another of its
 *      teachers on the sheet.
 * The teacher's lessons in the class have to add up to the subject's weekly
 * hours. A teacher with two subjects in one class (4 + 2 lessons) gets both,
 * and the split is flagged for checking.
 *
 * A class whose every number can be read like this is ready. Applying the
 * sheet makes it the timetable of the ready classes, with those teachers.
 */
final class ScheduleSheetService
{
    /** The form field: n[class id][key(day, period)] = teacher number */
    public const FIELD = 'n';

    /** A class's state on the sheet. */
    public const EMPTY = 'empty';       // no numbers yet
    public const ERROR = 'error';       // a number that cannot be used as it is
    public const WAITING = 'waiting';   // a teacher whose subject in the class is not known
    public const BLOCKED = 'blocked';   // would clash with a timetable that stays as it is
    public const READY = 'ready';       // can be applied
    public const APPLIED = 'applied';   // the class's timetable is already this sheet

    public static function key(int $day, int $period): int
    {
        return $day * 100 + $period;
    }

    /** The id of a cell's input ("n-12-1-3"), which is also the key of its error. */
    public static function cellId(int $classId, int $day, int $period): string
    {
        return 'n-' . $classId . '-' . $day . '-' . $period;
    }

    /**
     * Read the submitted sheet: every cell empty or a number from 1 to 999.
     *
     * @param array<int, array<int, string>> $input  class id => key() => value
     * @param array<int, array> $classes  the shift's classes, by id
     * @return array{cells: array<int, array<int, array<int, int>>>, errors: array<string, string>}
     */
    public static function read(array $input, array $classes, array $periods): array
    {
        $cells = [];
        $errors = [];
        foreach (array_keys($classes) as $classId) {
            foreach (Labels::SCHOOL_DAYS as $day) {
                foreach (array_keys($periods) as $period) {
                    $value = $input[$classId][self::key($day, $period)] ?? '';
                    if ($value === '') {
                        continue;
                    }
                    if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 999) {
                        $errors[self::cellId($classId, $day, $period)] = 'Shkruani numrin e mësimdhënësit (1–999).';
                        continue;
                    }
                    $cells[$classId][$day][$period] = (int) $value;
                }
            }
        }

        return ['cells' => $cells, 'errors' => $errors];
    }

    /** Save a shift's sheet; false when nothing changed. */
    public static function save(int $academicYearId, int $shift, array $cells, ?int $adminId): bool
    {
        return Database::transaction(static function () use ($academicYearId, $shift, $cells, $adminId): bool {
            if (self::normalise(ScheduleSheet::forShift($academicYearId, $shift)) === self::normalise($cells)) {
                return false;
            }

            ScheduleSheet::replaceShift($academicYearId, $shift, $cells);
            ActivityLog::record($adminId, 'schedule.sheet_updated', 'Ndryshoi orarin me numra të ndërrimit të ' . Labels::SHIFTS_OF[$shift] . '.', 'shift', $shift);

            return true;
        });
    }

    /**
     * Everything about a shift's sheet: its cells, what is wrong with them,
     * who teaches what, and the state of every class.
     */
    public static function analyse(int $academicYearId, int $shift): array
    {
        $periods = LessonPeriod::forShift($shift);
        $labels = [];
        $classes = [];
        foreach (SchoolClass::overview($academicYearId) as $class) {
            $labels[(int) $class['id']] = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
            if ((int) $class['shift'] === $shift) {
                $classes[(int) $class['id']] = $class + ['label' => $labels[(int) $class['id']]];
            }
        }

        $sheet = ScheduleSheet::forShift($academicYearId, $shift);
        $numbers = TeacherProfile::byNumber();
        $numberOf = [];   // teacher id => number
        foreach ($numbers as $number => $teacher) {
            $numberOf[(int) $teacher['id']] = $number;
        }
        $teaches = TeacherSubject::all();
        $subjects = [];   // class id => subject id => class-subject (id, teacher_id, hours, subject_name)
        foreach (ClassSubject::forYear($academicYearId) as $row) {
            if (isset($classes[(int) $row['class_id']])) {
                $subjects[(int) $row['class_id']][(int) $row['subject_id']] = $row;
            }
        }
        $who = static fn (int $number): string => isset($numbers[$number])
            ? $numbers[$number]['first_name'] . ' ' . $numbers[$number]['last_name'] . ' (' . $number . ')'
            : 'Numri ' . $number;

        $problems = [];   // class id => day => period => why the cell cannot be used
        $errors = [];
        $warnings = [];
        $slots = [];      // number => class id => [[day, period], …] in the order of the week
        $at = [];         // number => day => period => [class id, …]
        $filled = 0;
        $outside = 0;
        foreach ($sheet as $classId => $days) {
            foreach ($days as $day => $row) {
                foreach ($row as $period => $number) {
                    if (!isset($periods[$period]) || !in_array($day, Labels::SCHOOL_DAYS, true)) {
                        $outside++;
                        continue;
                    }
                    $filled++;
                    $slots[$number][$classId][] = [$day, $period];
                    $at[$number][$day][$period][] = $classId;
                }
            }
        }
        ksort($slots);
        if ($outside > 0) {
            $warnings[] = $outside . ' numra janë në orë që ky ndërrim nuk i ka më te Orët e mësimit; nuk shfaqen, dhe ruajtja e orarit i heq.';
        }

        // 1. Numbers that are nobody's
        foreach ($slots as $number => $byClass) {
            if (isset($numbers[$number])) {
                continue;
            }
            foreach ($byClass as $classId => $list) {
                foreach ($list as [$day, $period]) {
                    $problems[$classId][$day][$period] = 'Numri ' . $number . ' nuk i përket asnjë mësimdhënësi.';
                }
            }
            $errors[] = 'Numri ' . $number . ' nuk i përket asnjë mësimdhënësi ('
                . implode(', ', array_map(static fn (int $id): string => $labels[$id], array_keys($byClass))) . ').';
        }

        // 2. A teacher in two classes at once (the classes of a shift share its bell schedule)
        foreach ($at as $number => $days) {
            foreach ($days as $day => $row) {
                foreach ($row as $period => $classIds) {
                    if (count($classIds) < 2) {
                        continue;
                    }
                    $message = $who($number) . ' është në ' . (count($classIds) === 2 ? 'dy' : count($classIds)) . ' klasa njëkohësisht: '
                        . implode(' dhe ', array_map(static fn (int $id): string => $labels[$id], $classIds))
                        . ', ' . Labels::day($day) . ', ora ' . $period . '.';
                    foreach ($classIds as $classId) {
                        $problems[$classId][$day][$period] = $message;
                    }
                    $errors[] = $message;
                }
            }
        }

        // 3. A teacher who has a lesson at that time in a class outside this sheet (e.g. the other shift)
        $times = ScheduleEntry::teacherTimes($academicYearId);
        $busy = self::busy($times, static fn (int $classId): bool => !isset($sheet[$classId]));
        foreach ($slots as $number => $byClass) {
            if (!isset($numbers[$number])) {
                continue;
            }
            foreach ($byClass as $classId => $list) {
                foreach ($list as [$day, $period]) {
                    $other = self::clash($busy, (int) $numbers[$number]['id'], $day, $periods[$period]);
                    if ($other !== null && !isset($problems[$classId][$day][$period])) {
                        $message = $who($number) . ' ka orë në ' . $labels[$other] . ' në të njëjtën kohë me orën ' . $period . ' të ' . $classes[$classId]['label'] . ', ' . Labels::day($day) . '.';
                        $problems[$classId][$day][$period] = $message;
                        $errors[] = $message;
                    }
                }
            }
        }

        // 4. Which subject each number stands for, class by class, and what the class's week becomes
        $matches = [];   // class id => number => match()
        $plans = [];     // class id => ['slots' => day => period => class-subject id, 'teachers' => class-subject id => teacher id]
        $states = [];
        foreach ($classes as $classId => $class) {
            if (!isset($sheet[$classId])) {
                $states[$classId] = self::EMPTY;
                continue;
            }

            $count = [];
            foreach ($slots as $number => $byClass) {
                if (isset($byClass[$classId], $numbers[$number])) {
                    $count[$number] = count($byClass[$classId]);
                }
            }
            $matches[$classId] = self::match($count, $subjects[$classId] ?? [], $numbers, $teaches);

            $plan = ['slots' => [], 'teachers' => []];
            foreach ($matches[$classId] as $number => $match) {
                // Two subjects: the first one's hours take the teacher's first lessons of the week
                $queue = [];
                foreach ($match['subjects'] as $subjectId) {
                    $row = $subjects[$classId][$subjectId];
                    $plan['teachers'][(int) $row['id']] = (int) $numbers[$number]['id'];
                    $queue = array_merge($queue, array_fill(0, max(1, (int) $row['hours']), (int) $row['id']));
                }
                foreach ($queue === [] ? [] : $slots[$number][$classId] as $index => [$day, $period]) {
                    $plan['slots'][$day][$period] = $queue[min($index, count($queue) - 1)];
                }
            }
            $plans[$classId] = $plan;

            $waiting = array_filter($matches[$classId], static fn (array $match): bool => $match['subjects'] === []);
            $states[$classId] = match (true) {
                isset($problems[$classId]) => self::ERROR,
                $waiting !== []            => self::WAITING,
                $plan['slots'] === []      => self::EMPTY,   // only numbers in periods the shift no longer has
                default                    => self::READY,
            };
        }

        // 5. A ready class must not clash with a class of the sheet whose current timetable stays
        do {
            $blockedNow = false;
            $staying = self::busy($times, static fn (int $classId): bool => isset($sheet[$classId]) && $states[$classId] !== self::READY);
            foreach ($states as $classId => $state) {
                if ($state !== self::READY) {
                    continue;
                }
                foreach ($plans[$classId]['slots'] as $day => $row) {
                    foreach ($row as $period => $csId) {
                        $teacherId = $plans[$classId]['teachers'][$csId];
                        $other = self::clash($staying, $teacherId, $day, $periods[$period]);
                        if ($other !== null) {
                            $errors[] = $classes[$classId]['label'] . ' nuk mund të aplikohet ende: ' . $who($numberOf[$teacherId]) . ' ka orë në ' . $labels[$other]
                                . ' në të njëjtën kohë me orën ' . $period . ', ' . Labels::day($day) . ', sipas orarit të tanishëm të ' . $labels[$other] . '.';
                            $states[$classId] = self::BLOCKED;
                            $blockedNow = true;
                            continue 3;
                        }
                    }
                }
            }
        } while ($blockedNow);

        // 6. Ready classes whose timetable already is the sheet
        $current = [];
        foreach (ScheduleEntry::forShift($academicYearId, $shift) as $entry) {
            $current[(int) $entry['class_id']][(int) $entry['day']][(int) $entry['period']] = (int) $entry['class_subject_id'];
        }
        foreach ($states as $classId => $state) {
            if ($state !== self::READY) {
                continue;
            }
            $teachersNow = [];
            foreach ($subjects[$classId] ?? [] as $row) {
                $teachersNow[(int) $row['id']] = $row['teacher_id'] !== null ? (int) $row['teacher_id'] : null;
            }
            if (self::normalise($current[$classId] ?? []) === self::normalise($plans[$classId]['slots'])
                && array_intersect_key($teachersNow, $plans[$classId]['teachers']) == $plans[$classId]['teachers']) {
                $states[$classId] = self::APPLIED;
            }
        }

        // 7. Things to check that do not stop anything
        foreach ($matches as $classId => $byNumber) {
            $label = $classes[$classId]['label'];
            foreach ($byNumber as $number => $match) {
                $count = count($slots[$number][$classId]);
                if ($match['state'] === 'hours') {
                    $row = $subjects[$classId][$match['subjects'][0]];
                    $warnings[] = $label . ': ' . $who($number) . ' ka ' . $count . ' orë ' . $row['subject_name'] . ' në javë, plani ka ' . (int) $row['hours'] . '.';
                } elseif ($match['state'] === 'split') {
                    $parts = array_map(static fn (int $id): string => $subjects[$classId][$id]['subject_name'] . ' (' . (int) $subjects[$classId][$id]['hours'] . ')', $match['subjects']);
                    $warnings[] = $label . ': ' . $who($number) . ' jep ' . implode(' dhe ', $parts) . '. Orët u ndanë sipas radhës në javë; kontrollojini te orari i klasës.';
                }
            }

            $homeroomNumber = $numberOf[(int) $classes[$classId]['teacher_id']] ?? null;
            if ($homeroomNumber !== null && !isset($slots[$homeroomNumber][$classId])) {
                $warnings[] = $label . ': kujdestari i klasës, ' . $who($homeroomNumber) . ', nuk ka orë në të.';
            }

            if (in_array($states[$classId], [self::READY, self::APPLIED], true)) {
                $taught = [];
                foreach ($byNumber as $match) {
                    $taught += array_flip($match['subjects']);
                }
                foreach ($subjects[$classId] ?? [] as $subjectId => $row) {
                    if (!isset($taught[$subjectId])) {
                        $warnings[] = $label . ': ' . $row['subject_name'] . ' nuk ka orë në këtë orar.';
                    }
                }
            }
        }

        // 8. The teachers of the sheet: lessons, classes, subjects, and what is still unknown
        $teachers = [];
        foreach ($slots as $number => $byClass) {
            $teacher = $numbers[$number] ?? null;
            $row = [
                'number'   => $number,
                'teacher'  => $teacher,
                'lessons'  => array_sum(array_map('count', $byClass)),
                'classes'  => [],
                'subjects' => [],
                'open'     => [],
                'has_subjects' => $teacher !== null && ($teaches[(int) $teacher['id']] ?? []) !== [],
            ];
            foreach ($byClass as $classId => $list) {
                $row['classes'][$classes[$classId]['label']] = count($list);
                $match = $matches[$classId][$number] ?? null;
                if ($match === null) {
                    continue;
                }
                foreach ($match['subjects'] as $subjectId) {
                    $row['subjects'][$subjects[$classId][$subjectId]['subject_name']] = true;
                }
                if ($match['subjects'] === []) {
                    $row['open'][] = $classes[$classId]['label'] . ': ' . self::describe($match, $subjects[$classId] ?? [], $who);
                }
            }
            if ($teacher !== null && $row['lessons'] > (int) $teacher['weekly_norm']) {
                $warnings[] = $who($number) . ' ka ' . $row['lessons'] . ' orë në këtë orar, mbi normën prej ' . (int) $teacher['weekly_norm'] . ' orësh.';
            }
            $teachers[$number] = $row;
        }

        return [
            'periods'  => $periods,
            'classes'  => $classes,
            'sheet'    => $sheet,
            'slots'    => count($classes) * count(Labels::SCHOOL_DAYS) * count($periods),
            'filled'   => $filled,
            'problems' => $problems,
            'errors'   => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'teachers' => $teachers,
            'states'   => $states,
            'plans'    => $plans,
        ];
    }

    /**
     * Make the sheet the timetable of every ready class (that $only accepts):
     * its subjects get the sheet's teachers, its week is replaced, its
     * students are told.
     *
     * @param (callable(int): bool)|null $only  which ready classes (by id); all when null
     * @return list<string> the classes whose timetable changed
     */
    public static function apply(int $academicYearId, int $shift, ?int $adminId, ?callable $only = null): array
    {
        return Database::transaction(static function () use ($academicYearId, $shift, $adminId, $only): array {
            $analysis = self::analyse($academicYearId, $shift);

            $rooms = [];   // a room chosen for a lesson stays when the lesson stays
            foreach (ScheduleEntry::forShift($academicYearId, $shift) as $entry) {
                $rooms[(int) $entry['class_id']][(int) $entry['day']][(int) $entry['period']] = [(int) $entry['class_subject_id'], $entry['room_id']];
            }

            $applied = [];
            foreach ($analysis['states'] as $classId => $state) {
                if ($state !== self::READY || ($only !== null && !$only($classId))) {
                    continue;
                }
                $plan = $analysis['plans'][$classId];
                foreach ($plan['teachers'] as $csId => $teacherId) {
                    ClassSubject::setTeacher($csId, $teacherId);
                }

                $cells = [];
                foreach ($plan['slots'] as $day => $row) {
                    foreach ($row as $period => $csId) {
                        [$before, $room] = $rooms[$classId][$day][$period] ?? [null, null];
                        $cells[$day][$period] = ['class_subject_id' => $csId, 'room_id' => $before === $csId && $room !== null ? (int) $room : null];
                    }
                }
                ScheduleEntry::replaceForClass($classId, $cells, $adminId);

                $label = $analysis['classes'][$classId]['label'];
                ScheduleService::notifyStudents($classId, $label);
                $applied[] = $label;
            }

            if ($applied !== []) {
                ActivityLog::record($adminId, 'schedule.sheet_applied',
                    'Aplikoi orarin me numra të ndërrimit të ' . Labels::SHIFTS_OF[$shift] . ' në ' . implode(', ', $applied) . '.', 'shift', $shift);
            }

            return $applied;
        });
    }

    /**
     * Which subject(s) of a class each of its teachers teaches.
     *
     * @param array<int, int> $count  teacher number => lessons in the class
     * @param array<int, array> $subjects  subject id => class-subject
     * @return array<int, array{subjects: list<int>, state: string, candidates: list<int>, free: list<int>}>
     *   state: ok | hours (one subject, other hours than planned) | split (two or more subjects)
     *          | none (no subject of the class) | taken (all by other teachers) | ambiguous
     */
    private static function match(array $count, array $subjects, array $numbers, array $teaches): array
    {
        $numberOf = [];   // teacher id => number, for this class's teachers on the sheet
        foreach (array_keys($count) as $number) {
            $numberOf[(int) $numbers[$number]['id']] = $number;
        }

        // A subject the class has already given to one of them is theirs: the admin's choice comes first
        $taken = [];      // subject id => number
        foreach ($subjects as $id => $subject) {
            if ($subject['teacher_id'] !== null && isset($numberOf[(int) $subject['teacher_id']])) {
                $taken[$id] = $numberOf[(int) $subject['teacher_id']];
            }
        }

        $candidates = [];
        foreach ($count as $number => $lessons) {
            $given = array_keys($taken, $number, true);
            $candidates[$number] = $given !== [] ? $given : array_keys(array_intersect_key($subjects, $teaches[(int) $numbers[$number]['id']] ?? []));
        }

        $result = [];
        $free = static function (int $number) use (&$candidates, &$taken): array {
            return array_values(array_filter($candidates[$number], static fn (int $id): bool => ($taken[$id] ?? $number) === $number));
        };

        do {
            $progress = false;
            // The fewest subjects whose hours add up to the teacher's lessons — when there is only one such choice
            foreach ($candidates as $number => $ids) {
                if (isset($result[$number])) {
                    continue;
                }
                $options = self::combinations($free($number), $subjects, $count[$number]);
                if (count($options) === 1) {
                    $result[$number] = ['subjects' => $options[0], 'state' => count($options[0]) > 1 ? 'split' : 'ok'];
                    foreach ($options[0] as $id) {
                        $taken[$id] = $number;
                    }
                    $progress = true;
                }
            }
            if ($progress) {
                continue;
            }
            // Otherwise a teacher left with one possible subject takes it, even with other hours
            foreach ($candidates as $number => $ids) {
                if (!isset($result[$number]) && count($free($number)) === 1) {
                    $id = $free($number)[0];
                    $result[$number] = ['subjects' => [$id], 'state' => 'hours'];
                    $taken[$id] = $number;
                    $progress = true;
                    break;
                }
            }
        } while ($progress);

        foreach ($candidates as $number => $ids) {
            if (!isset($result[$number])) {
                $left = $free($number);
                $result[$number] = [
                    'subjects' => [],
                    'state'    => $ids === [] ? 'none' : ($left === [] ? 'taken' : 'ambiguous'),
                    'taken_by' => array_filter(array_intersect_key($taken, array_flip($ids)), static fn (int $by): bool => $by !== $number),
                ];
            }
            $result[$number]['candidates'] = $ids;
            $result[$number]['free'] = $free($number);
        }
        ksort($result);

        return $result;
    }

    /** Why a teacher's subject in a class is not known, in a few words. */
    private static function describe(array $match, array $subjects, callable $who): string
    {
        $names = static fn (array $ids): string => implode(' apo ', array_map(static fn (int $id): string => $subjects[$id]['subject_name'], $ids));

        return match ($match['state']) {
            'none'      => 'asnjë nga lëndët e shënuara nuk është në klasë',
            'taken'     => implode('; ', array_map(static fn (int $id, int $number): string => $subjects[$id]['subject_name'] . ' e ka ' . $who($number), array_keys($match['taken_by']), $match['taken_by'])),
            'ambiguous' => $names($match['free']) . '?',
            default     => '',
        };
    }

    /**
     * The smallest sets of these subjects whose weekly hours add up to $lessons.
     *
     * @param list<int> $ids
     * @return list<list<int>>
     */
    private static function combinations(array $ids, array $subjects, int $lessons): array
    {
        $ids = array_slice($ids, 0, 12);
        $found = [];
        for ($mask = 1, $n = count($ids); $mask < (1 << $n); $mask++) {
            $set = [];
            $sum = 0;
            for ($i = 0; $i < $n; $i++) {
                if ($mask & (1 << $i)) {
                    $set[] = $ids[$i];
                    $sum += (int) $subjects[$ids[$i]]['hours'];
                }
            }
            if ($sum === $lessons) {
                $found[] = $set;
            }
        }
        if ($found === []) {
            return [];
        }
        $fewest = min(array_map('count', $found));

        return array_values(array_filter($found, static fn (array $set): bool => count($set) === $fewest));
    }

    /**
     * When teachers are busy, from ScheduleEntry::teacherTimes(), for the classes $include accepts:
     * [teacher id => [day => [[starts, ends, class id], …]]]
     */
    private static function busy(array $times, callable $include): array
    {
        $busy = [];
        foreach ($times as $row) {
            if ($include((int) $row['class_id'])) {
                $busy[(int) $row['teacher_id']][(int) $row['day']][] = [$row['starts_at'], $row['ends_at'], (int) $row['class_id']];
            }
        }

        return $busy;
    }

    /** The class a teacher is busy in during this period of the day, or null. */
    private static function clash(array $busy, int $teacherId, int $day, array $period): ?int
    {
        foreach ($busy[$teacherId][$day] ?? [] as [$starts, $ends, $classId]) {
            if ($starts < $period['ends_at'] && $period['starts_at'] < $ends) {
                return $classId;
            }
        }

        return null;
    }

    /** The same cells in the same order, for comparing. */
    private static function normalise(array $cells): array
    {
        ksort($cells);
        foreach ($cells as &$days) {
            ksort($days);
            foreach ($days as &$periods) {
                if (is_array($periods)) {
                    ksort($periods);
                }
            }
            unset($periods);
        }

        return $cells;
    }
}
