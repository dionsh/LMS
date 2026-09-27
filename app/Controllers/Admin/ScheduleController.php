<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\LessonPeriod;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\SchoolClass;
use App\Services\ScheduleService;
use App\Support\Format;
use App\Support\Labels;

/**
 * /admin/orari — the timetable. The whole school on one sheet per shift,
 * laid out like the printed timetable on the notice board, and the editor
 * for one class's week.
 */
final class ScheduleController extends AdminController
{
    /** GET /admin/orari?ndrrimi=1&shfaq=lendet|mesimdhenesit */
    public function index(): Response
    {
        $shift = (int) $this->request->query('ndrrimi', '1');
        $shift = isset(Labels::SHIFTS[$shift]) ? $shift : 1;
        $mode = $this->request->query('shfaq') === 'mesimdhenesit' ? 'teachers' : 'subjects';
        $yearId = $this->yearId();

        $byGrade = [];
        $counts = [1 => 0, 2 => 0];
        foreach (SchoolClass::overview($yearId) as $class) {
            $counts[(int) $class['shift']]++;
            if ((int) $class['shift'] === $shift) {
                $byGrade[(int) $class['grade_level']][] = $class;
            }
        }

        $cells = [];
        $legend = [];
        foreach (ScheduleEntry::forShift($yearId, $shift) as $entry) {
            $cells[(int) $entry['class_id']][(int) $entry['day']][(int) $entry['period']] = $entry + ['code' => self::teacherCode($entry)];
            if ($entry['teacher_id'] !== null) {
                $legend[(int) $entry['teacher_id']] ??= ['code' => self::teacherCode($entry), 'entry' => $entry, 'subjects' => []];
                $legend[(int) $entry['teacher_id']]['subjects'][$entry['subject_name']] = true;
            }
        }
        uasort($legend, static fn (array $a, array $b): int => [$a['entry']['timetable_number'] === null, (int) $a['entry']['timetable_number'], $a['entry']['teacher_last_name']]
                                                              <=> [$b['entry']['timetable_number'] === null, (int) $b['entry']['timetable_number'], $b['entry']['teacher_last_name']]);

        return $this->page('admin/schedule', [
            'title'       => 'Orari i mësimit',
            'styles'      => ['timetable'],
            'year'        => AcademicYear::current(),
            'shift'       => $shift,
            'mode'        => $mode,
            'counts'      => $counts,
            'periods'     => LessonPeriod::forShift($shift),
            'byGrade'     => $byGrade,
            'cells'       => $cells,
            'legend'      => $legend,
            'clashes'     => ScheduleEntry::clashes($yearId),
            'outside'     => ScheduleEntry::outsideBellSchedule($yearId),
            'lastChanged' => ScheduleEntry::lastChanged($yearId, $shift),
        ], 'schedule');
    }

    /** GET /admin/orari/klasa/{id} — the class's week */
    public function edit(int $id): Response
    {
        $class = $this->findOrFail($id);
        $current = [];
        foreach (ScheduleEntry::forClass($id) as $entry) {
            $current['subject'][(int) $entry['day']][(int) $entry['period']] = (string) $entry['class_subject_id'];
            $current['room'][(int) $entry['day']][(int) $entry['period']] = $entry['room_id'] !== null ? (string) $entry['room_id'] : '';
        }

        return $this->editor($class, $current, []);
    }

    /** POST /admin/orari/klasa/{id} */
    public function update(int $id): Response
    {
        $class = $this->findOrFail($id);
        $subjects = $this->request->grid('cell');
        $rooms = $this->request->grid('room');
        ['cells' => $cells, 'errors' => $errors] = ScheduleService::read($class, $subjects, $rooms);
        $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);

        if ($errors !== []) {
            return $this->editor($class, ['subject' => $subjects, 'room' => $rooms], $errors, 422);
        }

        $changed = ScheduleService::save($class, $cells, (int) Auth::id());
        Session::flash($changed ? 'success' : 'info', $changed
            ? 'Orari i klasës ' . $label . ' u ruajt. Nxënësit e klasës u njoftuan.'
            : 'Orari i klasës ' . $label . ' nuk ndryshoi.');

        return redirect('/admin/orari/klasa/' . $id);
    }

    private function findOrFail(int $id): array
    {
        return SchoolClass::find($id) ?? throw new HttpException(404);
    }

    /** "25" when the teacher has a number on the printed timetable, otherwise the initials ("EB"). */
    private static function teacherCode(array $entry): string
    {
        if ($entry['teacher_id'] === null) {
            return '—';
        }

        return $entry['timetable_number'] !== null
            ? (string) $entry['timetable_number']
            : Format::initials($entry['teacher_first_name'], $entry['teacher_last_name']);
    }

    /** $values: ['subject' => [day => [period => cs id]], 'room' => …] as shown in the form. */
    private function editor(array $class, array $values, array $errors, int $status = 200): Response
    {
        $subjects = ClassSubject::forClass((int) $class['id']);
        $scheduled = [];
        foreach ($values['subject'] ?? [] as $periods) {
            foreach ($periods as $csId) {
                if ($csId !== '') {
                    $scheduled[$csId] = ($scheduled[$csId] ?? 0) + 1;
                }
            }
        }

        return $this->page('admin/schedule-class', [
            'title'       => 'Orari · ' . Format::classLabel((int) $class['grade_level'], (int) $class['section']),
            'styles'      => ['timetable'],
            'class'       => $class,
            'periods'     => LessonPeriod::forShift((int) $class['shift']),
            'subjects'    => $subjects,
            'rooms'       => Room::options(),
            'occupied'    => ScheduleService::occupied($class),
            'values'      => $values,
            'scheduled'   => $scheduled,
            'errors'      => $errors,
            'lastChanged' => ScheduleEntry::lastChanged((int) $class['academic_year_id'], null, (int) $class['id']),
        ], 'schedule', $status);
    }
}
