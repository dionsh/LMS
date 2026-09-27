<?php
/**
 * The timetable in numbers of one shift: the school's printed timetable with
 * a teacher's number in every cell, as a form; what the numbers say (who
 * teaches what, problems, what to check); and applying it to the classes.
 *
 * @var array|null $year
 * @var int $shift
 * @var array<int, int> $counts  classes per shift
 * @var array $analysis  ScheduleSheetService::analyse()
 * @var array<int, array<int, string>> $values  class id => key(day, period) => number as typed
 * @var array<string, string> $errors  cell id => message (numbers that could not be read)
 * @var bool $incomplete  the server did not get the whole form
 * @var string|null $lastSaved
 * @var string $field  the name of the form's last field
 */

use App\Services\ScheduleSheetService as Sheet;
use App\Support\Format;
use App\Support\Labels;

$days = Labels::SCHOOL_DAYS;
$periods = $analysis['periods'];
$classes = $analysis['classes'];
$states = $analysis['states'];
$byGrade = [];
foreach ($classes as $class) {
    $byGrade[(int) $class['grade_level']][] = $class;
}
$columns = count($days) * count($periods) + 2;
$ofState = static fn (string $state): array => array_keys(array_filter($states, static fn (string $s): bool => $s === $state));
$ready = $ofState(Sheet::READY);
$applied = $ofState(Sheet::APPLIED);
$inSheet = count($states) - count($ofState(Sheet::EMPTY));
$badges = [
    Sheet::READY   => ['success', 'Gati'],
    Sheet::APPLIED => ['info', 'Në orar'],
    Sheet::WAITING => ['plain', 'Pret lëndët'],
    Sheet::BLOCKED => ['warning', 'Pret klasat e tjera'],
    Sheet::ERROR   => ['danger', 'Ka probleme'],
    Sheet::EMPTY   => ['plain', 'Bosh'],
];
$withSubjects = count(array_filter($analysis['teachers'], static fn (array $t): bool => $t['has_subjects']));
$errorCount = count($analysis['errors']);
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Orari me numra</h1>
        <p class="lead">Orari siç e shtyp shkolla: në çdo qelizë numri i mësimdhënësit. Kur dihet cilën lëndë jep secili në klasë, ai bëhet orari që shohin nxënësit dhe mësimdhënësit.</p>
    </div>
    <a class="btn btn--secondary" href="<?= e(url('/admin/orari', ['ndrrimi' => $shift])) ?>"><?= icon('calendar') ?>Orari i mësimit</a>
</header>

<div class="sheet-controls">
    <nav class="segmented" aria-label="Ndërrimi">
        <?php foreach (Labels::SHIFTS as $number => $label): ?>
            <a class="segmented__item" href="<?= e(url('/admin/orari/numrat', ['ndrrimi' => $number])) ?>"<?= $number === $shift ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="segmented__count"><?= e($counts[$number] ?? 0) ?></span></a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ($incomplete): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">
            <strong class="alert__title">Orari nuk u ruajt.</strong>
            Serveri nuk e mori formularin të plotë, prandaj nuk u ndryshua asgjë. Provoni përsëri; nëse ndodh sërish, kufiri <code>max_input_vars</code> i PHP-së duhet rritur.
        </p>
    </div>
<?php elseif ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Orari nuk u ruajt. Në qelizat e shënuara shkruani vetëm numrin e mësimdhënësit, ose lërini bosh.</p>
    </div>
<?php endif; ?>

<div class="grid grid--stats sheet-stats">
    <div class="stat">
        <span class="stat__label">Orë në orar</span>
        <span class="stat__value"><?= e(sq_number($analysis['filled'])) ?></span>
        <span class="stat__meta">nga <?= e(sq_number($analysis['slots'])) ?> gjithsej</span>
    </div>
    <div class="stat">
        <span class="stat__label">Mësimdhënës</span>
        <span class="stat__value"><?= e(count($analysis['teachers'])) ?></span>
        <span class="stat__meta"><?= e($withSubjects) ?> me lëndët e shënuara</span>
    </div>
    <div class="stat">
        <span class="stat__label">Klasa gati</span>
        <span class="stat__value"><?= e(count($ready) + count($applied)) ?></span>
        <span class="stat__meta">nga <?= e(count($classes)) ?><?= $applied !== [] ? ' · ' . e(count($applied)) . ' tashmë në orar' : '' ?></span>
    </div>
    <div class="stat">
        <span class="stat__label">Probleme</span>
        <span class="stat__value"><?= e($errorCount) ?></span>
        <span class="stat__meta"><?= e(count($analysis['warnings'])) ?> për t’u kontrolluar</span>
    </div>
</div>

