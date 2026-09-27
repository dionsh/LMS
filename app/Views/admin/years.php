<?php
/**
 * @var list<array> $years   AcademicYear::overview(), newest first
 * @var array<int, list<array>> $terms  year id => terms
 * @var array $values @var array $errors  the "add a year" form
 */
?>
<header class="page-header">
    <div>
        <p class="kicker">Shkolla</p>
        <h1 class="page-header__title">Vitet shkollore</h1>
        <p class="lead">Portali punon me vitin aktual: klasat, orari dhe notat e tij. Vitet e kaluara ruhen të plota.</p>
    </div>
</header>

<?php if ($years !== []): ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Viti shkollor</th>
                    <th scope="col">Gjysmëvjetorët</th>
                    <th scope="col" class="num">Klasa</th>
                    <th scope="col" class="num">Nxënës</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($years as $year): ?>
                    <tr>
                        <td data-label="Viti shkollor">
                            <span class="table__primary"><?= e($year['name']) ?></span>
                            <?php if ((int) $year['is_current'] === 1): ?><span class="badge badge--info">Aktual</span><?php endif; ?>
                            <span class="table__secondary"><?= e(sq_date($year['starts_on'])) ?> – <?= e(sq_date($year['ends_on'])) ?></span>
                        </td>
                        <td data-label="Gjysmëvjetorët">
                            <?php foreach ($terms[(int) $year['id']] ?? [] as $term): ?>
                                <span class="table__secondary"><?= e($term['name']) ?>: <?= e(sq_date($term['starts_on'], 'day_month')) ?> – <?= e(sq_date($term['ends_on'], 'day_month')) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td data-label="Klasa" class="num"><?= e($year['classes']) ?></td>
                        <td data-label="Nxënës" class="num"><?= e(sq_number((int) $year['students'])) ?></td>
                        <td data-label="Veprime">
                            <span class="cluster cluster--tight">
                                <a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/vitet-shkollore/' . $year['id'] . '/ndrysho')) ?>">Ndrysho</a>
                                <?php if ((int) $year['is_current'] === 0): ?>
                                    <form method="post" action="<?= e(url('/admin/vitet-shkollore/' . $year['id'] . '/aktual')) ?>"
                                          data-confirm="Të bëhet <?= e($year['name']) ?> vit aktual?"
                                          data-confirm-text="Portali do të tregojë klasat, orarin dhe notat e këtij viti. <?= (int) $year['classes'] === 0 ? 'Ky vit nuk ka ende klasa: nxënësit nuk do të shohin klasë deri sa t’i shtoni.' : '' ?>"
                                          data-confirm-button="Bëje aktual">
                                        <?= csrf_field() ?>
                                        <button class="btn btn--secondary btn--sm" type="submit">Bëje aktual</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<section class="card portal-section" aria-labelledby="add-year-title">
    <h2 class="card__title" id="add-year-title">Shto vit shkollor</h2>
    <p class="meta plan-add__text">Viti i ri shtohet pa klasa dhe nuk bëhet aktual derisa ta zgjidhni. Kalimi i klasave në vitin e ri vjen me mjetin e fundvitit.</p>
    <?php if ($errors !== []): ?>
        <div class="alert alert--danger admin-note" role="alert">
            <?= icon('alert') ?>
            <p class="alert__body">Viti nuk u shtua. Shikoni fushat e shënuara.</p>
        </div>
    <?php endif; ?>
    <form class="form" method="post" action="<?= e(url('/admin/vitet-shkollore/shto')) ?>" novalidate>
        <?= csrf_field() ?>
        <?= partial('admin/partials/year-fields', ['values' => $values, 'errors' => $errors]) ?>
        <div class="form-actions">
            <button class="btn btn--secondary" type="submit"><?= icon('plus') ?>Shto vitin</button>
        </div>
    </form>
</section>
