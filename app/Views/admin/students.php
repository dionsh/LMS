<?php
/**
 * @var array|null $year
 * @var list<array> $students  User::studentsOverview()
 */

use App\Support\Format;
use App\Support\Labels;

$enrolled = count(array_filter($students, static fn (array $s): bool => $s['class_id'] !== null));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= $year ? 'Viti shkollor ' . e($year['name']) : 'Pa vit shkollor aktual' ?></p>
        <h1 class="page-header__title">Nxënësit</h1>
        <p class="lead"><?= e(count($students)) ?> nxënës · <?= e($enrolled) ?> të regjistruar në klasa</p>
    </div>
</header>

<?php if ($students === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka nxënës.</h2>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Nxënësi</th>
                    <th scope="col">Klasa</th>
                    <th scope="col">Gjendja</th>
                    <th scope="col">Hyrja e fundit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td data-label="Nxënësi">
                            <span class="table__primary"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></span>
                            <span class="table__secondary"><?= e($student['username']) ?></span>
                        </td>
                        <td data-label="Klasa">
                            <?= $student['class_id'] !== null
                                ? '<span class="badge badge--ink badge--plain">' . e(Format::classLabel((int) $student['grade_level'], (int) $student['section'])) . '</span>'
                                : '<span class="meta">Pa klasë</span>' ?>
                        </td>
                        <td data-label="Gjendja">
                            <span class="badge <?= $student['status'] === 'active' ? 'badge--success' : '' ?>"><?= e(Labels::USER_STATUSES[$student['status']] ?? $student['status']) ?></span>
                        </td>
                        <td data-label="Hyrja e fundit"><?= $student['last_login_at'] ? e(sq_date($student['last_login_at'], 'datetime')) : '<span class="meta">Asnjëherë</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
