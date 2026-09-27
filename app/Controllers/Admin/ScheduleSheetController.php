<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\AcademicYear;
use App\Models\LessonPeriod;
use App\Models\ScheduleSheet;
use App\Models\SchoolClass;
use App\Services\ScheduleSheetService;
use App\Support\Labels;

/**
 * /admin/orari/numrat — the timetable in numbers: the school's printed
 * timetable, a teacher's number in every cell, typed in as it is and then
 * applied to the class timetables (ScheduleSheetService).
 */
final class ScheduleSheetController extends AdminController
{
    /** The last field of the form: without it, the server did not get the whole sheet. */
    private const COMPLETE = 'plote';

    /** GET /admin/orari/numrat?ndrrimi=1 */
    public function index(): Response
    {
        $shift = (int) $this->request->query('ndrrimi', '1');

        return $this->sheet(isset(Labels::SHIFTS[$shift]) ? $shift : 1);
    }

    /** POST /admin/orari/numrat/{shift} */
    public function update(int $shift): Response
    {
        $this->shiftOrFail($shift);

        // PHP drops the fields past max_input_vars without an error; the last one tells
        if ($this->request->string(self::COMPLETE) !== '1') {
            return $this->sheet($shift, incomplete: true, status: 422);
        }

        $classes = $this->classes($shift);
        $input = $this->request->grid(ScheduleSheetService::FIELD);
        ['cells' => $cells, 'errors' => $errors] = ScheduleSheetService::read($input, $classes, LessonPeriod::forShift($shift));
        if ($errors !== []) {
            return $this->sheet($shift, $input, $errors, status: 422);
        }

        $changed = ScheduleSheetService::save($this->yearId(), $shift, $cells, (int) Auth::id());
        Session::flash($changed ? 'success' : 'info', $changed
            ? 'Orari me numra i ndërrimit të ' . Labels::SHIFTS_OF[$shift] . ' u ruajt.'
            : 'Orari me numra nuk ndryshoi.');

        return redirect('/admin/orari/numrat?ndrrimi=' . $shift);
    }

    /** POST /admin/orari/numrat/{shift}/apliko — the ready classes get the sheet as their timetable */
    public function apply(int $shift): Response
    {
        $this->shiftOrFail($shift);

        $applied = ScheduleSheetService::apply($this->yearId(), $shift, (int) Auth::id());
        Session::flash($applied !== [] ? 'success' : 'info', $applied !== []
            ? 'Orari u aplikua në ' . (count($applied) === 1 ? 'klasën ' : count($applied) . ' klasa: ') . implode(', ', $applied) . '. Nxënësit e tyre u njoftuan.'
            : 'Asnjë klasë nuk ndryshoi: asnjë klasë nuk është gati, ose klasat gati e kanë tashmë këtë orar.');

        return redirect('/admin/orari/numrat?ndrrimi=' . $shift);
    }

    private function shiftOrFail(int $shift): void
    {
        if (!isset(Labels::SHIFTS[$shift])) {
            throw new HttpException(404);
        }
    }

    /** The shift's classes of the current year, by id. */
    private function classes(int $shift): array
    {
        $classes = [];
        foreach (SchoolClass::overview($this->yearId()) as $class) {
            if ((int) $class['shift'] === $shift) {
                $classes[(int) $class['id']] = $class;
            }
        }

        return $classes;
    }

    /**
     * @param array<int, array<int, string>>|null $input  the numbers as sent (after an error), else the saved sheet
     */
    private function sheet(int $shift, ?array $input = null, array $errors = [], bool $incomplete = false, int $status = 200): Response
    {
        $analysis = ScheduleSheetService::analyse($this->yearId(), $shift);

        $values = $input;
        if ($values === null) {
            $values = [];
            foreach ($analysis['sheet'] as $classId => $days) {
                foreach ($days as $day => $periods) {
                    foreach ($periods as $period => $number) {
                        $values[$classId][ScheduleSheetService::key($day, $period)] = (string) $number;
                    }
                }
            }
        }

        $counts = [1 => 0, 2 => 0];
        foreach (SchoolClass::overview($this->yearId()) as $class) {
            $counts[(int) $class['shift']] = ($counts[(int) $class['shift']] ?? 0) + 1;
        }

        return $this->page('admin/schedule-sheet', [
            'title'      => 'Orari me numra',
            'styles'     => ['timetable'],
            'year'       => AcademicYear::current(),
            'shift'      => $shift,
            'counts'     => $counts,
            'analysis'   => $analysis,
            'values'     => $values,
            'errors'     => $errors,
            'incomplete' => $incomplete,
            'lastSaved'  => ScheduleSheet::lastSaved($this->yearId(), $shift),
            'field'      => self::COMPLETE,
        ], 'schedule', $status);
    }
}
