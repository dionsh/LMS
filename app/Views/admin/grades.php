<?php
/**
 * The curriculum: every grade with its subjects and weekly hours.
 *
 * @var list<array> $grades       GradeLevel::overview()
 * @var array<int, list<array>> $curriculum  grade level => subjects
 * @var array<int, int> $slots    lesson slots per week, by shift
 * @var array $values @var array $errors   the "add a grade" form
 */

use App\Support\Format;
use App\Support\Labels;
?>
<header class="page-header">
    <div>
        <p class="kicker">Shkolla</p>
        <h1 class="page-header__title">Plani mësimor</h1>
        <p class="lead">Lëndët që mëson secila klasë dhe sa orë në javë. Çdo paralele e re i merr këto lëndë vetvetiu.</p>
    </div>
    <a class="btn btn--secondary" href="<?= e(url('/admin/lendet')) ?>"><?= icon('book') ?>Lëndët</a>
</header>

<?php if ($grades === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka klasa në planin mësimor.</h2>
        <p class="empty__text">Shtoni klasat që mëson shkolla, p.sh. X, XI dhe XII.</p>
    </div>
<?php endif; ?>

<div class="plan-grid">
    <?php foreach ($grades as $grade): ?>
        <?php
        $level = (int) $grade['level'];
        $subjects = $curriculum[$level] ?? [];
        $hours = (int) $grade['weekly_hours'];
        $available = $slots[(int) $grade['shift']] ?? 0;
        $missing = (int) $grade['missing_hours'];
        ?>
        <section class="card plan-card" aria-labelledby="grade-<?= e($level) ?>">
            <header class="card__head">
                <div>
                    <h2 class="card__title" id="grade-<?= e($level) ?>">Klasa <?= e(Format::grade($level)) ?></h2>
                    <p class="meta">
                        <?= e($grade['classes']) ?> paralele · <?= e(Labels::shift((int) $grade['shift'], true)) ?> · <?= e(sq_number((int) $grade['students'])) ?> nxënës
                    </p>
                </div>
                <a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/plani-mesimor/' . $level)) ?>"><?= icon('pencil') ?>Ndrysho</a>
            </header>

            <?php if ($subjects === []): ?>
                <p class="meta">Ende pa lëndë.</p>
            <?php else: ?>
                <ul class="plan-list" role="list">
                    <?php foreach ($subjects as $subject): ?>
                        <li>
                            <span><?= e($subject['name']) ?></span>
                            <span class="plan-list__hours num"><?= $subject['weekly_hours'] !== null ? e($subject['weekly_hours']) : '—' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p class="plan-card__total">
                <?php if ($missing > 0): ?>
                    <span class="badge badge--warning">Orët mungojnë për <?= e($missing) ?> lëndë</span>
                <?php elseif ($subjects !== [] && $hours === $available): ?>
                    <span class="badge badge--success"><?= e($hours) ?> orë në javë · sa vendet e orarit</span>
                <?php elseif ($subjects !== []): ?>
                    <span class="badge badge--warning"><?= e($hours) ?> orë në javë · orari ka <?= e($available) ?> vende</span>
                <?php endif; ?>
            </p>
        </section>
    <?php endforeach; ?>
</div>

<section class="card portal-section plan-add" aria-labelledby="add-grade-title">
    <h2 class="card__title" id="add-grade-title">Shto klasë në planin mësimor</h2>
    <p class="meta plan-add__text">Vetëm nëse shkolla fillon të mësojë një klasë tjetër. Paralelet e saj shtohen pastaj te <a href="<?= e(url('/admin/klasat')) ?>">Klasat</a>.</p>
    <form class="plan-add__form" method="post" action="<?= e(url('/admin/plani-mesimor/shto')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label class="field__label" for="level">Klasa (numri)</label>
            <input class="input input--short" id="level" name="level" value="<?= e($values['level']) ?>" inputmode="numeric" maxlength="2"
                   <?= field_invalid($errors, 'level', 'level-hint') ?: ' aria-describedby="level-hint"' ?>>
            <p class="field__hint" id="level-hint">P.sh. 9 për klasën IX.</p>
            <?= field_error($errors, 'level') ?>
        </div>
        <div class="field">
            <label class="field__label" for="shift">Ndërrimi</label>
            <select class="select" id="shift" name="shift"<?= field_invalid($errors, 'shift') ?>>
                <?php foreach (Labels::SHIFTS as $shift => $label): ?>
                    <option value="<?= e($shift) ?>"<?= (string) $shift === $values['shift'] ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'shift') ?>
        </div>
        <div class="plan-add__actions">
            <button class="btn btn--secondary" type="submit"><?= icon('plus') ?>Shto klasën</button>
        </div>
    </form>
</section>
