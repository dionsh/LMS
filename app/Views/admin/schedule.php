<?php
/**
 * The whole school's timetable for one shift, laid out like the printed
 * "Orari i mësimit" on the notice board: classes down the side, the five
 * days × lesson periods across, homeroom teachers on the right.
 *
 * @var array|null $year
 * @var int $shift @var string $mode 'subjects' | 'teachers'
 * @var array<int, int> $counts  classes per shift
 * @var array $periods           LessonPeriod::forShift()
 * @var array<int, list<array>> $byGrade  grade => classes of this shift
 * @var array $cells             class id => day => period => lesson (+ 'code')
 * @var array $legend            teacher id => ['code', 'entry', 'subjects']
 * @var list<array> $clashes @var list<array> $outside @var string|null $lastChanged
 */

use App\Support\Format;
use App\Support\Labels;

$days = Labels::SCHOOL_DAYS;
$shiftName = Labels::shift($shift, true);
$classCount = array_sum(array_map('count', $byGrade));
$columns = count($days) * count($periods) + 2;
$incomplete = [];
foreach ($byGrade as $classes) {
    foreach ($classes as $class) {
        if ((int) $class['lessons'] !== (int) $class['planned_hours'] || (int) $class['lessons'] === 0) {
            $incomplete[] = $class;
        }
    }
}
$query = static fn (array $change): array => array_merge(['ndrrimi' => $shift, 'shfaq' => $mode === 'teachers' ? 'mesimdhenesit' : 'lendet'], $change);
?>
<header class="page-header no-print">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Orari i mësimit</h1>
        <p class="lead">
            <?= e(Labels::shift($shift)) ?> · <?= e($classCount) ?> klasa
            <?php if ($lastChanged !== null): ?> · përditësuar më <?= e(sq_date($lastChanged)) ?><?php endif; ?>
        </p>
    </div>
    <div class="cluster">
        <a class="btn btn--secondary" href="<?= e(url('/admin/orari/oret')) ?>"><?= icon('clock') ?>Orët e mësimit</a>
        <button class="btn btn--primary" type="button" data-print><?= icon('print') ?>Printo</button>
    </div>
</header>

<div class="sheet-controls no-print">
    <nav class="segmented" aria-label="Ndërrimi">
        <?php foreach (Labels::SHIFTS as $number => $label): ?>
            <a class="segmented__item" href="<?= e(url('/admin/orari', $query(['ndrrimi' => $number]))) ?>"<?= $number === $shift ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="segmented__count"><?= e($counts[$number] ?? 0) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <nav class="segmented" aria-label="Çfarë shfaqet në orar">
        <a class="segmented__item" href="<?= e(url('/admin/orari', $query(['shfaq' => 'lendet']))) ?>"<?= $mode === 'subjects' ? ' aria-current="page"' : '' ?>>Lëndët</a>
        <a class="segmented__item" href="<?= e(url('/admin/orari', $query(['shfaq' => 'mesimdhenesit']))) ?>"<?= $mode === 'teachers' ? ' aria-current="page"' : '' ?>>Mësimdhënësit</a>
    </nav>
</div>

<?php if ($clashes !== []): ?>
    <div class="alert alert--danger admin-note no-print" role="alert">
        <?= icon('alert') ?>
        <div class="alert__body">
            <strong class="alert__title"><?= e(count($clashes)) ?> përplasje në orar</strong>
            <ul>
                <?php foreach (array_slice($clashes, 0, 12) as $clash): ?>
                    <?php
                    $a = Format::classLabel((int) $clash['grade_a'], (int) $clash['section_a']);
                    $b = Format::classLabel((int) $clash['grade_b'], (int) $clash['section_b']);
                    $who = $clash['kind'] === 'teacher'
                        ? $clash['teacher_first_name'] . ' ' . $clash['teacher_last_name']
                        : 'Salla ' . $clash['room_name'];
                    ?>
                    <li>
                        <?= e(Format::ucfirst(Labels::day((int) $clash['day']))) ?>: <?= e($who) ?> në
                        <a href="<?= e(url('/admin/orari/klasa/' . $clash['class_a'])) ?>"><?= e($a) ?> (ora <?= e($clash['period_a']) ?>)</a> dhe
                        <a href="<?= e(url('/admin/orari/klasa/' . $clash['class_b'])) ?>"><?= e($b) ?> (ora <?= e($clash['period_b']) ?>)</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if ($outside !== []): ?>
    <div class="alert alert--warning admin-note no-print" role="alert">
        <?= icon('warning') ?>
        <p class="alert__body">
            <strong class="alert__title"><?= e(count($outside)) ?> orë jashtë orëve të mësimit</strong>
            Këto orë janë në orar, por ora e tyre nuk ekziston më te <a href="<?= e(url('/admin/orari/oret')) ?>">Orët e mësimit</a>:
            <?= e(implode(', ', array_unique(array_map(static fn (array $o): string => Format::classLabel((int) $o['grade_level'], (int) $o['section']), $outside)))) ?>.
        </p>
    </div>
