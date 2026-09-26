<?php
/**
 * A class's week as a table (shown on screens ≥ 768px).
 *
 * @var array<int, array{number: int, starts_at: string, ends_at: string, break_after: ?int}> $periods  LessonPeriod::forShift()
 * @var array<int, array<int, ?array{subject: string, teacher: string, room: string}>>        $week     day => period => lesson
 * @var int|null $today    ISO day to highlight
 * @var int|null $current  period number in progress today
 * @var string|null $caption
 */

use App\Support\Format;
use App\Support\Labels;

$days = [1, 2, 3, 4, 5];
$today = $today ?? null;
$current = $current ?? null;
?>
<div class="timetable-wrap">
    <table class="timetable">
        <caption class="visually-hidden"><?= e($caption ?? 'Orari javor') ?></caption>
        <thead>
            <tr>
                <th scope="col">Ora</th>
                <?php foreach ($days as $day): ?>
                    <th scope="col"<?= $day === $today ? ' class="is-today"' : '' ?>>
                        <?= e(Format::ucfirst(Labels::day($day))) ?><?= $day === $today ? ' · sot' : '' ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periods as $number => $period): ?>
                <tr>
                    <th scope="row">Ora <?= e($number) ?><span><?= e($period['starts_at']) ?>–<?= e($period['ends_at']) ?></span></th>
                    <?php foreach ($days as $day): ?>
                        <?php
                        $lesson = $week[$day][$number] ?? null;
                        $classes = array_filter([
                            $day === $today ? 'is-today' : '',
                            $day === $today && $number === $current ? 'is-current' : '',
                        ]);
                        ?>
                        <td<?= $classes !== [] ? ' class="' . e(implode(' ', $classes)) . '"' : '' ?>>
                            <?php if ($lesson !== null): ?>
                                <span class="timetable__subject"><?= e($lesson['subject']) ?></span>
                                <span class="timetable__meta"><?= e($lesson['teacher']) ?></span>
                                <span class="timetable__meta"><?= e($lesson['room']) ?></span>
                            <?php else: ?>
                                <span class="timetable__free" aria-hidden="true">—</span>
                                <span class="visually-hidden">Orë e lirë</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php if (($period['break_after'] ?? 0) >= 10): ?>
                    <tr class="timetable__break">
                        <td colspan="<?= e(count($days) + 1) ?>">Pushimi i madh · <?= e($period['break_after']) ?> min</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
