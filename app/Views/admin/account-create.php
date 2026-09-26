<?php
/**
 * Add a student, teacher or administrator.
 *
 * @var string $role @var array $values @var array $errors @var array $classes @var string $back @var string $title
 */
$intro = match ($role) {
    'student' => 'Nxënësi regjistrohet në klasën e zgjedhur për vitin shkollor aktual.',
    'teacher' => 'Mësimdhënësi mund të hyjë në portal sapo t’i lëshoni fletën e hyrjes.',
    default   => 'Administratorët kanë qasje të plotë në të gjithë portalin. Shtoni vetëm persona të besuar.',
};
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url($back)) ?>"><?= e(match ($role) { 'student' => 'Nxënësit', 'teacher' => 'Mësimdhënësit', default => 'Llogaritë' }) ?></a></li><li aria-current="page"><?= e($title) ?></li></ol>
        </nav>
        <h1 class="page-header__title"><?= e($title) ?></h1>
        <p class="lead"><?= e($intro) ?></p>
    </div>
</header>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Formulari ka <?= e(count($errors)) ?> <?= count($errors) === 1 ? 'gabim' : 'gabime' ?>. Shikoni fushat e shënuara më poshtë.</p>
    </div>
<?php endif; ?>

<form class="card form account-form" method="post" action="<?= e(url(current_path())) ?>" novalidate>
    <?= csrf_field() ?>
    <p class="form-note">Fushat me <span class="field__required">*</span> janë të detyrueshme.</p>
    <?= partial('admin/partials/account-fields', ['role' => $role, 'values' => $values, 'errors' => $errors, 'classes' => $classes, 'creating' => true]) ?>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('plus') ?><?= e($title) ?></button>
        <a class="btn btn--quiet" href="<?= e(url($back)) ?>">Anulo</a>
    </div>
</form>
