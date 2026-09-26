<?php
/**
 * @var string      $login        what was typed (refilled after an error; never the password)
 * @var string|null $error
 * @var bool        $expired      the previous session timed out
 * @var bool        $hasIntended  the visitor was sent here from a portal page
 */
?>
<div class="auth__form">
    <header>
        <p class="kicker">Portali mësimor</p>
        <h1 class="auth__title">Mirë se vini</h1>
        <p class="auth__intro">Hyni me emrin e përdoruesit ose me email-in që ju ka dhënë shkolla.</p>
    </header>

    <?= partial('partials/flash') ?>

    <?php if ($error !== null): ?>
        <div class="alert alert--danger" role="alert" id="login-error">
            <?= icon('alert') ?>
            <p class="alert__body"><?= e($error) ?></p>
        </div>
    <?php elseif ($expired): ?>
        <div class="alert alert--warning" role="status">
            <?= icon('clock') ?>
            <p class="alert__body">Sesioni juaj skadoi pas një kohe pa aktivitet. Ju lutemi hyni përsëri.</p>
        </div>
    <?php elseif ($hasIntended): ?>
        <div class="alert" role="status">
            <?= icon('lock') ?>
            <p class="alert__body">Hyni për të vazhduar te faqja që kërkuat.</p>
        </div>
    <?php endif; ?>

    <form class="form" method="post" action="<?= e(url('/hyr')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label class="field__label" for="login">Emri i përdoruesit ose email-i</label>
            <input class="input" id="login" name="login" value="<?= e($login) ?>"
                   autocomplete="username" autocapitalize="none" spellcheck="false" required
                   <?= $error !== null ? 'aria-invalid="true" aria-describedby="login-error"' : 'autofocus' ?>>
        </div>
        <div class="field">
            <label class="field__label" for="password">Fjalëkalimi</label>
            <div class="input-group">
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" required
                       <?= $error !== null ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                <button class="icon-btn input-group__btn" type="button" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Shfaq fjalëkalimin"><?= icon('eye') ?></button>
            </div>
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Hyr</button>
    </form>

    <div>
        <p class="auth__help">
            Llogaritë krijohen nga administrata e shkollës. Nëse keni harruar fjalëkalimin,
            drejtohuni kujdestarit të klasës ose administratës.
        </p>
        <a class="auth__back" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon--sm') ?>Kthehu te faqja e shkollës</a>
    </div>
</div>
