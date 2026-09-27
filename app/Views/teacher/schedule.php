<?php
/**
 * The teacher's week (read-only).
 *
 * @var array|null $year
 * @var array $view  TimetableView::forTeacher()
 */

use App\Support\Labels;

$shifts = array_map(static fn (int $shift): string => Labels::shift($shift, true), array_keys($view['week']));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Orari' ?></p>
        <h1 class="page-header__title">Orari</h1>
        <?php if ($view['lessons'] > 0): ?>
            <p class="lead"><?= e($view['lessons']) ?> orë në javë · <?= e($view['classes']) ?> <?= $view['classes'] === 1 ? 'klasë' : 'klasa' ?> · <?= e(implode(' dhe ', $shifts)) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($view['lessons'] > 0): ?>
        <button class="btn btn--secondary no-print" type="button" data-print><?= icon('print') ?>Printo</button>
    <?php endif; ?>
</header>

<?php if ($view['lessons'] === 0): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Nuk keni ende orë në orar.</h2>
        <p class="empty__text">Orët tuaja shfaqen këtu sapo administrata t’jua caktojë në orarin e klasave.</p>
    </div>
<?php else: ?>
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
            <span class="meta">Orari është i administratës; për ndryshime drejtohuni asaj.</span>
        </header>
        <?= partial('partials/teacher-week', ['view' => $view, 'idPrefix' => 'dita']) ?>
    </section>
<?php endif; ?>
