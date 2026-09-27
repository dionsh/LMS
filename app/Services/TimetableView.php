<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LessonPeriod;
use App\Models\ScheduleEntry;
use App\Support\Labels;
use App\Support\Timetable;
use DateTimeImmutable;

/**
 * What a timetable page (and a dashboard's "today") shows: the week, today's
 * lessons, and the lesson in progress or next — for a class or a teacher.
 */
final class TimetableView
{
    /**
     * A class's week (what its students see).
     *
     * @param array $class SchoolClass::find()/forStudent(): id, shift
     */
    public static function forClass(array $class, DateTimeImmutable $now): array
    {
        $periods = LessonPeriod::forShift((int) $class['shift']);
        $week = Timetable::classWeek(ScheduleEntry::forClass((int) $class['id']));
        $clock = Timetable::clock($periods, $now);
        $today = $clock['school_day'] ? Timetable::day($periods, $week[$clock['day']] ?? []) : [];

        return [
            'periods'    => $periods,
            'week'       => $week,
            'lessons'    => array_sum(array_map('count', $week)),
            'day'        => $clock['day'],
            'schoolDay'  => $clock['school_day'],
            'firstDay'   => Timetable::firstDay($now),
            'current'    => $clock['current'],
            'today'      => $today,
            'state'      => Timetable::nowNext($today, $now),
        ];
    }

    /** A teacher's week across both shifts (read-only). */
    public static function forTeacher(int $teacherId, int $academicYearId, DateTimeImmutable $now): array
    {
        $week = Timetable::teacherWeek(ScheduleEntry::forTeacher($teacherId, $academicYearId));
        $day = (int) $now->format('N');
        $schoolDay = in_array($day, Labels::SCHOOL_DAYS, true);

        $periods = [];
        $current = [];
        $today = [];
        foreach ($week as $shift => $days) {
            $periods[$shift] = LessonPeriod::forShift($shift);
            $current[$shift] = Timetable::clock($periods[$shift], $now)['current'];
            if ($schoolDay) {
                $today = array_merge($today, Timetable::day($periods[$shift], $days[$day] ?? []));
            }
        }
        usort($today, static fn (array $a, array $b): int => $a['starts_at'] <=> $b['starts_at']);

        $lessons = 0;
        $classes = [];
        foreach ($week as $days) {
            foreach ($days as $lessonsOfDay) {
                $lessons += count($lessonsOfDay);
                foreach ($lessonsOfDay as $lesson) {
                    $classes[$lesson['subject']] = true;
                }
            }
        }

        return [
            'week'      => $week,
            'periods'   => $periods,
            'lessons'   => $lessons,
            'classes'   => count($classes),
            'day'       => $day,
            'schoolDay' => $schoolDay,
            'firstDay'  => Timetable::firstDay($now),
            'current'   => $current,
            'today'     => $today,
            'state'     => Timetable::nowNext($today, $now),
        ];
    }
}
