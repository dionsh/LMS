<?php
/**
 * One class: who teaches each subject (and how many hours), its students and details.
 *
 * @var array $class          SchoolClass::find()
 * @var list<array> $subjects ClassSubject::forClass()
 * @var list<array> $teachers User::teacherOptions()
 * @var list<array> $students SchoolClass::students()
 * @var list<array> $addable  active subjects the class does not have
 * @var array $posted         ['teacher' => [csId => id], 'hours' => [csId => n]] after a failed save
 * @var array $errors
 */

use App\Support\Format;
use App\Support\Labels;

$label = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
$teacherName = static fn (array $t): string => ($t['timetable_number'] !== null ? $t['timetable_number'] . ' · ' : '')
    . $t['first_name'] . ' ' . $t['last_name'] . ' · ' . $t['weekly_load'] . '/' . $t['weekly_norm'] . ' orë';
$hoursTotal = 0;
$missingTeacher = 0;
$outside = [];
foreach ($subjects as $subject) {
    $hoursTotal += (int) $subject['hours'];
    $missingTeacher += $subject['teacher_id'] === null ? 1 : 0;
    if ((int) $subject['in_curriculum'] === 0) {
        $outside[] = $subject;
    }
}
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/klasat')) ?>">Klasat</a></li><li aria-current="page"><?= e($label) ?></li></ol>
        </nav>
        <h1 class="page-header__title">Klasa <?= e($label) ?></h1>
        <p class="lead">
            <?= e(Labels::shift((int) $class['shift'])) ?>
            <?php if ($class['teacher_first_name'] !== null): ?>
                · Kujdestari: <?= e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) ?>
            <?php endif; ?>
            <?= $class['room_name'] !== null ? ' · ' . e($class['room_name']) : '' ?>
            · <?= e($class['students']) ?> nxënës
        </p>
    </div>
    <div class="cluster">
        <a class="btn btn--secondary" href="<?= e(url('/admin/klasat/' . $class['id'] . '/ndrysho')) ?>"><?= icon('pencil') ?>Ndrysho klasën</a>
        <a class="btn btn--primary" href="<?= e(url('/admin/orari/klasa/' . $class['id'])) ?>"><?= icon('calendar') ?>Orari i klasës</a>
    </div>
</header>

<?php if ((int) $class['year_is_current'] === 0): ?>
    <div class="alert admin-note" role="note">
        <?= icon('info') ?>
        <p class="alert__body">Kjo klasë i përket vitit shkollor <?= e($class['year_name']) ?>, jo vitit aktual.</p>
    </div>
<?php endif; ?>

