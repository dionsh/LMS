<?php
/**
 * Add or edit a class (paralele).
 *
 * @var array|null $class        null when adding
 * @var array $values @var array $errors
 * @var array<int, int> $grades  grade level => default shift
 * @var list<array> $teachers    User::teacherOptions()
 * @var array<int, string> $homerooms teacher id => the class they are homeroom teacher of
 * @var list<array> $rooms       Room::options()
 */

use App\Support\Format;
use App\Support\Labels;

$editing = $class !== null;
$label = $editing ? Format::classLabel((int) $class['grade_level'], (int) $class['section']) : null;
$action = $editing ? '/admin/klasat/' . $class['id'] . '/ndrysho' : '/admin/klasat/shto';
$back = $editing ? '/admin/klasat/' . $class['id'] : '/admin/klasat';
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol>
                <li><a href="<?= e(url('/admin/klasat')) ?>">Klasat</a></li>
                <?php if ($editing): ?><li><a href="<?= e(url($back)) ?>"><?= e($label) ?></a></li><?php endif; ?>
                <li aria-current="page"><?= $editing ? 'Ndrysho' : 'Shto klasë' ?></li>
            </ol>
        </nav>
        <h1 class="page-header__title"><?= $editing ? 'Klasa ' . e($label) : 'Shto klasë' ?></h1>
        <p class="lead"><?= $editing ? 'Ndryshimet e numrit, ndërrimit ose sallës vlejnë menjëherë edhe për orarin.' : 'Klasa i merr vetvetiu lëndët e planit mësimor. Mësimdhënësit i caktoni pastaj te faqja e klasës.' ?></p>
    </div>
</header>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Formulari ka <?= e(count($errors)) ?> <?= count($errors) === 1 ? 'gabim' : 'gabime' ?>. Shikoni fushat e shënuara më poshtë.</p>
    </div>
<?php endif; ?>

