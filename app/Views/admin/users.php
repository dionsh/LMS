<?php
/**
 * Every account, all roles.
 *
 * @var list<array> $users @var App\Support\Paginator $paginator @var array $filters @var array $query @var string $role
 */

use App\Support\Format;
use App\Support\Labels;
?>
<header class="page-header">
    <div>
        <p class="kicker">Njerëzit</p>
        <h1 class="page-header__title">Llogaritë</h1>
        <p class="lead">Të gjitha llogaritë e portalit: nxënës, mësimdhënës dhe administratorë.</p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/admin/perdoruesit/shto')) ?>"><?= icon('plus') ?>Shto administrator</a>
</header>

<form class="filters" method="get" action="<?= e(url('/admin/perdoruesit')) ?>" role="search">
    <div class="field filters__search">
        <label class="field__label" for="q">Kërko</label>
        <input class="input" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Emri, përdoruesi ose email-i">
    </div>
    <div class="field">
        <label class="field__label" for="roli">Roli</label>
        <select class="select" id="roli" name="roli">
            <option value="">Të gjithë</option>
            <option value="nxenes"<?= $role === 'nxenes' ? ' selected' : '' ?>>Nxënës</option>
            <option value="mesimdhenes"<?= $role === 'mesimdhenes' ? ' selected' : '' ?>>Mësimdhënës</option>
            <option value="administrator"<?= $role === 'administrator' ? ' selected' : '' ?>>Administrator</option>
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
        <?php if ($query !== []): ?><a class="btn btn--quiet" href="<?= e(url('/admin/perdoruesit')) ?>">Pastro</a><?php endif; ?>
    </div>
</form>

<?php if ($users === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Asnjë llogari nuk përputhet me kërkimin.</h2>
    </div>
<?php else: ?>
    <p class="list-summary meta">Po shfaqen <?= e($paginator->from()) ?>–<?= e($paginator->to()) ?> nga <?= e(sq_number($paginator->total)) ?></p>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Emri</th>
                    <th scope="col">Roli</th>
                    <th scope="col">Llogaria</th>
                    <th scope="col">Hyrja e fundit</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $person): ?>
                    <tr>
                        <td data-label="Emri">
                            <a class="table__primary table__link" href="<?= e(url('/admin/perdoruesit/' . $person['id'] . '/ndrysho')) ?>"><?= e(Format::personName($person['role'] === 'teacher' ? $person['title'] : null, $person['first_name'], $person['last_name'])) ?></a>
                            <span class="table__secondary"><?= e($person['username']) ?><?= $person['email'] ? ' · ' . e($person['email']) : '' ?></span>
                        </td>
                        <td data-label="Roli"><?= e(Labels::role($person['role'])) ?></td>
                        <td data-label="Llogaria"><?= partial('admin/partials/account-state', ['person' => $person]) ?></td>
                        <td data-label="Hyrja e fundit"><?= $person['last_login_at'] ? e(sq_date($person['last_login_at'], 'datetime')) : '<span class="meta">Asnjëherë</span>' ?></td>
                        <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/perdoruesit/' . $person['id'] . '/ndrysho')) ?>">Ndrysho</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= partial('partials/pagination', ['paginator' => $paginator, 'query' => $query]) ?>
<?php endif; ?>
