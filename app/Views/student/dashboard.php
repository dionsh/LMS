<?php
/**
 * @var array      $user
 * @var array|null $class  the student's class this year, with its homeroom teacher
 */

use App\Support\Format;
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= e(Format::ucfirst(sq_date(new DateTimeImmutable(), 'long'))) ?></p>
        <h1 class="page-header__title"><?= e(Format::greeting()) ?>, <?= e($user['first_name']) ?>.</h1>
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
</header>

<div class="empty">
    <div class="empty__mark motif" aria-hidden="true"></div>
    <h2 class="empty__title">Paneli juaj po përgatitet</h2>
    <p class="empty__text">
        Së shpejti këtu do të shihni orën që keni tani, orarin e sotëm, detyrat në pritje,
        notat e fundit dhe njoftimet e shkollës.
    </p>
    <a class="btn btn--secondary" href="<?= e(url('/profili')) ?>"><?= icon('user') ?>Profili</a>
</div>