<?php endif; ?>

<?php if ($classCount === 0): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Asnjë klasë në ndërrimin e <?= e($shiftName === 'paradite' ? 'paradites' : 'pasdites') ?>.</h2>
        <p class="empty__text">Klasat shtohen te <a href="<?= e(url('/admin/klasat')) ?>">Klasat</a>.</p>
    </div>
<?php elseif ($periods === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ky ndërrim nuk ka ende orë mësimi.</h2>
        <p class="empty__text">Vendosni kur fillon dhe mbaron secila orë te <a href="<?= e(url('/admin/orari/oret')) ?>">Orët e mësimit</a>.</p>
    </div>
<?php else: ?>
    <div class="sheet-wrap">
        <table class="sheet<?= $mode === 'teachers' ? ' sheet--codes' : '' ?>">
            <caption class="sheet__caption">
                <span>Orari i mësimit</span>
                <span><?= e(Labels::shift($shift)) ?></span>
                <span><?= $year ? e($year['name']) : '' ?></span>
                <?php if ($lastChanged !== null): ?><span class="sheet__date"><?= e(sq_date($lastChanged, 'short')) ?></span><?php endif; ?>
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
            <?php foreach ($byGrade as $grade => $classes): ?>
                <tbody>
                    <tr class="sheet__grade">
                        <th scope="rowgroup" colspan="<?= e($columns) ?>"><span class="sheet__grade-label">Klasa <?= e(Format::grade($grade)) ?></span></th>
                    </tr>
                    <?php foreach ($classes as $class): ?>
                        <?php $label = Format::classLabel((int) $class['grade_level'], (int) $class['section']); ?>
                        <tr>
                            <th scope="row" class="sheet__class"><a href="<?= e(url('/admin/orari/klasa/' . $class['id'])) ?>"><?= e($label) ?></a></th>
                            <?php foreach ($days as $day): ?>
                                <?php foreach (array_keys($periods) as $index => $number): ?>
                                    <?php $lesson = $cells[(int) $class['id']][$day][$number] ?? null; ?>
                                    <?php if ($lesson === null): ?>
                                        <td class="sheet__cell is-empty<?= $index === 0 ? ' is-day-start' : '' ?>"></td>
                                    <?php else: ?>
                                        <?php
                                        $teacher = $lesson['teacher_id'] !== null ? $lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name'] : 'pa mësimdhënës';
                                        $title = $label . ', ' . Labels::day($day) . ', ora ' . $number . ': ' . $lesson['subject_name'] . ' · ' . $teacher . ($lesson['room_name'] !== null ? ' · ' . $lesson['room_name'] : '');
                                        ?>
                                        <td class="sheet__cell<?= $index === 0 ? ' is-day-start' : '' ?>" title="<?= e($title) ?>"><?= e($mode === 'teachers' ? $lesson['code'] : $lesson['short_name']) ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <td class="sheet__homeroom"><?= $class['teacher_first_name'] !== null ? e($class['teacher_first_name'] . ' ' . $class['teacher_last_name']) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($mode === 'teachers' && $legend !== []): ?>
        <section class="sheet-legend" aria-labelledby="legend-title">
            <h2 class="sheet-legend__title" id="legend-title">Mësimdhënësit në orar</h2>
            <p class="meta no-print">Qeliza tregon numrin e mësimdhënësit në orarin e shtypur; kur ai nuk është vendosur, inicialet e emrit.</p>
            <ul class="sheet-legend__list" role="list">
                <?php foreach ($legend as $item): ?>
                    <li>
                        <span class="sheet-legend__code"><?= e($item['code']) ?></span>
                        <span><?= e($item['entry']['teacher_first_name'] . ' ' . $item['entry']['teacher_last_name']) ?> <span class="meta">· <?= e(implode(', ', array_keys($item['subjects']))) ?></span></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="portal-section no-print" aria-labelledby="status-title">
        <header class="section-head">
            <h2 class="section-head__title" id="status-title">Klasat</h2>
            <span class="meta"><?= $incomplete === [] ? 'Çdo klasë e ka orarin e plotë.' : e(count($incomplete)) . ' klasa pa orar të plotë' ?></span>
        </header>
        <ul class="class-chips" role="list">
            <?php foreach ($byGrade as $classes): ?>
                <?php foreach ($classes as $class): ?>
                    <?php
                    $lessons = (int) $class['lessons'];
                    $planned = (int) $class['planned_hours'];
                    $state = $lessons === 0 ? 'plain' : ($lessons === $planned ? 'success' : 'warning');
                    ?>
                    <li>
                        <a class="class-chip" href="<?= e(url('/admin/orari/klasa/' . $class['id'])) ?>">
                            <span class="class-chip__label"><?= e(Format::classLabel((int) $class['grade_level'], (int) $class['section'])) ?></span>
                            <span class="badge badge--<?= e($state) ?>"><?= e($lessons) ?> / <?= e($planned) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
