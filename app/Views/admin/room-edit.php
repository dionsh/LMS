<?php
/**
 * @var array $room @var array $values @var array $errors
 * @var bool $used  a class's room or named in the timetable (cannot be deleted)
 */
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/sallat')) ?>">Sallat</a></li><li aria-current="page"><?= e($room['name']) ?></li></ol>
        </nav>
        <h1 class="page-header__title"><?= e($room['name']) ?></h1>
    </div>
</header>

<div class="account-layout">
    <form class="card form account-form" method="post" action="<?= e(url('/admin/sallat/' . $room['id'] . '/ndrysho')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label class="field__label" for="name">Emri<span class="field__required" aria-hidden="true">*</span></label>
                <input class="input" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="60" required autocomplete="off"<?= field_invalid($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="field">
                <label class="field__label" for="capacity">Kapaciteti</label>
                <input class="input input--short" id="capacity" name="capacity" value="<?= e($values['capacity']) ?>" inputmode="numeric" maxlength="3"<?= field_invalid($errors, 'capacity') ?>>
                <?= field_error($errors, 'capacity') ?>
            </div>
        </div>
        <label class="check"><input type="checkbox" name="is_active" value="1"<?= $values['is_active'] ? ' checked' : '' ?>> Aktive (mund të zgjidhet për klasa dhe orë)</label>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Ruaj ndryshimet</button>
            <a class="btn btn--quiet" href="<?= e(url('/admin/sallat')) ?>">Kthehu te lista</a>
        </div>
    </form>

    <aside class="account-side">
        <section class="card" aria-labelledby="delete-title">
            <h2 class="card__title" id="delete-title">Fshij sallën</h2>
            <?php if ($used): ?>
                <p class="meta account-side__text">Salla është e një klase ose përdoret në orar, prandaj nuk mund të fshihet. Mund ta bëni joaktive.</p>
            <?php else: ?>
                <p class="meta account-side__text">Salla nuk përdoret askund, prandaj mund të fshihet.</p>
                <form method="post" action="<?= e(url('/admin/sallat/' . $room['id'] . '/fshij')) ?>"
                      data-confirm="Të fshihet salla <?= e($room['name']) ?>?" data-confirm-button="Fshij">
                    <?= csrf_field() ?>
                    <button class="btn btn--quiet btn--block account-side__danger" type="submit"><?= icon('trash') ?>Fshij sallën</button>
                </form>
            <?php endif; ?>
        </section>
    </aside>
</div>
