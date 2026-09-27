<?php
/**
 * @var array $year @var array $values @var array $errors
 */
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/vitet-shkollore')) ?>">Vitet shkollore</a></li><li aria-current="page"><?= e($year['name']) ?></li></ol>
        </nav>
        <h1 class="page-header__title">Viti shkollor <?= e($year['name']) ?></h1>
        <?php if ((int) $year['is_current'] === 1): ?><p class="lead">Ky është viti aktual.</p><?php endif; ?>
    </div>
</header>

<?php if ($errors !== []): ?>
    <div class="alert alert--danger admin-note" role="alert">
        <?= icon('alert') ?>
        <p class="alert__body">Ndryshimet nuk u ruajtën. Shikoni fushat e shënuara.</p>
    </div>
<?php endif; ?>

<form class="card form" method="post" action="<?= e(url('/admin/vitet-shkollore/' . $year['id'] . '/ndrysho')) ?>" novalidate>
    <?= csrf_field() ?>
    <?= partial('admin/partials/year-fields', ['values' => $values, 'errors' => $errors]) ?>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Ruaj ndryshimet</button>
        <a class="btn btn--quiet" href="<?= e(url('/admin/vitet-shkollore')) ?>">Anulo</a>
    </div>
</form>
