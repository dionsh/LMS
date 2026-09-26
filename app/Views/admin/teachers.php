<?php
/**
 * @var array|null $year
 * @var list<array> $teachers  User::teachersOverview()
 */

use App\Support\Format;

$homeroom = count(array_filter($teachers, static fn (array $t): bool => $t['homeroom_class_id'] !== null));
$withoutCredentials = count(array_filter($teachers, static fn (array $t): bool => (int) $t['has_credentials'] === 0));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Mësimdhënësit</h1>
        <p class="lead"><?= e(count($teachers)) ?> mësimdhënës · <?= e($homeroom) ?> kujdestarë klase</p>
    </div>
</header>

<?php if ($withoutCredentials > 0): ?>
    <div class="alert admin-note" role="note">
        <?= icon('info') ?>
        <p class="alert__body"><?= e($withoutCredentials) ?> mësimdhënës janë regjistruar pa fletë hyrjeje: ende nuk mund të hyjnë në portal.</p>
    </div>
<?php endif; ?>

<?php if ($teachers === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka mësimdhënës.</h2>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Mësimdhënësi</th>
                    <th scope="col">Kujdestar i klasës</th>
                    <th scope="col">Llogaria</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td data-label="Mësimdhënësi">
                            <span class="table__primary"><?= e(Format::personName($teacher['title'], $teacher['first_name'], $teacher['last_name'])) ?></span>
                            <span class="table__secondary"><?= e($teacher['username']) ?></span>
                        </td>
                        <td data-label="Kujdestar i klasës">
                            <?= $teacher['homeroom_class_id'] !== null
                                ? '<span class="badge badge--ink badge--plain">' . e(Format::classLabel((int) $teacher['grade_level'], (int) $teacher['section'])) . '</span>'
                                : '<span class="meta">—</span>' ?>
                        </td>
                        <td data-label="Llogaria">
                            <?php if ($teacher['status'] !== 'active'): ?>
                                <span class="badge">Joaktive</span>
                            <?php elseif ((int) $teacher['has_credentials'] === 0): ?>
                                <span class="badge badge--plain">Pa fletë hyrjeje</span>
                            <?php else: ?>
                                <span class="badge badge--success">Aktive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
