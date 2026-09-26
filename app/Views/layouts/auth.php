<?php
/**
 * Sign-in layout: the school on one side, the form on the other.
 *
 * @var string      $content
 * @var string|null $title
 */
?>
<!doctype html>
<html lang="sq">
<head>
<?= partial('partials/head', ['title' => $title ?? null, 'styles' => ['auth']]) ?>
</head>
<body>
    <a class="skip-link" href="#main">Kalo te përmbajtja</a>

    <div class="auth">
        <aside class="auth__visual" aria-label="<?= e(setting('school_name', 'Gjimnazi “Kuvendi i Arbërit”')) ?>">
            <img class="auth__photo" src="<?= e(asset('img/school.jpg')) ?>" alt="" width="640" height="480">
            <div class="auth__lines motif" aria-hidden="true"></div>
            <?= partial('partials/brand', ['variant' => 'light']) ?>
            <div class="auth__statement">
                <p>Dija ndërtohet bashkë.</p>
                <p>Orari, detyrat, notat dhe njoftimet e shkollës, në një vend për nxënësit dhe mësimdhënësit.</p>
            </div>
        </aside>

        <main id="main" class="auth__panel" tabindex="-1">
            <?= $content ?>
        </main>
    </div>
</body>
</html>