<div class="account-layout">
    <form class="card form account-form" method="post" action="<?= e(url($action)) ?>" novalidate>
        <?= csrf_field() ?>
        <fieldset class="form-section">
            <legend class="form-section__title">Klasa</legend>
            <div class="form-grid form-grid--2">
                <div class="field">
                    <?php if ($editing): ?>
                        <span class="field__label">Klasa</span>
                        <input class="input" value="<?= e(Format::grade((int) $class['grade_level'])) ?>" readonly aria-label="Klasa" aria-describedby="grade-hint">
                        <p class="field__hint" id="grade-hint">Klasa nuk ndryshon; për klasën tjetër shtoni një paralele të re.</p>
                    <?php else: ?>
                        <label class="field__label" for="grade_level">Klasa<span class="field__required" aria-hidden="true">*</span></label>
                        <select class="select" id="grade_level" name="grade_level"<?= field_invalid($errors, 'grade_level') ?>>
                            <?php foreach ($grades as $level => $shift): ?>
                                <option value="<?= e($level) ?>"<?= (string) $level === $values['grade_level'] ? ' selected' : '' ?>><?= e(Format::grade($level)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'grade_level') ?>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="section">Numri i paraleles<span class="field__required" aria-hidden="true">*</span></label>
                    <input class="input input--short" id="section" name="section" value="<?= e($values['section']) ?>" inputmode="numeric" maxlength="2" required
                           <?= field_invalid($errors, 'section', 'section-hint') ?: ' aria-describedby="section-hint"' ?>>
                    <p class="field__hint" id="section-hint">P.sh. 1 për XII-1.</p>
                    <?= field_error($errors, 'section') ?>
                </div>
                <fieldset class="field">
                    <legend class="field__label">Ndërrimi</legend>
                    <div class="cluster">
                        <?php foreach (Labels::SHIFTS as $shift => $shiftLabel): ?>
                            <label class="check"><input type="radio" name="shift" value="<?= e($shift) ?>"<?= (string) $shift === $values['shift'] ? ' checked' : '' ?>> <?= e($shiftLabel) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error($errors, 'shift') ?>
                </fieldset>
                <div class="field">
                    <label class="field__label" for="stream">Drejtimi</label>
                    <input class="input" id="stream" name="stream" value="<?= e($values['stream']) ?>" maxlength="80" autocomplete="off"
                           <?= field_invalid($errors, 'stream', 'stream-hint') ?: ' aria-describedby="stream-hint"' ?>>
                    <p class="field__hint" id="stream-hint">P.sh. Shkenca natyrore. Opsional.</p>
                    <?= field_error($errors, 'stream') ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="form-section__title">Kujdestari dhe salla</legend>
            <div class="form-grid form-grid--2">
                <div class="field">
                    <label class="field__label" for="homeroom_teacher_id">Kujdestari i klasës</label>
                    <select class="select" id="homeroom_teacher_id" name="homeroom_teacher_id"<?= field_invalid($errors, 'homeroom_teacher_id') ?>>
                        <option value="">Pa kujdestar</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <?php $busy = $homerooms[(int) $teacher['id']] ?? null; ?>
                            <option value="<?= e($teacher['id']) ?>"<?= (string) $teacher['id'] === $values['homeroom_teacher_id'] ? ' selected' : '' ?>>
                                <?= e($teacher['last_name'] . ' ' . $teacher['first_name']) ?><?= $busy !== null ? e(' — kujdestar i ' . $busy) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'homeroom_teacher_id') ?>
                </div>
                <div class="field">
                    <label class="field__label" for="home_room_id">Salla e klasës</label>
                    <select class="select" id="home_room_id" name="home_room_id"
                            <?= field_invalid($errors, 'home_room_id', 'home_room_id-hint') ?: ' aria-describedby="home_room_id-hint"' ?>>
                        <option value="">Pa caktuar</option>
                        <?php foreach ($rooms as $room): ?>
                            <?php if ((int) $room['is_active'] === 0 && (string) $room['id'] !== $values['home_room_id']) { continue; } ?>
                            <option value="<?= e($room['id']) ?>"<?= (string) $room['id'] === $values['home_room_id'] ? ' selected' : '' ?>><?= e($room['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field__hint" id="home_room_id-hint">Nxënësit qëndrojnë në sallën e tyre; mësimdhënësit vijnë te ata.<?= $rooms === [] ? ' Sallat shtohen te “Sallat”.' : '' ?></p>
                    <?= field_error($errors, 'home_room_id') ?>
                </div>
            </div>
        </fieldset>

        <div class="form-actions">
            <?php if ($editing): ?>
                <button class="btn btn--primary" type="submit">Ruaj ndryshimet</button>
            <?php else: ?>
                <button class="btn btn--primary" type="submit"><?= icon('plus') ?>Shto klasën</button>
                <button class="btn btn--secondary" type="submit" name="next" value="1">Shto dhe vazhdo me tjetrën</button>
            <?php endif; ?>
            <a class="btn btn--quiet" href="<?= e(url($back)) ?>">Anulo</a>
        </div>
    </form>

    <?php if ($editing): ?>
        <aside class="account-side">
            <section class="card" aria-labelledby="delete-title">
                <h2 class="card__title" id="delete-title">Fshij klasën</h2>
                <?php if ((int) $class['students'] > 0): ?>
                    <p class="meta account-side__text">Klasa ka <?= e($class['students']) ?> nxënës. Zhvendosini ata në një klasë tjetër para se ta fshini.</p>
                <?php else: ?>
                    <p class="meta account-side__text">Klasa nuk ka nxënës. Fshihen edhe lëndët dhe orari i saj.</p>
                    <form method="post" action="<?= e(url('/admin/klasat/' . $class['id'] . '/fshij')) ?>"
                          data-confirm="Të fshihet klasa <?= e($label) ?>?"
                          data-confirm-text="Lëndët, mësimdhënësit e caktuar dhe orari i saj fshihen gjithashtu."
                          data-confirm-button="Fshij klasën">
                        <?= csrf_field() ?>
                        <button class="btn btn--quiet btn--block account-side__danger" type="submit"><?= icon('trash') ?>Fshij klasën</button>
                    </form>
                <?php endif; ?>
            </section>
        </aside>
    <?php endif; ?>
</div>
