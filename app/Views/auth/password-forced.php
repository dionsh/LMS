<?php
/**
 * First sign-in: replace the temporary password.
 *
 * @var array                 $user
 * @var array<string, string> $errors
 */
?>
<div class="auth__form">
    <header>
        <p class="kicker">Hapi i fundit</p>
        <h1 class="auth__title">Zgjidhni fjalëkalimin tuaj</h1>
        <p class="auth__intro">
            Mirë se vini, <?= e($user['first_name']) ?>. Fjalëkalimi që morët nga shkolla është i përkohshëm.
            Zgjidhni tani një fjalëkalim që e dini vetëm ju.
        </p>
    </header>

    <form class="form" method="post" action="<?= e(url('/ndrysho-fjalekalimin')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="username" value="<?= e($user['username']) ?>" autocomplete="username">

        <div class="field">
            <label class="field__label" for="current_password">Fjalëkalimi i përkohshëm</label>
            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= field_invalid($errors, 'current_password') ?>>
            <?= field_error($errors, 'current_password') ?>
        </div>

        <div class="field">
            <label class="field__label" for="password">Fjalëkalimi i ri</label>
            <div class="input-group">
                <input class="input" id="password" name="password" type="password" autocomplete="new-password" required
                       <?= field_invalid($errors, 'password', 'password-hint') ?: ' aria-describedby="password-hint"' ?>>
                <button class="icon-btn input-group__btn" type="button" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Shfaq fjalëkalimin"><?= icon('eye') ?></button>
            </div>
            <p class="field__hint" id="password-hint">Të paktën 8 karaktere. Një fjali e shkurtër që e mbani mend lehtë është zgjedhje e mirë.</p>
            <?= field_error($errors, 'password') ?>
        </div>

        <div class="field">
            <label class="field__label" for="password_confirmation">Përsëriteni fjalëkalimin e ri</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required<?= field_invalid($errors, 'password_confirmation') ?>>
            <?= field_error($errors, 'password_confirmation') ?>
        </div>

        <button class="btn btn--primary btn--lg btn--block" type="submit">Ruaj fjalëkalimin</button>
    </form>

    <form method="post" action="<?= e(url('/dil')) ?>">
        <?= csrf_field() ?>
        <p class="auth__help">
            Nuk jeni <?= e($user['first_name'] . ' ' . $user['last_name']) ?>?
            <button class="btn btn--quiet btn--sm" type="submit">Dil</button>
        </p>
    </form>
</div>
