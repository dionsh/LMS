<?php
/**
 * One class-subject the teacher teaches: its lessons in the week and its students.
 *
 * @var array $classSubject  ClassSubject::find()
 * @var array $class         SchoolClass::find()
 * @var list<array> $lessons its timetable entries, with times
 * @var list<array> $students
 */

use App\Support\Format;
use App\Support\Labels;

$label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/mesimdhenesi/klasat')) ?>">Klasat</a></li><li aria-current="page"><?= e($label) ?> · <?= e($classSubject['subject_name']) ?></li></ol>
        </nav>
        <h1 class="page-header__title"><?= e($label) ?> · <?= e($classSubject['subject_name']) ?></h1>
        <p class="lead">
            <?= e(Labels::shift((int) $class['shift'])) ?>
            <?= $classSubject['hours'] !== null ? ' · ' . e($classSubject['hours']) . ' orë në javë' : '' ?>
            <?php if ($class['teacher_first_name'] !== null): ?> · Kujdestari: <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?><?php endif; ?>
            <?= $class['room_name'] !== null ? ' · ' . e($class['room_name']) : '' ?>
        </p>
    </div>
</header>

<div class="class-columns">
    <section class="card" aria-labelledby="students-title">
        <header class="card__head">
            <h2 class="card__title" id="students-title">Nxënësit</h2>
            <span class="meta num"><?= e(count($students)) ?></span>
        </header>
        <?php if ($students === []): ?>
            <p class="meta">Klasa nuk ka ende nxënës.</p>
        <?php else: ?>
            <ol class="roster">
                <?php foreach ($students as $student): ?>
                    <li><?= e($student['first_name'] . ' ' . $student['last_name']) ?></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="lessons-title">
        <h2 class="card__title" id="lessons-title">Orët në javë</h2>
        <?php if ($lessons === []): ?>
            <p class="meta class-columns__details">Kjo lëndë nuk është ende në orarin e klasës.</p>
        <?php else: ?>
            <ul class="item-list class-columns__details" role="list">
                <?php foreach ($lessons as $lesson): ?>
                    <li>
                        <div>
                            <span class="item__title"><?= e(Format::ucfirst(Labels::day((int) $lesson['day']))) ?> · Ora <?= e($lesson['period']) ?></span>
                            <span class="item__meta">
                                <?= $lesson['starts_at'] !== null ? e($lesson['starts_at']) . '–' . e($lesson['ends_at']) : '' ?>
                                <?= $lesson['room_name'] !== null ? ' · ' . e($lesson['room_name']) : '' ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
