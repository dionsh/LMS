<?php
/**
 * A class's week: day tabs on phones (today selected), the week grid from 768px.
 *
 * @var array $view     TimetableView::forClass()
 * @var string $caption e.g. "Orari javor i klasës XII-1"
 * @var string $idPrefix unique prefix for tab ids
 */

use App\Support\Format;
use App\Support\Labels;

$idPrefix = $idPrefix ?? 'day';
$today = $view['schoolDay'] ? $view['day'] : null;
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
            <h3 class="visually-hidden"><?= e(Format::ucfirst(Labels::day($day))) ?></h3>
            <?= partial('partials/timetable-day', ['periods' => $view['periods'], 'lessons' => $view['week'][$day] ?? [], 'current' => $day === $today ? $view['current'] : null]) ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="schedule-week">
    <?= partial('partials/timetable-week', ['periods' => $view['periods'], 'week' => $view['week'], 'today' => $today, 'current' => $view['current'], 'caption' => $caption]) ?>
</div>
