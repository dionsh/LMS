<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\LessonPeriod;
use App\Models\SchoolClass;
use App\Support\Labels;

/**
 * /admin/orari/oret — the bell schedule (orët e mësimit) of both shifts.
 * The timetable stores period numbers only, so new times apply to every
 * lesson of the shift at once.
 */
final class BellScheduleController extends AdminController
{
    /** GET /admin/orari/oret */
    public function edit(): Response
    {
        return $this->form(null, [], []);
    }

    /** POST /admin/orari/oret/{shift} */
    public function update(int $shift): Response
    {
        if (!isset(Labels::SHIFTS[$shift])) {
            throw new HttpException(404);
        }

        $starts = $this->request->keyed('starts');
        $ends = $this->request->keyed('ends');
        [$times, $errors] = $this->validate($shift, $starts, $ends);

        if ($errors !== []) {
            return $this->form($shift, ['starts' => $starts, 'ends' => $ends], $errors, 422);
        }

        Database::transaction(static function () use ($shift, $times): void {
            LessonPeriod::replaceShift($shift, $times);
            ActivityLog::record((int) Auth::id(), 'bells.updated', 'Ndryshoi orët e mësimit të ndërrimit ' . Labels::shift($shift, true) . '.', 'shift', $shift);
        });
        Session::flash('success', 'Orët e mësimit të ndërrimit ' . Labels::shift($shift, true) . ' u ruajtën. Orari i klasave i ndjek vetvetiu.');

        return redirect('/admin/orari/oret');
    }

    /**
     * Rows 1, 2, … as typed. Trailing empty rows remove those periods; a gap in
     * the middle, a period ending before it starts, or two overlapping periods
     * are errors. Periods still used by the timetable cannot be removed.
     *
     * @return array{0: list<array{0: string, 1: string}>, 1: array<string, string>}
     */
    private function validate(int $shift, array $starts, array $ends): array
    {
        $numbers = array_keys($starts + $ends);
        $last = $numbers === [] ? 0 : min(12, max($numbers));
        $times = [];
        $errors = [];
        $gapAt = null;
        $previousEnd = null;

        for ($number = 1; $number <= $last; $number++) {
            $start = self::time($starts[$number] ?? '');
            $end = self::time($ends[$number] ?? '');

            if ($start === '' && $end === '') {
                $gapAt ??= $number;
                continue;
            }
            if ($gapAt !== null) {
                $errors['period-' . $gapAt] = 'Ora ' . $gapAt . ' nuk mund të jetë bosh nëse ka orë pas saj. Hiqni vetëm orët e fundit.';
                break;
            }
            if ($start === null || $end === null || $start === '' || $end === '') {
                $errors['period-' . $number] = 'Shkruani kohën e fillimit dhe të mbarimit, p.sh. 08:00 dhe 08:45.';
                continue;
            }
            if ($end <= $start) {
                $errors['period-' . $number] = 'Ora duhet të mbarojë pasi fillon.';
                continue;
            }
            if ($previousEnd !== null && $start < $previousEnd) {
                $errors['period-' . $number] = 'Ora ' . $number . ' fillon para se të mbarojë ora ' . ($number - 1) . '.';
                continue;
            }

            $times[] = [$start, $end];
            $previousEnd = $end;
        }

        if ($errors === [] && $times === []) {
            $errors['period-1'] = 'Ndërrimi duhet të ketë të paktën një orë mësimi.';
        }

        $used = LessonPeriod::highestUsed($shift, $this->yearId());
        if ($errors === [] && count($times) < $used) {
            $errors['period-' . (count($times) + 1)] = 'Ora ' . $used . ' përdoret në orarin e klasave të këtij ndërrimi. Hiqni së pari ato orë nga orari.';
        }

        return [$times, $errors];
    }

    /** "8:05", "08:05" or "08:05:00" → "08:05"; '' stays ''; anything else → null. */
    private static function time(string $value): ?string
    {
        if ($value === '') {
            return '';
        }

        return preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(:00)?$/', $value, $m) === 1
            ? str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2]
            : null;
    }

    /** Both shifts' forms; $posted and $errors belong to the shift that was just submitted. */
    private function form(?int $postedShift, array $posted, array $errors, int $status = 200): Response
    {
        $shifts = [];
        foreach (Labels::SHIFTS as $shift => $label) {
            $shifts[$shift] = [
                'label'   => $label,
                'periods' => LessonPeriod::forShift($shift),
                'used'    => LessonPeriod::highestUsed($shift, $this->yearId()),
                'classes' => 0,
                'posted'  => $shift === $postedShift ? $posted : null,
                'errors'  => $shift === $postedShift ? $errors : [],
            ];
        }
        foreach (SchoolClass::overview($this->yearId()) as $class) {
            $shifts[(int) $class['shift']]['classes']++;
        }

        return $this->page('admin/bells', ['title' => 'Orët e mësimit', 'styles' => ['timetable'], 'shifts' => $shifts], 'schedule', $status);
    }
}
