<?php
/**
 * A teacher's week across both shifts: day tabs on phones (only the lessons,
 * in time order), one week grid per shift from 768px.
 *
 * @var array  $view     TimetableView::forTeacher()
 * @var string $idPrefix unique prefix for tab ids
 */

use App\Support\Format;
use App\Support\Labels;

$idPrefix = $idPrefix ?? 'tday';
$today = $view['schoolDay'] ? $view['day'] : null;
$bothShifts = count($view['week']) > 1;
?>
<div class="schedule-days">
    <div class="tabs" role="tablist" aria-label="Ditët e javës">
        <?php foreach (Labels::SCHOOL_DAYS as $day): ?>
            <button class="tab" type="button" role="tab" id="<?= e($idPrefix) ?>-tab-<?= e($day) ?>" aria-controls="<?= e($idPrefix) ?>-<?= e($day) ?>" aria-selected="<?= $day === $view['firstDay'] ? 'true' : 'false' ?>" tabindex="<?= $day === $view['firstDay'] ? '0' : '-1' ?>">
                <?= e(Labels::DAYS_SHORT[$day]) ?><?php if ($day === $today): ?><span class="tab__note">sot</span><?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php foreach (Labels::SCHOOL_DAYS as $day): ?>
        <div class="tab-panel" role="tabpanel" id="<?= e($idPrefix) ?>-<?= e($day) ?>" aria-labelledby="<?= e($idPrefix) ?>-tab-<?= e($day) ?>" <?= $day === $view['firstDay'] ? '' : 'hidden' ?>>
            <?php $any = false; ?>
            <?php foreach ($view['week'] as $shift => $days): ?>
                <?php if (($days[$day] ?? []) === []) { continue; } $any = true; ?>
                <?php if ($bothShifts): ?><h3 class="schedule-shift"><?= e(Labels::shift($shift)) ?></h3><?php endif; ?>
                <?= partial('partials/timetable-day', ['periods' => $view['periods'][$shift], 'lessons' => $days[$day], 'current' => $day === $today ? $view['current'][$shift] : null, 'hideFree' => true]) ?>
            <?php endforeach; ?>
            <?php if (!$any): ?>
                <p class="meta schedule-free">Nuk keni orë mësimi <?= e(Labels::DAYS_ON[$day]) ?>.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="schedule-week">
    <?php foreach ($view['week'] as $shift => $days): ?>
        <?php if ($bothShifts): ?><h3 class="schedule-shift"><?= e(Labels::shift($shift)) ?></h3><?php endif; ?>
        <?= partial('partials/timetable-week', [
            'periods' => $view['periods'][$shift],
            'week'    => $days,
            'today'   => $today,
            'current' => $view['current'][$shift],
            'caption' => 'Orari javor · ' . Labels::shift($shift, true),
        ]) ?>
    <?php endforeach; ?>
</div>
