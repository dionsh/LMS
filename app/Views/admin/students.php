<?php
/**
 * @var list<array> $students @var App\Support\Paginator $paginator @var array $filters @var array $query
 * @var array|null $class  the class filtered on @var array $classes grouped options @var int $waiting
 */

use App\Support\Format;

$classLabel = $class !== null ? Format::classLabel((int) $class['grade_level'], (int) $class['section']) : null;
?>
<header class="page-header">
    <div>
        <p class="kicker">Njerëzit</p>
        <h1 class="page-header__title"><?= $classLabel ? 'Nxënësit e klasës ' . e($classLabel) : 'Nxënësit' ?></h1>
        <p class="lead"><?= e(sq_number($paginator->total)) ?> nxënës<?= $filters['q'] !== '' || $filters['status'] ? ' sipas kërkimit' : '' ?></p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/admin/nxenesit/shto', $class ? ['klasa' => $class['id']] : [])) ?>"><?= icon('plus') ?>Shto nxënës</a>
</header>

<form class="filters" method="get" action="<?= e(url('/admin/nxenesit')) ?>" role="search">
    <div class="field filters__search">
        <label class="field__label" for="q">Kërko</label>
        <input class="input" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Emri ose mbiemri">
    </div>
    <div class="field">
        <label class="field__label" for="klasa">Klasa</label>
        <select class="select" id="klasa" name="klasa">
            <option value="">Të gjitha klasat</option>
            <?php foreach ($classes as $grade => $options): ?>
                <optgroup label="Klasa <?= e($grade) ?>">
                    <?php foreach ($options as $option): ?>
                        <option value="<?= e($option['id']) ?>"<?= $class !== null && (int) $class['id'] === $option['id'] ? ' selected' : '' ?>><?= e($option['label']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
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
        <?php if ($query !== []): ?><a class="btn btn--quiet" href="<?= e(url('/admin/nxenesit')) ?>">Pastro</a><?php endif; ?>
    </div>
</form>

<?php if ($class !== null && $waiting > 0): ?>
    <div class="callout">
        <div>
            <strong><?= e($waiting) ?> nxënës të klasës <?= e($classLabel) ?> ende nuk <?= $waiting === 1 ? 'ka' : 'kanë' ?> hyrë në portal.</strong>
            <p class="meta">Lëshoni dhe printoni fletët e tyre të hyrjes, që kujdestari t’ua shpërndajë.</p>
        </div>
        <form method="post" action="<?= e(url('/admin/nxenesit/fletet')) ?>"
              data-confirm="Të lëshohen <?= e($waiting) ?> fletë hyrjeje?"
              data-confirm-text="Për çdo nxënës krijohet një fjalëkalim i ri i përkohshëm. Fletët e mëparshme të këtyre nxënësve nuk do të vlejnë më."
              data-confirm-button="Lësho fletët">
            <?= csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= e($class['id']) ?>">
            <button class="btn btn--primary" type="submit"><?= icon('file') ?>Lësho fletët e hyrjes</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($students === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title"><?= $query !== [] ? 'Asnjë nxënës nuk përputhet me kërkimin.' : 'Ende nuk ka nxënës.' ?></h2>
        <p class="empty__text"><?= $query !== [] ? 'Provoni një emër tjetër ose pastroni filtrat.' : 'Shtoni nxënësit e parë dhe caktojini në klasat e tyre.' ?></p>
    </div>
<?php else: ?>
    <p class="list-summary meta">Po shfaqen <?= e($paginator->from()) ?>–<?= e($paginator->to()) ?> nga <?= e(sq_number($paginator->total)) ?></p>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Nxënësi</th>
                    <th scope="col">Klasa</th>
                    <th scope="col">Llogaria</th>
                    <th scope="col">Hyrja e fundit</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td data-label="Nxënësi">
                            <a class="table__primary table__link" href="<?= e(url('/admin/perdoruesit/' . $student['id'] . '/ndrysho')) ?>"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></a>
                            <span class="table__secondary"><?= e($student['username']) ?></span>
                        </td>
                        <td data-label="Klasa">
                            <?= $student['class_id'] !== null
                                ? '<span class="badge badge--ink badge--plain">' . e(Format::classLabel((int) $student['class_grade'], (int) $student['class_section'])) . '</span>'
                                : '<span class="meta">Pa klasë</span>' ?>
                        </td>
                        <td data-label="Llogaria"><?= partial('admin/partials/account-state', ['person' => $student]) ?></td>
                        <td data-label="Hyrja e fundit"><?= $student['last_login_at'] ? e(sq_date($student['last_login_at'], 'datetime')) : '<span class="meta">Asnjëherë</span>' ?></td>
                        <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/perdoruesit/' . $student['id'] . '/ndrysho')) ?>">Ndrysho</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= partial('partials/pagination', ['paginator' => $paginator, 'query' => $query]) ?>
<?php endif; ?>
