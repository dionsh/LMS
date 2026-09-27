<?php
/**
 * Add or edit a subject, including which grades study it and for how many lessons a week.
 *
 * @var array|null $subject   null when adding
 * @var array $values @var array $errors
 * @var list<array> $places   subjects an elective can take the place of: [[id, name], …]
 * @var list<int> $grades     the school's grade levels
 * @var list<array> $teachers teachers who teach it (editing only)
 * @var bool $taught          taught in some class (cannot be deleted)
 */

use App\Support\Format;

$editing = $subject !== null;
$action = $editing ? '/admin/lendet/' . $subject['id'] . '/ndrysho' : '/admin/lendet/shto';
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/lendet')) ?>">Lëndët</a></li><li aria-current="page"><?= e($editing ? $subject['name'] : 'Shto lëndë') ?></li></ol>
        </nav>
        <h1 class="page-header__title"><?= e($editing ? $subject['name'] : 'Shto lëndë') ?></h1>
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
            <legend class="form-section__title">Lënda</legend>
            <div class="form-grid form-grid--2">
                <div class="field">
                    <label class="field__label" for="name">Emri<span class="field__required" aria-hidden="true">*</span></label>
                    <input class="input" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="100" required autocomplete="off"<?= field_invalid($errors, 'name') ?>>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="field">
                    <label class="field__label" for="short_name">Shkurtimi</label>
                    <input class="input" id="short_name" name="short_name" value="<?= e($values['short_name']) ?>" maxlength="12" autocomplete="off"
                           <?= field_invalid($errors, 'short_name', 'short_name-hint') ?: ' aria-describedby="short_name-hint"' ?>>
                    <p class="field__hint" id="short_name-hint">Për orarin e përgjithshëm të shkollës, ku vendi është i ngushtë, p.sh. “Mat.”.</p>
                    <?= field_error($errors, 'short_name') ?>
                </div>
            </div>
            <div class="field">
                <label class="field__label" for="description">Përshkrimi</label>
                <textarea class="textarea" id="description" name="description" maxlength="2000"
                          <?= field_invalid($errors, 'description', 'description-hint') ?: ' aria-describedby="description-hint"' ?>><?= e($values['description']) ?></textarea>
                <p class="field__hint" id="description-hint">Shfaqet në faqen publike “Programet”. Opsional.</p>
                <?= field_error($errors, 'description') ?>
            </div>
            <div class="field">
                <label class="field__label" for="fills_subject_id">Lëndë zgjedhore në vend të</label>
                <select class="select" id="fills_subject_id" name="fills_subject_id"
                        <?= field_invalid($errors, 'fills_subject_id', 'fills_subject_id-hint') ?: ' aria-describedby="fills_subject_id-hint"' ?>>
                    <option value="">— Jo, është lëndë më vete</option>
                    <?php foreach ($places as $place): ?>
                        <option value="<?= e($place['id']) ?>"<?= (string) $place['id'] === $values['fills_subject_id'] ? ' selected' : '' ?>><?= e($place['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint" id="fills_subject_id-hint">P.sh. Orientim në karrierë në vend të Mësimit zgjedhor. Klasat që e zgjedhin (te faqja e klasës) e marrin me të njëjtat orë në javë, prandaj lënda zgjedhore nuk ka klasa më poshtë.</p>
                <?= field_error($errors, 'fills_subject_id') ?>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="form-section__title">Klasat që e mësojnë</legend>
            <p class="field__hint">E njëjta gjë si te Plani mësimor: paralelet e klasave të shënuara e marrin lëndën vetvetiu.</p>
            <div class="table-wrap">
                <table class="table plan-table">
                    <thead><tr><th scope="col">Klasa</th><th scope="col" class="num">Orë në javë</th></tr></thead>
                    <tbody>
                        <?php foreach ($grades as $level): ?>
                            <tr>
                                <td>
                                    <label class="check plan-table__check">
                                        <input type="checkbox" name="grades[]" value="<?= e($level) ?>"<?= in_array($level, $values['grades'], true) ? ' checked' : '' ?>>
                                        Klasa <?= e(Format::grade($level)) ?>
                                    </label>
                                </td>
                                <td class="num">
                                    <label class="visually-hidden" for="hours-<?= e($level) ?>">Orë në javë në klasën <?= e(Format::grade($level)) ?></label>
                                    <input class="input input--hours" id="hours-<?= e($level) ?>" name="hours[<?= e($level) ?>]" value="<?= e($values['hours'][$level] ?? '') ?>"
                                           inputmode="numeric" maxlength="2"<?= field_invalid($errors, 'hours-' . $level) ?>>
                                    <?= field_error($errors, 'hours-' . $level) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="form-section__title">Gjendja</legend>
            <label class="check"><input type="checkbox" name="is_active" value="1"<?= $values['is_active'] ? ' checked' : '' ?>> Aktive (mund t’u caktohet klasave)</label>
            <label class="check"><input type="checkbox" name="show_on_website" value="1"<?= $values['show_on_website'] ? ' checked' : '' ?>> Shfaqe në faqen publike “Programet”</label>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= $editing ? 'Ruaj ndryshimet' : icon('plus') . 'Shto lëndën' ?></button>
            <a class="btn btn--quiet" href="<?= e(url('/admin/lendet')) ?>"><?= $editing ? 'Kthehu te lista' : 'Anulo' ?></a>
        </div>
    </form>

    <?php if ($editing): ?>
        <aside class="account-side">
            <section class="card" aria-labelledby="teachers-title">
                <header class="card__head">
                    <h2 class="card__title" id="teachers-title">Mësimdhënësit</h2>
                    <span class="meta num"><?= e(count($teachers)) ?></span>
                </header>
                <?php if ($teachers === []): ?>
                    <p class="meta">Asnjë mësimdhënës nuk e ka këtë lëndë te “Lëndët që jep”.</p>
                <?php else: ?>
                    <ul class="item-list" role="list">
                        <?php foreach ($teachers as $teacher): ?>
                            <li>
                                <a class="item__title" href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>"><?= e(Format::personName($teacher['title'], $teacher['first_name'], $teacher['last_name'])) ?></a>
                                <?php if ($teacher['status'] !== 'active'): ?><span class="badge">Joaktiv</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="card" aria-labelledby="delete-title">
                <h2 class="card__title" id="delete-title">Fshij lëndën</h2>
                <?php if ($taught): ?>
                    <p class="meta account-side__text">Lënda mësohet në klasa, prandaj nuk mund të fshihet. Nëse shkolla nuk e mëson më, hiqni shenjën “Aktive”.</p>
                <?php else: ?>
                    <p class="meta account-side__text">Lënda nuk mësohet në asnjë klasë, prandaj mund të fshihet.</p>
                    <form method="post" action="<?= e(url('/admin/lendet/' . $subject['id'] . '/fshij')) ?>"
                          data-confirm="Të fshihet lënda <?= e($subject['name']) ?>?"
                          data-confirm-text="Hiqet edhe nga plani mësimor dhe nga lëndët e mësimdhënësve."
                          data-confirm-button="Fshij">
                        <?= csrf_field() ?>
                        <button class="btn btn--quiet btn--block account-side__danger" type="submit"><?= icon('trash') ?>Fshij lëndën</button>
                    </form>
                <?php endif; ?>
            </section>
        </aside>
    <?php endif; ?>
</div>
