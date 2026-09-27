<?php
/**
 * The timetable editor for one class: a subject (and, if needed, a room) for
 * every day × period of the class's shift.
 *
 * @var array $class              SchoolClass::find()
 * @var array $periods            LessonPeriod::forShift()
 * @var list<array> $subjects     ClassSubject::forClass()
 * @var list<array> $rooms        Room::options()
 * @var array $occupied           ScheduleService::occupied(): day => period => ['teachers' => …, 'rooms' => …]
 * @var array $values             ['subject' => day => period => cs id, 'room' => day => period => room id]
 * @var array<string, int> $scheduled  cs id => lessons in the grid as shown
 * @var array $errors             'cell-{day}-{period}' => message
 * @var string|null $lastChanged
 */

use App\Support\Format;
use App\Support\Labels;

$label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
$days = Labels::SCHOOL_DAYS;
$homeRoom = $class['room_name'];
$activeRooms = array_filter($rooms, static fn (array $r): bool => (int) $r['is_active'] === 1);
$slots = count($days) * count($periods);
$planned = array_sum(array_map(static fn (array $s): int => (int) $s['hours'], $subjects));
$inGrid = array_sum($scheduled);

$optionLabel = static function (array $subject, ?string $busyIn): string {
    $teacher = $subject['teacher_first_name'] !== null
        ? mb_substr($subject['teacher_first_name'], 0, 1) . '. ' . $subject['teacher_last_name']
        : 'pa mësimdhënës';

    return ($subject['timetable_number'] !== null ? $subject['timetable_number'] . ' · ' : '')
        . $subject['subject_name'] . ' — ' . $teacher
        . ($busyIn !== null ? ' · ka orë në ' . $busyIn : '');
};
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/orari', ['ndrrimi' => $class['shift']])) ?>">Orari</a></li><li aria-current="page"><?= e($label) ?></li></ol>
        </nav>
        <h1 class="page-header__title">Orari i klasës <?= e($label) ?></h1>
        <p class="lead">
            <?= e(Labels::shift((int) $class['shift'])) ?>
            <?php if ($class['teacher_first_name'] !== null): ?> · Kujdestari: <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?><?php endif; ?>
            <?= $homeRoom !== null ? ' · ' . e($homeRoom) : '' ?>
            <?php if ($lastChanged !== null): ?> · përditësuar më <?= e(sq_date($lastChanged)) ?><?php endif; ?>
        </p>
    </div>
    <a class="btn btn--secondary" href="<?= e(url('/admin/klasat/' . $class['id'])) ?>"><?= icon('layers') ?>Lëndët e klasës</a>
</header>

