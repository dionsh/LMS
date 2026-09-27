<?php
/**
 * The student's subjects and teachers.
 *
 * @var array|null $class       SchoolClass::forStudent()
 * @var list<array> $subjects   ClassSubject::forClass()
 * @var array<int, array<int, true>> $days  class-subject id => days it is taught
 */

use App\Support\Format;
use App\Support\Labels;

$hours = array_sum(array_map(static fn (array $s): int => (int) $s['hours'], $subjects));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $class !== null ? 'Klasa ' . e(Format::classLabel((int) $class['grade_level'], (int) $class['section'])) : 'Lëndët' ?></p>
        <h1 class="page-header__title">Lëndët</h1>
        <?php if ($subjects !== []): ?>
            <p class="lead"><?= e(count($subjects)) ?> lëndë · <?= e($hours) ?> orë në javë</p>
        <?php endif; ?>
    </div>
    <?php if ($subjects !== []): ?>
        <a class="btn btn--secondary" href="<?= e(url('/nxenesi/orari')) ?>"><?= icon('calendar') ?>Orari</a>
    <?php endif; ?>
</header>

<?php if ($class === null): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk jeni regjistruar në një klasë.</h2>
        <p class="empty__text">Lëndët tuaja shfaqen këtu sapo administrata t’ju regjistrojë në klasën tuaj.</p>
    </div>
<?php elseif ($subjects === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Lëndët e klasës suaj ende nuk janë caktuar.</h2>
    </div>
<?php else: ?>
    <?php if ($class['teacher_first_name'] !== null): ?>
        <p class="lead subjects-homeroom">Kujdestari i klasës: <strong><?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?></strong></p>
    <?php endif; ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Lënda</th>
                    <th scope="col">Mësimdhënësi</th>
                    <th scope="col" class="num">Orë në javë</th>
                    <th scope="col">Ditët</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <?php $subjectDays = array_keys($days[(int) $subject['id']] ?? []); sort($subjectDays); ?>
                    <tr>
                        <td data-label="Lënda"><span class="table__primary"><?= e($subject['subject_name']) ?></span></td>
                        <td data-label="Mësimdhënësi"><?= $subject['teacher_first_name'] !== null ? e(Format::personName($subject['teacher_title'], $subject['teacher_first_name'], $subject['teacher_last_name'])) : '<span class="meta">Ende pa mësimdhënës</span>' ?></td>
                        <td data-label="Orë në javë" class="num"><?= $subject['hours'] !== null ? e($subject['hours']) : '—' ?></td>
                        <td data-label="Ditët"><?= $subjectDays !== [] ? e(implode(' · ', array_map(static fn (int $d): string => Labels::DAYS_SHORT[$d], $subjectDays))) : '<span class="meta">—</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
