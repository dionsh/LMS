<?php
/**
 * The daily duty of a shift as on the printed timetable: the places (hall,
 * floors) down the side, the five days across, a teacher in each cell.
 *
 * @var list<array> $posts  Duty::posts()
 * @var array       $grid   Duty::forShift(): day => post id => place => teacher
 * @var string      $shiftName
 */

use App\Services\DutyRoster;
use App\Support\Labels;

$days = Labels::SCHOOL_DAYS;
?>
<div class="sheet-wrap duty-sheet-wrap">
    <table class="sheet duty-sheet">
        <caption class="sheet__caption"><span>Kujdestaria e ditës</span><span><?= e($shiftName) ?></span></caption>
        <thead>
            <tr>
                <th scope="col" class="sheet__corner">Vendi</th>
                <?php foreach ($days as $day): ?>
                    <th scope="col" class="sheet__day is-day-start"><?= e(Labels::day($day)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <?php for ($place = 1; $place <= (int) $post['places']; $place++): ?>
                    <tr>
                        <?php if ($place === 1): ?>
                            <th scope="rowgroup" rowspan="<?= e($post['places']) ?>" class="sheet__row-head"><?= e($post['name']) ?></th>
                        <?php endif; ?>
                        <?php foreach ($days as $day): ?>
                            <?php $teacher = $grid[$day][(int) $post['id']][$place] ?? null; ?>
                            <td class="duty-sheet__cell is-day-start"><?= $teacher !== null ? e(DutyRoster::name($teacher)) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
