<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;

/**
 * Shaping timetable rows for the views, and where the school day stands
 * right now. No SQL here: the rows come from ScheduleEntry.
 */
final class Timetable
{
    /**
     * Rows with 'day' and 'period' → [day => [period => row]].
     *
     * @param list<array> $entries
     */
    public static function grid(array $entries): array
    {
        $grid = [];
        foreach ($entries as $entry) {
            $grid[(int) $entry['day']][(int) $entry['period']] = $entry;
        }

        return $grid;
    }

    /**
     * A class's week for the timetable partials: day => period => lesson,
     * each lesson ['subject' => …, 'meta' => [teacher, room]].
     * The room is shown only when the lesson is not in the class's own room,
     * unless $alwaysRoom (the class's own room is then shown too, when known).
     */
    public static function classWeek(array $entries, bool $alwaysRoom = false): array
    {
        $week = [];
        foreach ($entries as $entry) {
            $room = $entry['room_name'] ?? null;
            if ($room === null && $alwaysRoom) {
                $room = $entry['home_room_name'] ?? null;
            }

            $week[(int) $entry['day']][(int) $entry['period']] = [
                'subject' => $entry['subject_name'],
                'meta'    => array_values(array_filter([
                    $entry['teacher_first_name'] !== null
                        ? Format::personName($entry['teacher_title'], $entry['teacher_first_name'], $entry['teacher_last_name'])
                        : 'Pa mësimdhënës',
                    $room,
                ])),
            ];
        }

        return $week;
    }

    /**
     * A teacher's week: shift => day => period => lesson, where the lesson's
     * title is the class ("XII-1") and its details the subject and room.
     */
    public static function teacherWeek(array $entries): array
    {
        $week = [];
        foreach ($entries as $entry) {
            $week[(int) $entry['shift']][(int) $entry['day']][(int) $entry['period']] = [
                'subject'  => Format::classLabel((int) $entry['grade_level'], (int) $entry['section']),
                'meta'     => array_values(array_filter([$entry['subject_name'], $entry['room_name']])),
                'class_subject_id' => (int) $entry['class_subject_id'],
            ];
        }
        ksort($week);

        return $week;
    }

    /**
     * One day's lessons in time order, with their times:
     * [['period' => 3, 'starts_at' => '09:45', 'ends_at' => '10:30', 'lesson' => […]], …]
     *
     * @param array<int, array> $periods LessonPeriod::forShift()
     * @param array<int, array> $lessons period => lesson (one day of classWeek() / teacherWeek())
     */
    public static function day(array $periods, array $lessons): array
    {
        $day = [];
        foreach ($periods as $number => $period) {
            if (isset($lessons[$number])) {
                $day[] = ['period' => $number, 'starts_at' => $period['starts_at'], 'ends_at' => $period['ends_at'], 'lesson' => $lessons[$number]];
            }
        }

        return $day;
    }

    /**
     * The lesson in progress and the next one, from a day's lessons (Timetable::day()).
     *
     * @return array{current: ?array, next: ?array, minutes_in: ?int, minutes_left: ?int, length: ?int}
     */
    public static function nowNext(array $lessons, DateTimeInterface $now): array
    {
        $time = $now->format('H:i');
        $state = ['current' => null, 'next' => null, 'minutes_in' => null, 'minutes_left' => null, 'length' => null];

        usort($lessons, static fn (array $a, array $b): int => $a['starts_at'] <=> $b['starts_at']);
        foreach ($lessons as $lesson) {
            if ($state['current'] === null && $lesson['starts_at'] <= $time && $time < $lesson['ends_at']) {
                $state['current'] = $lesson;
                $state['minutes_in'] = self::minutes($lesson['starts_at'], $time);
                $state['minutes_left'] = self::minutes($time, $lesson['ends_at']);
                $state['length'] = self::minutes($lesson['starts_at'], $lesson['ends_at']);
            } elseif ($state['next'] === null && $lesson['starts_at'] > $time) {
                $state['next'] = $lesson;
            }
        }

        return $state;
    }

    /** The day to show first: today on school days, Monday at the weekend. */
    public static function firstDay(DateTimeInterface $now): int
    {
        $day = (int) $now->format('N');

        return in_array($day, Labels::SCHOOL_DAYS, true) ? $day : Labels::SCHOOL_DAYS[0];
    }

    /**
     * Where the school day stands at $now for one shift's periods.
     *
     * @param array<int, array{starts_at: string, ends_at: string}> $periods LessonPeriod::forShift()
     * @return array{day: int, school_day: bool, current: ?int, next: ?int, minutes_left: ?int, minutes_in: ?int}
     */
    public static function clock(array $periods, DateTimeInterface $now): array
    {
        $day = (int) $now->format('N');
        $time = $now->format('H:i');
        $state = ['day' => $day, 'school_day' => in_array($day, Labels::SCHOOL_DAYS, true),
                  'current' => null, 'next' => null, 'minutes_left' => null, 'minutes_in' => null];

        if (!$state['school_day']) {
            return $state;
        }

        foreach ($periods as $number => $period) {
            if ($state['current'] === null && $period['starts_at'] <= $time && $time < $period['ends_at']) {
                $state['current'] = $number;
                $state['minutes_left'] = self::minutes($time, $period['ends_at']);
                $state['minutes_in'] = self::minutes($period['starts_at'], $time);
            } elseif ($state['next'] === null && $period['starts_at'] > $time) {
                $state['next'] = $number;
            }
        }

        return $state;
    }

    /** Length of a period in minutes. */
    public static function length(array $period): int
    {
        return self::minutes($period['starts_at'], $period['ends_at']);
    }

    private static function minutes(string $from, string $to): int
    {
        return (int) ((strtotime($to) - strtotime($from)) / 60);
    }
}
