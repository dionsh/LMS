<?php
/**
 * The student's timetable.
 *
 * @var array|null $class        SchoolClass::forStudent()
 * @var array|null $view         TimetableView::forClass()
 * @var list<array> $changes     unread "timetable changed" notifications
 * @var string|null $lastChanged
 */

use App\Support\Format;
use App\Support\Labels;

$label = $class !== null ? Format::classLabel((int) $class['grade_level'], (int) $class['section']) : null;
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $class !== null ? 'Klasa ' . e($label) . ' · ' . e(Labels::shift((int) $class['shift'])) : 'Orari' ?></p>
        <h1 class="page-header__title">Orari</h1>
        <?php if ($class !== null): ?>
            <p class="lead">
                <?php if ($class['teacher_first_name'] !== null): ?>Kujdestari: <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?><?php endif; ?>
                <?= $class['room_name'] !== null ? ' · ' . e($class['room_name']) : '' ?>
            </p>
        <?php endif; ?>
    </div>
    <?php if ($view !== null && $view['lessons'] > 0): ?>
        <button class="btn btn--secondary no-print" type="button" data-print><?= icon('print') ?>Printo</button>
    <?php endif; ?>
</header>

<?php if ($class === null): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk jeni regjistruar në një klasë.</h2>
        <p class="empty__text">Orari shfaqet këtu sapo administrata t’ju regjistrojë në klasën tuaj.</p>
    </div>
<?php elseif ($view['lessons'] === 0): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Orari i klasës <?= e($label) ?> ende nuk është gati.</h2>
        <p class="empty__text">Ai shfaqet këtu sapo administrata ta plotësojë.</p>
    </div>
<?php else: ?>
    <?php if ($changes !== []): ?>
        <div class="callout no-print" role="status">
            <div>
                <strong>Orari i klasës suaj ndryshoi më <?= e(sq_date($changes[0]['created_at'], 'datetime')) ?>.</strong>
                <p class="meta">Shikoni orët e reja më poshtë.</p>
            </div>
            <form method="post" action="<?= e(url('/nxenesi/orari/lexuar')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--secondary btn--sm" type="submit"><?= icon('check') ?>E pashë</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="no-print schedule-now">
        <?= partial('partials/schedule-now', [
            'state'      => $view['state'],
            'schoolDay'  => $view['schoolDay'],
            'hasLessons' => $view['today'] !== [],
            'day'        => $view['day'],
        ]) ?>
    </div>

    <section class="portal-section schedule-section" aria-labelledby="week-title">
        <header class="section-head">
            <h2 class="section-head__title" id="week-title">Java</h2>
            <span class="meta">
                <?= e($view['lessons']) ?> orë në javë
                <?php if ($lastChanged !== null): ?> · përditësuar më <?= e(sq_date($lastChanged)) ?><?php endif; ?>
            </span>
        </header>
        <p class="print-only schedule-print-title">Orari i klasës <?= e($label) ?> · <?= e(Labels::shift((int) $class['shift'])) ?></p>
        <?= partial('partials/class-week', ['view' => $view, 'caption' => 'Orari javor i klasës ' . $label, 'idPrefix' => 'dita']) ?>
        <p class="meta schedule-note">
            Mësimi mbahet në <?= $class['room_name'] !== null ? 'sallën e klasës (' . e($class['room_name']) . ')' : 'sallën e klasës' ?>; mësimdhënësit vijnë te ju. Kur një orë mbahet diku tjetër, salla shënohet te ora.
        </p>
    </section>
<?php endif; ?>