<section class="card" aria-labelledby="subjects-title">
    <header class="card__head">
        <h2 class="card__title" id="subjects-title">Lëndët dhe mësimdhënësit</h2>
        <span class="cluster">
            <span class="meta num"><?= e($hoursTotal) ?> orë në javë</span>
            <?php if ($subjects !== []): ?>
                <?= $missingTeacher === 0 ? '<span class="badge badge--success">Të gjitha kanë mësimdhënës</span>' : '<span class="badge badge--warning">' . e($missingTeacher) . ' pa mësimdhënës</span>' ?>
            <?php endif; ?>
        </span>
    </header>

    <?php if ($errors !== []): ?>
        <div class="alert alert--danger admin-note" role="alert">
            <?= icon('alert') ?>
            <p class="alert__body">Ndryshimet nuk u ruajtën. Shikoni rreshtat e shënuar.</p>
        </div>
    <?php endif; ?>

    <?php if ($subjects === []): ?>
        <p class="meta">Klasa nuk ka ende lëndë. Plotësoni planin mësimor të klasës <?= e(Format::grade((int) $class['grade_level'])) ?> ose shtoni një lëndë më poshtë.</p>
    <?php else: ?>
        <form method="post" action="<?= e(url('/admin/klasat/' . $class['id'] . '/lendet')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="table-wrap">
                <table class="table table--stack assign-table">
                    <thead>
                        <tr>
                            <th scope="col">Lënda</th>
                            <th scope="col">Mësimdhënësi</th>
                            <th scope="col" class="num">Orë në javë</th>
                            <th scope="col" class="num">Në orar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <?php
                            $csId = (int) $subject['id'];
                            $selected = $posted['teacher'][$csId] ?? ($subject['teacher_id'] !== null ? (string) $subject['teacher_id'] : '');
                            $own = $posted['hours'][$csId] ?? ($subject['own_hours'] !== null ? (string) $subject['own_hours'] : '');
                            $qualified = array_filter($teachers, static fn (array $t): bool => in_array((int) $subject['subject_id'], $t['subject_ids'], true));
                            $others = array_filter($teachers, static fn (array $t): bool => !in_array((int) $subject['subject_id'], $t['subject_ids'], true));
                            $lessons = (int) $subject['lessons'];
                            $hours = $subject['hours'] !== null ? (int) $subject['hours'] : null;
                            ?>
                            <tr>
                                <td data-label="Lënda">
                                    <span class="table__primary"><?= e($subject['subject_name']) ?></span>
                                    <?php if ((int) $subject['in_curriculum'] === 0): ?><span class="table__secondary">Jashtë planit mësimor</span><?php endif; ?>
                                </td>
                                <td data-label="Mësimdhënësi">
                                    <label class="visually-hidden" for="teacher-<?= e($csId) ?>">Mësimdhënësi i lëndës <?= e($subject['subject_name']) ?></label>
                                    <select class="select assign-table__teacher" id="teacher-<?= e($csId) ?>" name="teacher[<?= e($csId) ?>]"<?= field_invalid($errors, 'teacher-' . $csId) ?>>
                                        <option value="">Pa caktuar</option>
                                        <?php if ($qualified !== []): ?>
                                            <optgroup label="Japin <?= e($subject['subject_name']) ?>">
                                                <?php foreach ($qualified as $teacher): ?>
                                                    <option value="<?= e($teacher['id']) ?>"<?= (string) $teacher['id'] === $selected ? ' selected' : '' ?>><?= e($teacherName($teacher)) ?><?= $teacher['status'] !== 'active' ? ' (joaktiv)' : '' ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <optgroup label="<?= $qualified !== [] ? 'Mësimdhënës të tjerë' : 'Të gjithë mësimdhënësit' ?>">
                                            <?php foreach ($others as $teacher): ?>
                                                <option value="<?= e($teacher['id']) ?>"<?= (string) $teacher['id'] === $selected ? ' selected' : '' ?>><?= e($teacherName($teacher)) ?><?= $teacher['status'] !== 'active' ? ' (joaktiv)' : '' ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <?= field_error($errors, 'teacher-' . $csId) ?>
                                </td>
                                <td data-label="Orë në javë" class="num">
                                    <label class="visually-hidden" for="hours-<?= e($csId) ?>">Orë në javë për <?= e($subject['subject_name']) ?></label>
                                    <input class="input input--hours" id="hours-<?= e($csId) ?>" name="hours[<?= e($csId) ?>]" value="<?= e($own) ?>"
                                           placeholder="<?= e($subject['plan_hours'] ?? '') ?>" inputmode="numeric" maxlength="2"
                                           aria-describedby="hours-hint"<?= field_invalid($errors, 'hours-' . $csId, 'hours-hint') ?>>
                                    <?= field_error($errors, 'hours-' . $csId) ?>
                                </td>
                                <td data-label="Në orar" class="num">
                                    <?php if ($hours === null): ?>
                                        <span class="meta"><?= e($lessons) ?></span>
                                    <?php elseif ($lessons === $hours): ?>
                                        <span class="badge badge--success"><?= e($lessons) ?> / <?= e($hours) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge--warning"><?= e($lessons) ?> / <?= e($hours) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="field__hint assign-hint" id="hours-hint">Orë në javë: bosh = sipas planit mësimor (numri i zbehtë). Shkruani një numër vetëm kur kjo klasë ka orë të tjera. Te mësimdhënësit, “18/20 orë” = orët që jep tani këtë vit / norma e tij.</p>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Ruaj mësimdhënësit</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="assign-extra">
        <?php if ($addable !== []): ?>
            <form class="inline-form" method="post" action="<?= e(url('/admin/klasat/' . $class['id'] . '/lendet/shto')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="field__label" for="subject_id">Shto lëndë jashtë planit</label>
                    <select class="select" id="subject_id" name="subject_id">
                        <?php foreach ($addable as $subject): ?>
                            <option value="<?= e($subject['id']) ?>"><?= e($subject['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn--secondary" type="submit"><?= icon('plus') ?>Shto</button>
            </form>
        <?php endif; ?>
        <?php if ($outside !== []): ?>
            <form class="inline-form" method="post" action="<?= e(url('/admin/klasat/' . $class['id'] . '/lendet/hiq')) ?>"
                  data-confirm="Të hiqet lënda nga klasa <?= e($label) ?>?" data-confirm-button="Hiq">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="field__label" for="class_subject_id">Hiq lëndë jashtë planit</label>
                    <select class="select" id="class_subject_id" name="class_subject_id">
                        <?php foreach ($outside as $subject): ?>
                            <option value="<?= e($subject['id']) ?>"><?= e($subject['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn--quiet account-side__danger" type="submit"><?= icon('trash') ?>Hiq</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<div class="class-columns portal-section">
    <section class="card" aria-labelledby="students-title">
        <header class="card__head">
            <h2 class="card__title" id="students-title">Nxënësit</h2>
            <span class="cluster">
                <a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/nxenesit', ['klasa' => $class['id']])) ?>">Fletët e hyrjes</a>
                <a class="btn btn--secondary btn--sm" href="<?= e(url('/admin/nxenesit/shto', ['klasa' => $class['id']])) ?>"><?= icon('plus') ?>Shto nxënës</a>
            </span>
        </header>
        <?php if ($students === []): ?>
            <p class="meta">Klasa nuk ka ende nxënës.</p>
        <?php else: ?>
            <ol class="item-list student-list" role="list">
                <?php foreach ($students as $student): ?>
                    <li>
                        <div>
                            <a class="item__title" href="<?= e(url('/admin/perdoruesit/' . $student['id'] . '/ndrysho')) ?>"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></a>
                            <span class="item__meta"><?= e($student['username']) ?></span>
                        </div>
                        <?= partial('admin/partials/account-state', ['person' => $student]) ?>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="meta class-columns__note">Për ta zhvendosur një nxënës në klasë tjetër, hapni llogarinë e tij dhe ndryshoni klasën.</p>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="details-title">
        <h2 class="card__title" id="details-title">Të dhënat</h2>
        <dl class="details class-columns__details">
            <div><dt>Viti shkollor</dt><dd><?= e($class['year_name']) ?></dd></div>
            <div><dt>Ndërrimi</dt><dd><?= e(Labels::shift((int) $class['shift'])) ?></dd></div>
            <div><dt>Drejtimi</dt><dd><?= $class['stream'] !== null ? e($class['stream']) : '—' ?></dd></div>
            <div><dt>Kujdestari</dt><dd><?= $class['teacher_first_name'] !== null ? e(Format::personName($class['teacher_title'], $class['teacher_first_name'], $class['teacher_last_name'])) : '—' ?></dd></div>
            <div><dt>Salla e klasës</dt><dd><?= $class['room_name'] !== null ? e($class['room_name']) : '—' ?></dd></div>
        </dl>
    </section>
</div>
