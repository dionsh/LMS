<?php
/**
 * A teacher's week, as the admin checks it (the same view the teacher has).
 *
 * @var array $teacher  User::details()
 * @var array $view     TimetableView::forTeacher()
 */

use App\Support\Format;
use App\Support\Labels;

$name = Format::personName($teacher['title'], $teacher['first_name'], $teacher['last_name']);
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol>
                <li><a href="<?= e(url('/admin/mesimdhenesit')) ?>">Mësimdhënësit</a></li>
                <li><a href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>"><?= e($teacher['first_name'] . ' ' . $teacher['last_name']) ?></a></li>
                <li aria-current="page">Orari</li>
            </ol>
        </nav>
        <h1 class="page-header__title">Orari i <?= e($name) ?></h1>
        <p class="lead">
            <?= e($view['lessons']) ?> orë në javë
            <?php if ($view['lessons'] > 0): ?> · <?= e($view['classes']) ?> <?= $view['classes'] === 1 ? 'klasë' : 'klasa' ?> · <?= e(implode(' dhe ', array_map(static fn (int $s): string => Labels::shift($s, true), array_keys($view['week'])))) ?><?php endif; ?>
            <?= $teacher['timetable_number'] !== null ? ' · Nr. ' . e($teacher['timetable_number']) . ' në orar' : '' ?>
        </p>
    </div>
    <?php if ($view['lessons'] > 0): ?>
        <button class="btn btn--secondary no-print" type="button" data-print><?= icon('print') ?>Printo</button>
    <?php endif; ?>
</header>

<?php if ($view['lessons'] === 0): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ky mësimdhënës nuk ka ende orë në orar.</h2>
    </div>
<?php else: ?>
    <?= partial('partials/teacher-week', ['view' => $view, 'idPrefix' => 'dita']) ?>
<?php endif; ?>
