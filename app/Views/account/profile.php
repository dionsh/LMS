<?php
/**
 * @var array                 $user
 * @var array{email: string, phone: string} $values
 * @var array<string, string> $errors          contact form
 * @var array<string, string> $passwordErrors  password form
 */

use App\Support\Format;
use App\Support\Labels;
?>
<header class="page-header">
    <div>
        <p class="kicker">Llogaria</p>
        <h1 class="page-header__title">Profili</h1>
        <p class="lead">Të dhënat e llogarisë suaj dhe fjalëkalimi.</p>
    </div>
</header>

<div class="dashboard">
    <section class="card span-5" aria-labelledby="account-title">
        <header class="card__head">
            <h2 class="card__title" id="account-title">Llogaria</h2>
            <span class="avatar avatar--lg" aria-hidden="true"><?= e(Format::initials($user['first_name'], $user['last_name'])) ?></span>
        </header>
        <dl class="details">
            <div><dt>Emri dhe mbiemri</dt><dd><?= e($user['first_name'] . ' ' . $user['last_name']) ?></dd></div>
            <div><dt>Emri i përdoruesit</dt><dd><?= e($user['username']) ?></dd></div>
            <div><dt>Roli</dt><dd><?= e(Labels::role($user['role'])) ?></dd></div>
            <div><dt>Hyrja e fundit</dt><dd><?= $user['last_login_at'] ? e(sq_date($user['last_login_at'], 'datetime')) : '—' ?></dd></div>
        </dl>
        <p class="form-note profile-note">Emri dhe mbiemri vendosen nga shkolla. Nëse ka ndonjë gabim, njoftoni administratën.</p>
    </section>

    <div class="span-7 stack--lg">
        <section class="card" aria-labelledby="contact-title">
            <header class="card__head"><h2 class="card__title" id="contact-title">Kontakti</h2></header>
            <form class="form" method="post" action="<?= e(url('/profili')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label class="field__label" for="email">Email-i</label>
                        <input class="input" id="email" name="email" type="email" value="<?= e($values['email']) ?>" autocomplete="email"
                               <?= field_invalid($errors, 'email', 'email-hint') ?: ' aria-describedby="email-hint"' ?>>
                        <p class="field__hint" id="email-hint">Opsional. Mund ta përdorni edhe për të hyrë në portal.</p>
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="phone">Telefoni</label>
                        <input class="input" id="phone" name="phone" type="tel" value="<?= e($values['phone']) ?>" autocomplete="tel"<?= field_invalid($errors, 'phone') ?>>
                        <?= field_error($errors, 'phone') ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Ruaj ndryshimet</button>
                </div>
            </form>
        </section>

        <section class="card" aria-labelledby="password-title">
            <header class="card__head"><h2 class="card__title" id="password-title">Ndrysho fjalëkalimin</h2></header>
            <form class="form" method="post" action="<?= e(url('/profili/fjalekalimi')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="username" value="<?= e($user['username']) ?>" autocomplete="username">
                <div class="field">
                    <label class="field__label" for="current_password">Fjalëkalimi aktual</label>
                    <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password"<?= field_invalid($passwordErrors, 'current_password') ?>>
                    <?= field_error($passwordErrors, 'current_password') ?>
                </div>
                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label class="field__label" for="password">Fjalëkalimi i ri</label>
                        <input class="input" id="password" name="password" type="password" autocomplete="new-password"
                               <?= field_invalid($passwordErrors, 'password', 'password-hint') ?: ' aria-describedby="password-hint"' ?>>
                        <p class="field__hint" id="password-hint">Të paktën 8 karaktere.</p>
                        <?= field_error($passwordErrors, 'password') ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="password_confirmation">Përsëriteni fjalëkalimin e ri</label>
                        <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"<?= field_invalid($passwordErrors, 'password_confirmation') ?>>
                        <?= field_error($passwordErrors, 'password_confirmation') ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn--secondary" type="submit">Ndrysho fjalëkalimin</button>
                </div>
            </form>
        </section>
    </div>
</div>
