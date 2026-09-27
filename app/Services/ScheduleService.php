<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\ClassSubject;
use App\Models\LessonPeriod;
use App\Models\Notification;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Support\Format;
use App\Support\Labels;

/**
 * Building a class's week in the admin timetable editor.
 *
 * A proposed week is checked against every other class of the year by real
 * clock times: a teacher cannot be in two classes at once, and a room — the
 * class's own, or the one named for a lesson — cannot hold two classes at once.
 * (One class, one lesson per slot is enforced by the database itself.)
 */
final class ScheduleService
{
    /**
     * What other classes of the year occupy during this class's periods:
     * [day => [period => ['teachers' => [teacher id => 'XI-4'], 'rooms' => [room id => 'XI-4']]]]
     */
    public static function occupied(array $class): array
    {
        $occupied = [];
        foreach (ScheduleEntry::occupiedElsewhere((int) $class['academic_year_id'], (int) $class['shift'], (int) $class['id']) as $row) {
            $label = Format::classLabel((int) $row['grade_level'], (int) $row['section']);
            if ($row['teacher_id'] !== null) {
                $occupied[(int) $row['day']][(int) $row['period']]['teachers'][(int) $row['teacher_id']] = $label;
            }
            if ($row['room_id'] !== null) {
                $occupied[(int) $row['day']][(int) $row['period']]['rooms'][(int) $row['room_id']] = $label;
            }
        }

        return $occupied;
    }

    /**
     * Read and check the submitted week.
     *
     * @param array<int, array<int, string>> $subjectInput day => period => class-subject id ('' = free)
     * @param array<int, array<int, string>> $roomInput    day => period => room id ('' = the class's own room)
     * @return array{cells: array<int, array<int, array{class_subject_id: int, room_id: ?int}>>, errors: array<string, string>}
     */
    public static function read(array $class, array $subjectInput, array $roomInput): array
    {
        $subjects = array_column(ClassSubject::forClass((int) $class['id']), null, 'id');
        $rooms = array_column(Room::options(), null, 'id');
        $occupied = self::occupied($class);
        $homeRoom = $class['home_room_id'] !== null ? (int) $class['home_room_id'] : null;

        $cells = [];
        $errors = [];
        foreach (Labels::SCHOOL_DAYS as $day) {
            foreach (array_keys(LessonPeriod::forShift((int) $class['shift'])) as $period) {
                $value = $subjectInput[$day][$period] ?? '';
                $room = $roomInput[$day][$period] ?? '';
                $key = 'cell-' . $day . '-' . $period;

                if ($value === '') {
                    continue;
                }
                if (!ctype_digit($value) || !isset($subjects[(int) $value])) {
                    $errors[$key] = 'Zgjidhni një lëndë të kësaj klase.';
                    continue;
                }
                if ($room !== '' && (!ctype_digit($room) || !isset($rooms[(int) $room]))) {
                    $errors[$key] = 'Zgjidhni sallën nga lista.';
                    continue;
                }

                $subject = $subjects[(int) $value];
                $roomId = $room !== '' ? (int) $room : null;
                $cells[$day][$period] = ['class_subject_id' => (int) $value, 'room_id' => $roomId];

                $teacherId = $subject['teacher_id'] !== null ? (int) $subject['teacher_id'] : null;
                $busyIn = $teacherId !== null ? ($occupied[$day][$period]['teachers'][$teacherId] ?? null) : null;
                if ($busyIn !== null) {
                    $errors[$key] = $subject['teacher_first_name'] . ' ' . $subject['teacher_last_name'] . ' ka orë në klasën ' . $busyIn . ' në këtë kohë.';
                    continue;
                }

                $effectiveRoom = $roomId ?? $homeRoom;
                $roomTakenBy = $effectiveRoom !== null ? ($occupied[$day][$period]['rooms'][$effectiveRoom] ?? null) : null;
                if ($roomTakenBy !== null) {
                    $errors[$key] = 'Salla ' . $rooms[$effectiveRoom]['name'] . ' është e zënë nga klasa ' . $roomTakenBy . ' në këtë kohë.';
                }
            }
        }

        return ['cells' => $cells, 'errors' => $errors];
    }

    /**
     * Save the class's week. Nothing is written when it did not change; when it
     * did, the change is logged and the class's students are notified.
     *
     * @return bool whether the timetable changed
     */
    public static function save(array $class, array $cells, int $adminId): bool
    {
        return Database::transaction(static function () use ($class, $cells, $adminId): bool {
            $classId = (int) $class['id'];
            $before = [];
            foreach (ScheduleEntry::forClass($classId) as $entry) {
                $before[(int) $entry['day']][(int) $entry['period']] = [
                    'class_subject_id' => (int) $entry['class_subject_id'],
                    'room_id'          => $entry['room_id'] !== null ? (int) $entry['room_id'] : null,
                ];
            }

            if (self::normalise($before) === self::normalise($cells)) {
                return false;
            }

            ScheduleEntry::replaceForClass($classId, $cells, $adminId);

            $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
            ActivityLog::record($adminId, 'schedule.updated', 'Ndryshoi orarin e klasës ' . $label . '.', 'class', $classId);
            Notification::notifyClass($classId, 'schedule.changed', 'Orari i klasës ' . $label . ' ndryshoi', 'Shikoni orarin e ri të klasës.', '/nxenesi/orari');

            return true;
        });
    }

    /** Same week, same order — for comparing before and after. */
    private static function normalise(array $cells): array
    {
        ksort($cells);
        foreach ($cells as &$periods) {
            ksort($periods);
        }

        return $cells;
    }
}
