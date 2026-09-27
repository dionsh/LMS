<?php
/**
 * Daily duty (kujdestaria e ditës) of one shift: a teacher for every place
 * of every post (the hall, each floor), every school day.
 *
 * @var int $shift
 * @var list<array> $posts     Duty::posts()
 * @var list<array> $teachers  User::teacherOptions()
 * @var array<int, array<int, int>> $lessons  teacher id => day => lessons in this shift
 * @var array<string, string> $values  field => teacher id
 * @var array<string, string> $errors
 */

use App\Services\DutyRoster;
use App\Support\Format;
use App\Support\Labels;

$days = Labels::SCHOOL_DAYS;
$label = static fn (array $t): string => ($t['timetable_number'] !== null ? $t['timetable_number'] . ' · ' : '') . $t['first_name'] . ' ' . $t['last_name'];
?>
<header class="page-header">
    <div>
        <p class="kicker">Shkolla</p>
        <h1 class="page-header__title">Kujdestaria e ditës</h1>
        <p class="lead">Kush kujdeset për sallën dhe për secilin kat, çdo ditë mësimi. E njëjta tabelë del edhe te orari i shkollës dhe në orarin e shtypur.</p>
    </div>
    <a class="btn btn--secondary" href="<?= e(url('/admin/orari', ['ndrrimi' => $shift])) ?>"><?= icon('calendar') ?>Orari i shkollës</a>
</header>

<div class="sheet-controls">
    <nav class="segmented" aria-label="Ndërrimi">
        <?php foreach (Labels::SHIFTS as $number => $name): ?>
            <a class="segmented__item" href="<?= e(url('/admin/kujdestaria', ['ndrrimi' => $number])) ?>"<?= $number === $shift ? ' aria-current="page"' : '' ?>><?= e($name) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Kujdestaria nuk u ruajt. Shikoni qelizat e shënuara.</p>
    </div>
<?php endif; ?>

<?php if ($posts === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka vende kujdestarie.</h2>
        <p class="empty__text">Shtoni më poshtë sallën dhe katet e shkollës.</p>
    </div>
<?php else: ?>
    <form method="post" action="<?= e(url('/admin/kujdestaria/' . $shift)) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="timetable-wrap">
            <table class="timetable timetable--edit duty-table">
                <caption class="visually-hidden">Kujdestaria e ditës · <?= e(Labels::shift($shift, true)) ?></caption>
                <thead>
                    <tr>
                        <th scope="col">Vendi</th>
                        <?php foreach ($days as $day): ?>
                            <th scope="col"><?= e(Format::ucfirst(Labels::day($day))) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <?php for ($place = 1; $place <= (int) $post['places']; $place++): ?>
                            <tr<?= $place === 1 ? ' class="duty-table__post"' : '' ?>>
                                <?php if ($place === 1): ?>
                                    <th scope="rowgroup" rowspan="<?= e($post['places']) ?>"><?= e($post['name']) ?></th>
                                <?php endif; ?>
                                <?php foreach ($days as $day): ?>
                                    <?php
                                    $field = DutyRoster::field($day, (int) $post['id'], $place);
                                    $selected = $values[$field] ?? '';
                                    $atSchool = array_filter($teachers, static fn (array $t): bool => ($lessons[(int) $t['id']][$day] ?? 0) > 0);
                                    $others = array_filter($teachers, static fn (array $t): bool => ($lessons[(int) $t['id']][$day] ?? 0) === 0);
                                    ?>
                                    <td<?= isset($errors[$field]) ? ' class="is-invalid"' : '' ?>>
                                        <label class="visually-hidden" for="<?= e($field) ?>"><?= e($post['name']) ?><?= (int) $post['places'] > 1 ? ' (' . $place . ')' : '' ?>, <?= e(Labels::day($day)) ?></label>
                                        <select class="select timetable__select" id="<?= e($field) ?>" name="<?= e($field) ?>"<?= field_invalid($errors, $field) ?>>
                                            <option value="">—</option>
                                            <?php if ($atSchool !== []): ?>
                                                <optgroup label="Kanë mësim këtë ditë">
                                                    <?php foreach ($atSchool as $teacher): ?>
                                                        <option value="<?= e($teacher['id']) ?>"<?= (string) $teacher['id'] === $selected ? ' selected' : '' ?>><?= e($label($teacher) . ' · ' . $lessons[(int) $teacher['id']][$day] . ' orë') ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                            <optgroup label="Pa mësim këtë ditë në këtë ndërrim">
                                                <?php foreach ($others as $teacher): ?>
                                                    <option value="<?= e($teacher['id']) ?>"<?= (string) $teacher['id'] === $selected ? ' selected' : '' ?>><?= e($label($teacher)) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                        <?= field_error($errors, $field) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="field__hint timetable-hint">Një mësimdhënës mund të ketë vetëm një vend kujdestarie në ditë. Të parët në listë janë ata që kanë mësim atë ditë në këtë ndërrim, me numrin e orëve.</p>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Ruaj kujdestarinë</button>
        </div>
    </form>
<?php endif; ?>

<section class="card portal-section" aria-labelledby="posts-title">
    <h2 class="card__title" id="posts-title">Vendet e kujdestarisë</h2>
    <p class="meta plan-add__text">Salla dhe katet e shkollës, dhe sa mësimdhënës kujdesen për secilin në ditë. Vlejnë për të dy ndërrimet.</p>
    <ul class="item-list" role="list">
        <?php foreach ($posts as $post): ?>
            <li>
                <form class="inline-form duty-post" method="post" action="<?= e(url('/admin/kujdestaria/vendet/' . $post['id'])) ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label class="field__label" for="post-name-<?= e($post['id']) ?>">Vendi</label>
                        <input class="input" id="post-name-<?= e($post['id']) ?>" name="name" value="<?= e($post['name']) ?>" maxlength="60">
                    </div>
                    <div class="field">
                        <label class="field__label" for="post-places-<?= e($post['id']) ?>">Mësimdhënës në ditë</label>
                        <input class="input input--short" id="post-places-<?= e($post['id']) ?>" name="places" value="<?= e($post['places']) ?>" inputmode="numeric" maxlength="1">
                    </div>
                    <button class="btn btn--secondary" type="submit">Ruaj</button>
                </form>
                <?php if ((int) $post['used'] === 0): ?>
                    <form method="post" action="<?= e(url('/admin/kujdestaria/vendet/' . $post['id'] . '/fshij')) ?>"
                          data-confirm="Të hiqet vendi “<?= e($post['name']) ?>”?" data-confirm-button="Hiq">
                        <?= csrf_field() ?>
                        <button class="btn btn--quiet account-side__danger" type="submit"><?= icon('trash') ?>Hiq</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <form class="inline-form duty-post duty-post--new" method="post" action="<?= e(url('/admin/kujdestaria/vendet/shto')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label class="field__label" for="new-post-name">Vend i ri</label>
            <input class="input" id="new-post-name" name="name" maxlength="60" placeholder="P.sh. Oborri">
        </div>
        <div class="field">
            <label class="field__label" for="new-post-places">Mësimdhënës në ditë</label>
            <input class="input input--short" id="new-post-places" name="places" value="1" inputmode="numeric" maxlength="1">
        </div>
        <button class="btn btn--secondary" type="submit"><?= icon('plus') ?>Shto vendin</button>
    </form>
</section>
