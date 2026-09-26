<?php
/**
 * Public website layout.
 *
 * @var string        $content  rendered view HTML
 * @var string|null   $title
 * @var list<string>  $styles   extra stylesheets (optional)
 */
?>
<!doctype html>
<html lang="sq">
<head>
<?= partial('partials/head', [
    'title'       => $title ?? null,
    'description' => $description ?? null,
    'styles'      => array_merge(['site'], $styles ?? []),
    'scripts'     => $scripts ?? [],
]) ?>
</head>
<body>
    <a class="skip-link" href="#main">Kalo te përmbajtja</a>

    <?= partial('partials/site-header') ?>

    <main id="main" tabindex="-1">
        <?php $flash = partial('partials/flash'); ?>
        <?php if ($flash !== ''): ?>
            <div class="container section--tight"><?= $flash ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>

    <?= partial('partials/site-footer') ?>
    <?= partial('partials/confirm-dialog') ?>
</body>
</html>
