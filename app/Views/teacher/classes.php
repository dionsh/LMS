<?php
/**
 * What the teacher teaches this year.
 *
 * @var list<array> $teaching  ClassSubject::forTeacher()
 * @var array|null  $homeroom  the class the teacher is homeroom teacher of
 */

use App\Support\Format;
use App\Support\Labels;

$hours = array_sum(array_map(static fn (array $t): int => (int) $t['hours'], $teaching));
?>
<header class="page-header">
    <div>
        <p class="kicker">Mësimi</p>
        <h1 class="page-header__title">Klasat</h1>
        <?php if ($teaching !== []): ?>
            <p class="lead"><?= e(count($teaching)) ?> lëndë në klasa · <?= e($hours) ?> orë në javë</p>
        <?php endif; ?>
    </div>
    <?php if ($teaching !== []): ?>
        <a class="btn btn--secondary" href="<?= e(url('/mesimdhenesi/orari')) ?>"><?= icon('calendar') ?>Orari</a>
    <?php endif; ?>
</header>

<?php if ($homeroom !== null): ?>
    <div class="callout">
        <div>
            <strong>Jeni kujdestar i klasës <?= e(Format::classLabel((int) $homeroom['grade_level'], (int) $homeroom['section'])) ?>.</strong>
        </div>
    </div>
<?php endif; ?>

<?php if ($teaching === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ju është caktuar asnjë klasë.</h2>
        <p class="empty__text">Klasat dhe lëndët që jepni shfaqen këtu sapo administrata t’jua caktojë.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Klasa</th>
                    <th scope="col">Lënda</th>
                    <th scope="col">Ndërrimi</th>
                    <th scope="col" class="num">Orë në javë</th>
                    <th scope="col" class="num">Nxënës</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teaching as $item): ?>
                    <tr>
                        <td data-label="Klasa">
                            <a class="table__primary table__link" href="<?= e(url('/mesimdhenesi/klasat/' . $item['id'])) ?>"><?= e(Format::classLabel((int) $item['grade_level'], (int) $item['section'])) ?></a>
                        </td>
                        <td data-label="Lënda"><?= e($item['subject_name']) ?></td>
                        <td data-label="Ndërrimi"><?= e(Labels::shift((int) $item['shift'])) ?></td>
                        <td data-label="Orë në javë" class="num"><?= $item['hours'] !== null ? e($item['hours']) : '—' ?></td>
                        <td data-label="Nxënës" class="num"><?= e($item['students']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
