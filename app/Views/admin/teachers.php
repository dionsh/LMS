<?php
/**
 * @var list<array> $teachers @var App\Support\Paginator $paginator @var array $filters @var array $query
 * @var string $access  '' | 'pa-flete' | 'me-flete'
 * @var int $waiting    active teachers without credentials
 */

use App\Support\Format;
?>
<header class="page-header">
    <div>
        <p class="kicker">Njerëzit</p>
        <h1 class="page-header__title">Mësimdhënësit</h1>
        <p class="lead"><?= e(sq_number($paginator->total)) ?> mësimdhënës<?= $query !== [] ? ' sipas kërkimit' : '' ?></p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/admin/mesimdhenesit/shto')) ?>"><?= icon('plus') ?>Shto mësimdhënës</a>
</header>

<form class="filters" method="get" action="<?= e(url('/admin/mesimdhenesit')) ?>" role="search">
    <div class="field filters__search">
        <label class="field__label" for="q">Kërko</label>
        <input class="input" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Emri ose mbiemri">
    </div>
    <div class="field">
        <label class="field__label" for="llogaria">Hyrja në portal</label>
        <select class="select" id="llogaria" name="llogaria">
            <option value="">Të gjithë</option>
            <option value="pa-flete"<?= $access === 'pa-flete' ? ' selected' : '' ?>>Pa fletë hyrjeje</option>
            <option value="me-flete"<?= $access === 'me-flete' ? ' selected' : '' ?>>Me fletë hyrjeje</option>
        </select>
    </div>
    <div class="field">
        <label class="field__label" for="gjendja">Gjendja</label>
        <select class="select" id="gjendja" name="gjendja">
            <option value="">Të gjitha</option>
            <option value="aktiv"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Aktiv</option>
            <option value="joaktiv"<?= $filters['status'] === 'inactive' ? ' selected' : '' ?>>Joaktiv</option>
        </select>
    </div>
    <div class="filters__actions">
        <button class="btn btn--secondary" type="submit"><?= icon('search') ?>Kërko</button>
        <?php if ($query !== []): ?><a class="btn btn--quiet" href="<?= e(url('/admin/mesimdhenesit')) ?>">Pastro</a><?php endif; ?>
    </div>
</form>

<?php if ($waiting > 0): ?>
    <div class="callout">
        <div>
            <strong><?= e($waiting) ?> mësimdhënës ende nuk <?= $waiting === 1 ? 'ka' : 'kanë' ?> fletë hyrjeje.</strong>
            <p class="meta">Pa të, nuk mund të hyjnë në portal. Lëshojini të gjitha me një hap dhe printojini.</p>
        </div>
        <form method="post" action="<?= e(url('/admin/mesimdhenesit/fletet')) ?>"
              data-confirm="Të lëshohen <?= e($waiting) ?> fletë hyrjeje?"
              data-confirm-text="Për çdo mësimdhënës pa fletë krijohet një fjalëkalim i përkohshëm. Printojini menjëherë: fjalëkalimet shfaqen vetëm një herë."
              data-confirm-button="Lësho fletët">
            <?= csrf_field() ?>
            <button class="btn btn--primary" type="submit"><?= icon('file') ?>Lësho fletët e hyrjes</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($teachers === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title"><?= $query !== [] ? 'Asnjë mësimdhënës nuk përputhet me kërkimin.' : 'Ende nuk ka mësimdhënës.' ?></h2>
    </div>
<?php else: ?>
    <p class="list-summary meta">Po shfaqen <?= e($paginator->from()) ?>–<?= e($paginator->to()) ?> nga <?= e(sq_number($paginator->total)) ?></p>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Mësimdhënësi</th>
                    <th scope="col">Kujdestar i klasës</th>
                    <th scope="col">Llogaria</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td data-label="Mësimdhënësi">
                            <a class="table__primary table__link" href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>"><?= e(Format::personName($teacher['title'], $teacher['first_name'], $teacher['last_name'])) ?></a>
                            <span class="table__secondary"><?= e($teacher['username']) ?></span>
                        </td>
                        <td data-label="Kujdestar i klasës">
                            <?= $teacher['homeroom_class_id'] !== null
                                ? '<span class="badge badge--ink badge--plain">' . e(Format::classLabel((int) $teacher['homeroom_grade'], (int) $teacher['homeroom_section'])) . '</span>'
                                : '<span class="meta">—</span>' ?>
                        </td>
                        <td data-label="Llogaria"><?= partial('admin/partials/account-state', ['person' => $teacher]) ?></td>
                        <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/perdoruesit/' . $teacher['id'] . '/ndrysho')) ?>">Ndrysho</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= partial('partials/pagination', ['paginator' => $paginator, 'query' => $query]) ?>
<?php endif; ?>
