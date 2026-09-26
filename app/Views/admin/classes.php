<?php
/**
 * @var array|null $year
 * @var array<int, list<array>> $byGrade  grade level => classes
 */

use App\Support\Format;
use App\Support\Labels;

$total = array_sum(array_map('count', $byGrade));
$withHomeroom = 0;
foreach ($byGrade as $classes) {
    $withHomeroom += count(array_filter($classes, static fn (array $c): bool => $c['teacher_id'] !== null));
}
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Klasat</h1>
        <p class="lead"><?= e($total) ?> klasa · <?= e($withHomeroom) ?> me kujdestar</p>
    </div>
</header>

<?php if ($byGrade === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka klasa.</h2>
        <p class="empty__text">Klasat e vitit shkollor do të shfaqen këtu.</p>
    </div>
<?php endif; ?>

<?php foreach ($byGrade as $grade => $classes): ?>
    <section class="admin-section" aria-labelledby="grade-<?= e($grade) ?>">
        <header class="section-head">
            <h2 class="section-head__title" id="grade-<?= e($grade) ?>">Klasa <?= e(Labels::GRADE_ROMAN[$grade] ?? $grade) ?></h2>
            <span class="meta"><?= e(count($classes)) ?> paralele · <?= e(Labels::SHIFTS[(int) $classes[0]['shift']] ?? '') ?></span>
        </header>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr>
                        <th scope="col">Klasa</th>
                        <th scope="col">Kujdestari i klasës</th>
                        <th scope="col">Ndërrimi</th>
                        <th scope="col" class="num">Nxënës</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td data-label="Klasa"><span class="table__primary"><?= e(Format::classLabel((int) $class['grade_level'], (int) $class['section'])) ?></span></td>
                            <td data-label="Kujdestari i klasës">
                                <?php if ($class['teacher_id'] !== null): ?>
                                    <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?>
                                <?php else: ?>
                                    <span class="badge badge--warning">Pa kujdestar</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ndërrimi"><?= e(Labels::SHIFTS[(int) $class['shift']] ?? '') ?></td>
                            <td data-label="Nxënës" class="num"><?= e($class['students']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach; ?>
