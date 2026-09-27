<?php
/**
 * @var array|null $year
 * @var array<int, list<array>> $byGrade  grade level => classes (SchoolClass::overview())
 * @var array<int, int> $shifts           grade level => default shift
 */

use App\Support\Format;
use App\Support\Labels;

$all = array_merge(...array_values($byGrade ?: [[]]));
$total = count($all);
$withHomeroom = count(array_filter($all, static fn (array $c): bool => $c['teacher_id'] !== null));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Klasat</h1>
        <p class="lead"><?= e($total) ?> klasa · <?= e($withHomeroom) ?> me kujdestar</p>
    </div>
    <div class="cluster">
        <a class="btn btn--secondary" href="<?= e(url('/admin/plani-mesimor')) ?>"><?= icon('clipboard') ?>Plani mësimor</a>
        <?php if ($year !== null && $shifts !== []): ?>
            <a class="btn btn--primary" href="<?= e(url('/admin/klasat/shto')) ?>"><?= icon('plus') ?>Shto klasë</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($total === 0): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka klasa.</h2>
        <p class="empty__text">Shtoni paralelet e vitit shkollor. Secila i merr vetvetiu lëndët e planit mësimor të klasës së saj.</p>
    </div>
<?php endif; ?>

<?php foreach ($byGrade as $grade => $classes): ?>
    <?php if ($classes === []) { continue; } ?>
    <section class="admin-section" aria-labelledby="grade-<?= e($grade) ?>">
        <header class="section-head">
            <h2 class="section-head__title" id="grade-<?= e($grade) ?>">Klasa <?= e(Format::grade($grade)) ?></h2>
            <span class="cluster">
                <span class="meta"><?= e(count($classes)) ?> paralele · <?= e(Labels::shift($shifts[$grade] ?? 1, true)) ?></span>
                <a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/klasat/shto', ['niveli' => $grade])) ?>"><?= icon('plus') ?>Shto paralele</a>
            </span>
        </header>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr>
                        <th scope="col">Klasa</th>
                        <th scope="col">Kujdestari i klasës</th>
                        <th scope="col">Lëndët</th>
                        <th scope="col">Orari</th>
                        <th scope="col" class="num">Nxënës</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <?php
                        $subjects = (int) $class['subjects'];
                        $assigned = (int) $class['subjects_with_teacher'];
                        $planned = (int) $class['planned_hours'];
                        $lessons = (int) $class['lessons'];
                        ?>
                        <tr>
                            <td data-label="Klasa">
                                <a class="table__primary table__link" href="<?= e(url('/admin/klasat/' . $class['id'])) ?>"><?= e(Format::classLabel((int) $class['grade_level'], (int) $class['section'])) ?></a>
                                <span class="table__secondary"><?= e(Labels::shift((int) $class['shift'])) ?><?= $class['room_name'] !== null ? ' · ' . e($class['room_name']) : '' ?></span>
                            </td>
                            <td data-label="Kujdestari i klasës">
                                <?php if ($class['teacher_id'] !== null): ?>
                                    <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?>
                                <?php else: ?>
                                    <span class="badge badge--warning">Pa kujdestar</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Lëndët">
                                <?php if ($subjects === 0): ?>
                                    <span class="badge badge--warning">Pa lëndë</span>
                                <?php elseif ($assigned === $subjects): ?>
                                    <span class="badge badge--success"><?= e($subjects) ?> me mësimdhënës</span>
                                <?php else: ?>
                                    <span class="badge badge--warning"><?= e($subjects - $assigned) ?> pa mësimdhënës</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Orari">
                                <a class="table__link" href="<?= e(url('/admin/orari/klasa/' . $class['id'])) ?>">
                                    <?php if ($lessons === 0): ?>
                                        <span class="badge badge--plain">Pa orar</span>
                                    <?php elseif ($planned > 0 && $lessons === $planned): ?>
                                        <span class="badge badge--success"><?= e($lessons) ?> orë në javë</span>
                                    <?php else: ?>
                                        <span class="badge badge--warning"><?= e($lessons) ?><?= $planned > 0 ? ' nga ' . e($planned) : '' ?> orë</span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td data-label="Nxënës" class="num"><?= e($class['students']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach; ?>
