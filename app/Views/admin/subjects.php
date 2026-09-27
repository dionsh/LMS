<?php
/**
 * @var list<array> $subjects  Subject::overview()
 */

use App\Support\Format;

$active = count(array_filter($subjects, static fn (array $s): bool => (int) $s['is_active'] === 1));
?>
<header class="page-header">
    <div>
        <p class="kicker">Shkolla</p>
        <h1 class="page-header__title">Lëndët</h1>
        <p class="lead"><?= e($active) ?> lëndë aktive<?= count($subjects) > $active ? ' · ' . e(count($subjects) - $active) . ' joaktive' : '' ?></p>
    </div>
    <div class="cluster">
        <a class="btn btn--secondary" href="<?= e(url('/admin/plani-mesimor')) ?>"><?= icon('clipboard') ?>Plani mësimor</a>
        <a class="btn btn--primary" href="<?= e(url('/admin/lendet/shto')) ?>"><?= icon('plus') ?>Shto lëndë</a>
    </div>
</header>

<?php if ($subjects === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka lëndë.</h2>
        <p class="empty__text">Shtoni lëndët që mëson shkolla dhe zgjidhni klasat që i mësojnë.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Lënda</th>
                    <th scope="col">Klasat</th>
                    <th scope="col" class="num">Mësimdhënës</th>
                    <th scope="col" class="num">Paralele</th>
                    <th scope="col">Gjendja</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td data-label="Lënda">
                            <a class="table__primary table__link" href="<?= e(url('/admin/lendet/' . $subject['id'] . '/ndrysho')) ?>"><?= e($subject['name']) ?></a>
                            <span class="table__secondary"><?= e($subject['short_name']) ?></span>
                        </td>
                        <td data-label="Klasat">
                            <?php if ($subject['fills_subject_id'] !== null): ?>
                                <span class="meta">Zgjedhore, në vend të <?= e($subject['fills_name']) ?></span>
                            <?php elseif ($subject['grades'] === null): ?>
                                <span class="meta">Asnjë</span>
                            <?php else: ?>
                                <span class="cluster cluster--tight">
                                    <?php foreach (explode(',', (string) $subject['grades']) as $level): ?>
                                        <span class="badge badge--plain"><?= e(Format::grade((int) $level)) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Mësimdhënës" class="num"><?= e($subject['teachers']) ?></td>
                        <td data-label="Paralele" class="num"><?= e($subject['classes']) ?></td>
                        <td data-label="Gjendja"><?= (int) $subject['is_active'] === 1 ? '<span class="badge badge--success">Aktive</span>' : '<span class="badge">Joaktive</span>' ?></td>
                        <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/lendet/' . $subject['id'] . '/ndrysho')) ?>">Ndrysho</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
