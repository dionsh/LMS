<?php
/**
 * One grade's curriculum: default shift, subjects and weekly hours.
 *
 * @var array $grade     ['level', 'shift']
 * @var array $overview  GradeLevel::overview() row (classes, students…)
 * @var list<array> $subjects  every subject
 * @var array<int, int> $slots lesson slots per week, by shift
 * @var array $values    ['shift', 'move_classes', 'selected' => [subject_id => hours string]]
 * @var array $errors
 */

use App\Support\Format;
use App\Support\Labels;

$level = (int) $grade['level'];
$label = Format::grade($level);
$classes = (int) ($overview['classes'] ?? 0);
$selected = $values['selected'];
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/plani-mesimor')) ?>">Plani mësimor</a></li><li aria-current="page">Klasa <?= e($label) ?></li></ol>
        </nav>
        <h1 class="page-header__title">Klasa <?= e($label) ?></h1>
        <p class="lead"><?= e($classes) ?> paralele · <?= e(sq_number((int) ($overview['students'] ?? 0))) ?> nxënës</p>
    </div>
</header>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Plani nuk u ruajt. Shikoni fushat e shënuara më poshtë.</p>
    </div>
<?php endif; ?>

<form class="card form account-form" method="post" action="<?= e(url('/admin/plani-mesimor/' . $level)) ?>" novalidate>
    <?= csrf_field() ?>

    <fieldset class="form-section">
        <legend class="form-section__title">Ndërrimi</legend>
        <div class="cluster">
            <?php foreach (Labels::SHIFTS as $shift => $shiftLabel): ?>
                <label class="check"><input type="radio" name="shift" value="<?= e($shift) ?>"<?= (string) $shift === $values['shift'] ? ' checked' : '' ?>> <?= e($shiftLabel) ?></label>
            <?php endforeach; ?>
        </div>
        <?= field_error($errors, 'shift') ?>
        <p class="field__hint">Paralelet e reja të klasës <?= e($label) ?> e marrin këtë ndërrim.</p>
        <?php if ($classes > 0): ?>
            <label class="check"><input type="checkbox" name="move_classes" value="1"<?= $values['move_classes'] ? ' checked' : '' ?>> Kaloji edhe <?= e($classes) ?> paralelet ekzistuese në këtë ndërrim</label>
        <?php endif; ?>
    </fieldset>

    <fieldset class="form-section">
        <legend class="form-section__title">Lëndët dhe orët në javë</legend>
        <p class="field__hint">Shënoni lëndët që mëson klasa <?= e($label) ?>. Orari i një ndërrimi ka <?= e($slots[(int) $values['shift']] ?? $slots[1]) ?> vende në javë (<?= e(($slots[(int) $values['shift']] ?? $slots[1]) / 5) ?> orë × 5 ditë).</p>
        <?= field_error($errors, 'subjects') ?>
        <div class="table-wrap">
            <table class="table plan-table">
                <thead>
                    <tr>
                        <th scope="col">Lënda</th>
                        <th scope="col" class="num">Orë në javë</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <?php
                        $id = (int) $subject['id'];
                        $in = array_key_exists($id, $selected);
                        if ((int) $subject['is_active'] === 0 && !$in) {
                            continue;   // inactive subjects appear only while still in the plan
                        }
                        ?>
                        <tr>
                            <td>
                                <label class="check plan-table__check">
                                    <input type="checkbox" name="subjects[]" value="<?= e($id) ?>"<?= $in ? ' checked' : '' ?>>
                                    <?= e($subject['name']) ?><?= (int) $subject['is_active'] === 0 ? ' <span class="badge">Joaktive</span>' : '' ?>
                                </label>
                            </td>
                            <td class="num">
                                <label class="visually-hidden" for="hours-<?= e($id) ?>">Orë në javë për <?= e($subject['name']) ?></label>
                                <input class="input input--hours" id="hours-<?= e($id) ?>" name="hours[<?= e($id) ?>]" value="<?= e($selected[$id] ?? '') ?>"
                                       inputmode="numeric" maxlength="2"<?= field_invalid($errors, 'hours-' . $id) ?>>
                                <?= field_error($errors, 'hours-' . $id) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="field__hint">Pa numër = orët ende nuk janë vendosur. Lëndët që hiqni largohen nga paralelet vetëm nëse nuk kanë ende mësimdhënës, orar apo nota.</p>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Ruaj planin</button>
        <a class="btn btn--quiet" href="<?= e(url('/admin/plani-mesimor')) ?>">Anulo</a>
    </div>
</form>

<?php if ($classes === 0): ?>
    <section class="card portal-section" aria-labelledby="remove-title">
        <h2 class="card__title" id="remove-title">Hiq klasën <?= e($label) ?></h2>
        <p class="meta account-side__text">Klasa nuk ka paralele, prandaj mund të hiqet nga plani mësimor bashkë me lëndët e saj.</p>
        <form method="post" action="<?= e(url('/admin/plani-mesimor/' . $level . '/fshij')) ?>"
              data-confirm="Të hiqet klasa <?= e($label) ?>?"
              data-confirm-text="Lëndët e saj në planin mësimor hiqen gjithashtu."
              data-confirm-button="Hiq klasën">
            <?= csrf_field() ?>
            <button class="btn btn--quiet account-side__danger" type="submit"><?= icon('trash') ?>Hiq klasën <?= e($label) ?></button>
        </form>
    </section>
<?php endif; ?>