<?php if ($errorCount > 0): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <div class="alert__body">
            <strong class="alert__title"><?= $errorCount === 1 ? '1 problem në orar' : e($errorCount) . ' probleme në orar' ?></strong>
            <ul>
                <?php foreach (array_slice($analysis['errors'], 0, 12) as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($errorCount > 12): ?><p>… dhe <?= e($errorCount - 12) ?> të tjera. Qelizat me probleme janë të shënuara në orar.</p><?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<section class="card sheet-apply" aria-labelledby="apply-title">
    <div>
        <h2 class="card__title" id="apply-title">Në orarin e klasave</h2>
        <?php if ($ready !== []): ?>
            <p>Gati për t’u aplikuar: <?= e(implode(', ', array_map(static fn (int $id): string => $classes[$id]['label'], $ready))) ?>.</p>
            <p class="meta">Orari i tanishëm i këtyre klasave zëvendësohet me këtë, lëndët e tyre marrin mësimdhënësit e orarit dhe nxënësit njoftohen.</p>
        <?php elseif ($applied !== [] && count($applied) === $inSheet): ?>
            <p>Çdo klasë e këtij orari e ka tashmë këtë orar.</p>
        <?php elseif ($inSheet === 0): ?>
            <p class="meta">Ky orar është ende bosh. Shkruani numrat e mësimdhënësve në tabelën më poshtë.</p>
        <?php else: ?>
            <p class="meta">Asnjë klasë nuk është ende gati. Që një numër të bëhet lëndë, duhet të dihet çfarë jep ai mësimdhënës: shënoni lëndët e secilit te <a href="<?= e(url('/admin/mesimdhenesit')) ?>">Mësimdhënësit</a>, ose caktojeni te lënda e vet në faqen e klasës.</p>
        <?php endif; ?>
    </div>
    <?php if ($ready !== []): ?>
        <form method="post" action="<?= e(url('/admin/orari/numrat/' . $shift . '/apliko')) ?>"
              data-confirm="Të aplikohet orari në <?= e(count($ready) === 1 ? '1 klasë' : count($ready) . ' klasa') ?>?"
              data-confirm-text="Orari i tanishëm i tyre zëvendësohet dhe nxënësit e tyre njoftohen."
              data-confirm-button="Apliko">
            <?= csrf_field() ?>
            <button class="btn btn--primary" type="submit"><?= icon('check') ?>Apliko në orar</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($classes === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Asnjë klasë në ndërrimin e <?= e(Labels::SHIFTS_OF[$shift]) ?>.</h2>
        <p class="empty__text">Klasat shtohen te <a href="<?= e(url('/admin/klasat')) ?>">Klasat</a>.</p>
    </div>
<?php elseif ($periods === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ky ndërrim nuk ka ende orë mësimi.</h2>
        <p class="empty__text">Vendosni kur fillon dhe mbaron secila orë te <a href="<?= e(url('/admin/orari/oret')) ?>">Orët e mësimit</a>.</p>
    </div>
<?php else: ?>
    <form class="portal-section" method="post" action="<?= e(url('/admin/orari/numrat/' . $shift)) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="sheet-wrap">
            <table class="sheet sheet--codes sheet--input" data-sheet-grid>
                <caption class="sheet__caption">
                    <span>Orari i mësimit</span>
                    <span><?= e(Labels::shift($shift)) ?></span>
                    <span><?= $year ? e($year['name']) : '' ?></span>
                    <?php if ($lastSaved !== null): ?><span class="sheet__date">ruajtur më <?= e(sq_date($lastSaved, 'short')) ?></span><?php endif; ?>
                </caption>
                <thead>
                    <tr>
                        <th scope="col" rowspan="2" class="sheet__corner">Klasa</th>
                        <?php foreach ($days as $day): ?>
                            <th scope="colgroup" colspan="<?= e(count($periods)) ?>" class="sheet__day is-day-start"><?= e(Labels::day($day)) ?></th>
                        <?php endforeach; ?>
                        <th scope="col" rowspan="2" class="sheet__homeroom-head">Kujdestari</th>
                    </tr>
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <?php foreach (array_keys($periods) as $index => $number): ?>
                                <th scope="col" class="sheet__period<?= $index === 0 ? ' is-day-start' : '' ?>"><span class="visually-hidden"><?= e(Labels::day($day)) ?>, ora </span><?= e($number) ?></th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <?php foreach ($byGrade as $grade => $gradeClasses): ?>
                    <tbody>
                        <tr class="sheet__grade">
                            <th scope="rowgroup" colspan="<?= e($columns) ?>"><span class="sheet__grade-label">Klasa <?= e(Format::grade($grade)) ?></span></th>
                        </tr>
                        <?php foreach ($gradeClasses as $class): ?>
                            <?php $classId = (int) $class['id']; ?>
                            <tr>
                                <th scope="row" class="sheet__class"><a href="<?= e(url('/admin/orari/klasa/' . $classId)) ?>"><?= e($class['label']) ?></a></th>
                                <?php foreach ($days as $day): ?>
                                    <?php foreach (array_keys($periods) as $index => $period): ?>
                                        <?php
                                        $id = Sheet::cellId($classId, $day, $period);
                                        $invalid = isset($errors[$id]);
                                        $message = $errors[$id] ?? $analysis['problems'][$classId][$day][$period] ?? null;
                                        ?>
                                        <td class="sheet__cell<?= $index === 0 ? ' is-day-start' : '' ?><?= $message !== null ? ' is-invalid' : '' ?>">
                                            <input class="sheet-input" id="<?= e($id) ?>" name="<?= e(Sheet::FIELD . '[' . $classId . '][' . Sheet::key($day, $period) . ']') ?>"
                                                   value="<?= e($values[$classId][Sheet::key($day, $period)] ?? '') ?>" inputmode="numeric" maxlength="3" autocomplete="off"
                                                   aria-label="<?= e($class['label'] . ', ' . Labels::day($day) . ', ora ' . $period) ?>"<?= $invalid ? ' aria-invalid="true"' : '' ?><?= $message !== null ? ' aria-describedby="' . e($id) . '-error" title="' . e($message) . '"' : '' ?>>
                                            <?php if ($message !== null): ?><span class="visually-hidden" id="<?= e($id) ?>-error"><?= e($message) ?></span><?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <td class="sheet__homeroom"><?= $class['teacher_first_name'] !== null ? e($class['teacher_first_name'] . ' ' . $class['teacher_last_name']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>
        <p class="field__hint timetable-hint">Shkruani numrin e mësimdhënësit si në orarin e shtypur dhe lëreni bosh orën pa mësim. Shigjetat lart e poshtë, si dhe Enter, kalojnë te klasa tjetër në të njëjtën orë.</p>
        <input type="hidden" name="<?= e($field) ?>" value="1">
        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Ruaj orarin</button>
        </div>
    </form>

    <section class="portal-section" aria-labelledby="classes-title">
        <header class="section-head">
            <h2 class="section-head__title" id="classes-title">Klasat</h2>
            <span class="meta">Gati: çdo numër në klasë ka lëndën e vet. Në orar: klasa e ka tashmë këtë orar.</span>
        </header>
        <ul class="class-chips" role="list">
            <?php foreach ($classes as $classId => $class): ?>
                <?php [$tone, $text] = $badges[$states[$classId]]; ?>
                <li>
                    <a class="class-chip" href="<?= e(url('/admin/orari/klasa/' . $classId)) ?>">
                        <span class="class-chip__label"><?= e($class['label']) ?></span>
                        <span class="badge badge--<?= e($tone) ?>"><?= e($text) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($analysis['warnings'] !== []): ?>
    <section class="card portal-section" aria-labelledby="checks-title">
        <h2 class="card__title" id="checks-title">Për t’u kontrolluar</h2>
        <ul class="sheet-notes">
            <?php foreach ($analysis['warnings'] as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($analysis['teachers'] !== []): ?>
    <section class="portal-section" aria-labelledby="teachers-title">
        <header class="section-head">
            <h2 class="section-head__title" id="teachers-title">Mësimdhënësit në orar</h2>
            <span class="meta">Kush është secili numër, sa orë ka në këtë orar dhe cilën lëndë jep.</span>
        </header>
        <div class="table-wrap">
            <table class="table sheet-teachers">
                <thead>
                    <tr>
                        <th scope="col" class="num">Nr.</th>
                        <th scope="col">Mësimdhënësi</th>
                        <th scope="col" class="num">Orë</th>
                        <th scope="col">Klasat</th>
                        <th scope="col">Lëndët</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['teachers'] as $number => $row): ?>
                        <?php $teacher = $row['teacher']; ?>
                        <tr>
                            <td class="num table__primary"><?= e($number) ?></td>
                            <td>
                                <?php if ($teacher === null): ?>
                                    <span class="badge badge--danger">Pa mësimdhënës</span>
                                <?php else: ?>
                                    <a class="table__primary" href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>"><?= e($teacher['first_name'] . ' ' . $teacher['last_name']) ?></a>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?= e($row['lessons']) ?><?= $teacher !== null ? ' / ' . e($teacher['weekly_norm']) : '' ?>
                                <?php if ($teacher !== null && $row['lessons'] > (int) $teacher['weekly_norm']): ?><span class="badge badge--warning">mbi normë</span><?php endif; ?>
                            </td>
                            <td class="sheet-teachers__classes"><?= e(implode(', ', array_map(static fn (string $label, int $lessons): string => $label . ' (' . $lessons . ')', array_keys($row['classes']), $row['classes']))) ?></td>
                            <td>
                                <?php if ($teacher === null): ?>
                                    —
                                <?php elseif (!$row['has_subjects']): ?>
                                    <span class="badge badge--plain">Pa lëndë</span>
                                    <a href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>">Shëno lëndët</a>
                                <?php else: ?>
                                    <?= e(implode(', ', array_keys($row['subjects']))) ?>
                                    <?php foreach ($row['open'] as $open): ?>
                                        <span class="table__secondary"><?= e($open) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
