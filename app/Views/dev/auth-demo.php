<?php
/**
 * The sign-in screen's look (development only). The working form is built in T04.
 *
 * @var bool $showError
 */
?>
<div class="auth__form">
    <header>
        <p class="kicker">Portali mësimor</p>
        <h1 class="auth__title">Mirë se vini</h1>
        <p class="auth__intro">Hyni me emrin e përdoruesit ose me email-in që ju ka dhënë shkolla.</p>
    </header>

    <?php if ($showError): ?>
        <div class="alert alert--danger" role="alert">
            <?= icon('alert') ?>
            <p class="alert__body">Emri i përdoruesit ose fjalëkalimi është i pasaktë.</p>
        </div>
    <?php endif; ?>

    <!-- A <div> in this demo: the real <form method="post"> arrives with T04 -->
    <div class="form">
        <div class="field">
            <label class="field__label" for="login-id">Emri i përdoruesit ose email-i</label>
            <input class="input" id="login-id" name="login" autocomplete="username" autocapitalize="none" spellcheck="false"<?= $showError ? ' value="arta.gashi" aria-invalid="true"' : '' ?>>
        </div>
        <div class="field">
            <label class="field__label" for="login-password">Fjalëkalimi</label>
            <div class="input-group">
                <input class="input" id="login-password" name="password" type="password" autocomplete="current-password"<?= $showError ? ' aria-invalid="true"' : '' ?>>
                <button class="icon-btn input-group__btn" type="button" data-password-toggle aria-controls="login-password" aria-pressed="false" aria-label="Shfaq fjalëkalimin"><?= icon('eye') ?></button>
            </div>
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="button">Hyr</button>
    </div>

    <div>
        <p class="auth__help">
            Llogaritë krijohen nga administrata e shkollës. Nëse keni harruar fjalëkalimin,
            drejtohuni kujdestarit të klasës ose administratës.
        </p>
        <a class="auth__back" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon--sm') ?>Kthehu te faqja e shkollës</a>
        <p class="meta auth__note">
            Shembull:
            <?php if ($showError): ?>
                <a href="<?= e(route('dev.auth')) ?>">gjendja normale</a>
            <?php else: ?>
                <a href="<?= e(route('dev.auth', [], ['gabim' => '1'])) ?>">shfaq gjendjen me gabim</a>
            <?php endif; ?>
        </p>
    </div>
</div>
