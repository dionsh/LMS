<?php
/**
 * @var array      $user
 * @var array|null $class  the student's class this year, with its homeroom teacher
 * @var array|null $view   TimetableView::forClass()
 * @var DateTimeImmutable $now
 */

use App\Support\Format;
use App\Support\Labels;
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= e(Format::ucfirst(sq_date($now, 'long'))) ?></p>
        <h1 class="page-header__title"><?= e(Format::greeting($now)) ?>, <?= e($user['first_name']) ?>.</h1>
        <?php if ($class !== null): ?>
            <p class="lead">
                Klasa <?= e(Format::classLabel((int) $class['grade_level'], (int) $class['section'])) ?>
                <?php if ($class['teacher_first_name'] !== null): ?>
                    · Kujdestari: <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="lead">Ende nuk jeni regjistruar në një klasë. Për ndihmë drejtohuni administratës së shkollës.</p>
        <?php endif; ?>
    </div>
    <?php if ($view !== null && $view['lessons'] > 0): ?>
        <a class="btn btn--secondary" href="<?= e(url('/nxenesi/orari')) ?>"><?= icon('calendar') ?>Orari javor</a>
    <?php endif; ?>
</header>

<?php if ($view !== null && $view['lessons'] > 0): ?>
    <div class="dashboard">
        <div class="span-7">
            <?= partial('partials/schedule-now', [
                'state'      => $view['state'],
                'schoolDay'  => $view['schoolDay'],
                'hasLessons' => $view['today'] !== [],
                'day'        => $view['day'],
            ]) ?>
        </div>
        <section class="card span-5" aria-labelledby="today-title">
            <header class="card__head">
                <h2 class="card__title" id="today-title"><?= $view['schoolDay'] ? 'Sot' : e(Format::ucfirst(Labels::day($view['firstDay']))) ?></h2>
                <a class="link-arrow" href="<?= e(url('/nxenesi/orari')) ?>">Java <?= icon('arrow-right') ?></a>
            </header>
            <?= partial('partials/timetable-day', [
                'periods' => $view['periods'],
                'lessons' => $view['week'][$view['firstDay']] ?? [],
                'current' => $view['schoolDay'] ? $view['current'] : null,
                'compact' => true,
            ]) ?>
        </section>
    </div>
<?php endif; ?>

<div class="empty portal-section">
    <div class="empty__mark motif" aria-hidden="true"></div>
    <h2 class="empty__title">Së shpejti edhe më shumë këtu</h2>
    <p class="empty__text">Detyrat në pritje, notat e fundit dhe njoftimet e shkollës do të shfaqen në këtë panel.</p>
</div>