<?php if ($subjects === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Klasa nuk ka ende lëndë.</h2>
        <p class="empty__text">Caktoni së pari lëndët dhe mësimdhënësit te <a href="<?= e(url('/admin/klasat/' . $class['id'])) ?>">faqja e klasës</a>.</p>
    </div>
<?php elseif ($periods === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ndërrimi i klasës nuk ka ende orë mësimi.</h2>
        <p class="empty__text">Vendosni orët te <a href="<?= e(url('/admin/orari/oret')) ?>">Orët e mësimit</a>.</p>
    </div>
<?php else: ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert--danger admin-note" role="alert">
            <?= icon('alert') ?>
            <div class="alert__body">
                <strong class="alert__title">Orari nuk u ruajt: <?= e(count($errors)) ?> <?= count($errors) === 1 ? 'orë ka' : 'orë kanë' ?> përplasje</strong>
                <ul>
                    <?php foreach ($errors as $key => $message): ?>
                        <?php [, $day, $period] = explode('-', $key); ?>
                        <li><a href="#<?= e($key) ?>"><?= e(Format::ucfirst(Labels::day((int) $day))) ?>, ora <?= e($period) ?></a>: <?= e($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/orari/klasa/' . $class['id'])) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="timetable-wrap">
            <table class="timetable timetable--edit">
                <caption class="visually-hidden">Orari javor i klasës <?= e($label) ?></caption>
                <thead>
                    <tr>
                        <th scope="col">Ora</th>
                        <?php foreach ($days as $day): ?>
                            <th scope="col"><?= e(Format::ucfirst(Labels::day($day))) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periods as $number => $period): ?>
                        <tr>
                            <th scope="row">Ora <?= e($number) ?><span><?= e($period['starts_at']) ?>–<?= e($period['ends_at']) ?></span></th>
                            <?php foreach ($days as $day): ?>
                                <?php
                                $key = 'cell-' . $day . '-' . $number;
                                $selected = $values['subject'][$day][$number] ?? '';
                                $room = $values['room'][$day][$number] ?? '';
                                $busyTeachers = $occupied[$day][$number]['teachers'] ?? [];
                                $busyRooms = $occupied[$day][$number]['rooms'] ?? [];
                                $dayName = Labels::day($day);
                                ?>
                                <td<?= isset($errors[$key]) ? ' class="is-invalid"' : '' ?>>
                                    <label class="visually-hidden" for="<?= e($key) ?>"><?= e(Format::ucfirst($dayName)) ?>, ora <?= e($number) ?></label>
                                    <select class="select timetable__select" id="<?= e($key) ?>" name="cell[<?= e($day) ?>][<?= e($number) ?>]"<?= field_invalid($errors, $key) ?>>
                                        <option value="">—</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <?php $busyIn = $subject['teacher_id'] !== null ? ($busyTeachers[(int) $subject['teacher_id']] ?? null) : null; ?>
                                            <option value="<?= e($subject['id']) ?>"<?= (string) $subject['id'] === $selected ? ' selected' : '' ?>><?= e($optionLabel($subject, $busyIn)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($activeRooms !== [] || $room !== ''): ?>
                                        <label class="visually-hidden" for="room-<?= e($day) ?>-<?= e($number) ?>">Salla, <?= e($dayName) ?>, ora <?= e($number) ?></label>
                                        <select class="select timetable__room" id="room-<?= e($day) ?>-<?= e($number) ?>" name="room[<?= e($day) ?>][<?= e($number) ?>]">
                                            <option value=""><?= $homeRoom !== null ? e($homeRoom) : 'Salla e klasës' ?></option>
                                            <?php foreach ($rooms as $option): ?>
                                                <?php
                                                if ((int) $option['id'] === (int) $class['home_room_id'] || ((int) $option['is_active'] === 0 && (string) $option['id'] !== $room)) {
                                                    continue;
                                                }
                                                $takenBy = $busyRooms[(int) $option['id']] ?? null;
                                                ?>
                                                <option value="<?= e($option['id']) ?>"<?= (string) $option['id'] === $room ? ' selected' : '' ?>><?= e($option['name'] . ($takenBy !== null ? ' · e zënë nga ' . $takenBy : '')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <?= field_error($errors, $key) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php if (($period['break_after'] ?? 0) >= 10): ?>
                            <tr class="timetable__break">
                                <td colspan="<?= e(count($days) + 1) ?>">Pushimi i madh · <?= e($period['break_after']) ?> min</td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="field__hint timetable-hint">
            Nxënësit qëndrojnë në <?= $homeRoom !== null ? 'sallën e tyre (' . e($homeRoom) . ')' : 'sallën e tyre' ?> dhe mësimdhënësit vijnë te ata.
            <?php if ($activeRooms !== []): ?>Zgjidhni sallë tjetër vetëm për orët që mbahen diku tjetër, p.sh. në palestër.<?php endif; ?>
            Mësimdhënësit që kanë orë në një klasë tjetër në të njëjtën kohë shënohen me “ka orë në…”.
        </p>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Ruaj orarin</button>
            <a class="btn btn--quiet" href="<?= e(url('/admin/orari', ['ndrrimi' => $class['shift']])) ?>">Anulo</a>
        </div>
    </form>

    <section class="portal-section" aria-labelledby="plan-title">
        <header class="section-head">
            <h2 class="section-head__title" id="plan-title">Orët sipas planit</h2>
            <span class="meta"><?= e($inGrid) ?> nga <?= e($planned) ?> orë në orar · orari ka <?= e($slots) ?> vende në javë</span>
        </header>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr>
                        <th scope="col">Lënda</th>
                        <th scope="col">Mësimdhënësi</th>
                        <th scope="col" class="num">Sipas planit</th>
                        <th scope="col" class="num">Në orar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <?php
                        $count = $scheduled[(string) $subject['id']] ?? 0;
                        $hours = $subject['hours'] !== null ? (int) $subject['hours'] : null;
                        ?>
                        <tr>
                            <td data-label="Lënda"><span class="table__primary"><?= e($subject['subject_name']) ?></span></td>
                            <td data-label="Mësimdhënësi"><?= $subject['teacher_first_name'] !== null ? e(Format::personName($subject['teacher_title'], $subject['teacher_first_name'], $subject['teacher_last_name'])) : '<span class="badge badge--warning">Pa mësimdhënës</span>' ?></td>
                            <td data-label="Sipas planit" class="num"><?= $hours !== null ? e($hours) : '—' ?></td>
                            <td data-label="Në orar" class="num">
                                <?php if ($hours === null): ?>
                                    <?= e($count) ?>
                                <?php elseif ($count === $hours): ?>
                                    <span class="badge badge--success"><?= e($count) ?></span>
                                <?php else: ?>
                                    <span class="badge badge--warning"><?= e($count) ?> (<?= $count < $hours ? 'mungojnë ' . e($hours - $count) : e($count - $hours) . ' më shumë' ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
