<?php
/**
 * @var array $user
 * @var array $view  TimetableView::forTeacher()
 * @var DateTimeImmutable $now
 */

use App\Support\Format;
use App\Support\Labels;
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= e(Format::ucfirst(sq_date($now, 'long'))) ?></p>
        <h1 class="page-header__title"><?= e(Format::greeting($now)) ?>, <?= e($user['first_name']) ?>.</h1>
        <p class="lead">
            <?= $view['lessons'] > 0
                ? e($view['lessons']) . ' orë në javë · ' . e($view['classes']) . ' ' . ($view['classes'] === 1 ? 'klasë' : 'klasa')
                : 'Portali i mësimdhënësit.' ?>
        </p>
    </div>
    <?php if ($view['lessons'] > 0): ?>
        <a class="btn btn--secondary" href="<?= e(url('/mesimdhenesi/orari')) ?>"><?= icon('calendar') ?>Orari javor</a>
    <?php endif; ?>
</header>

<?php $dutyToday = array_values(array_filter($view['duty'], static fn (array $d): bool => $view['schoolDay'] && (int) $d['day'] === $view['day'])); ?>
<?php if ($dutyToday !== []): ?>
    <div class="callout" role="status">
        <div>
            <strong>Sot keni kujdestarinë e ditës: <?= e($dutyToday[0]['post']) ?>.</strong>
            <p class="meta">Ndërrimi i <?= e(Labels::SHIFTS_OF[(int) $dutyToday[0]['shift']]) ?>.</p>
        </div>
    </div>
<?php endif; ?>

<?php if ($view['lessons'] > 0): ?>
    <div class="dashboard">
        <div class="span-7">
            <?= partial('partials/schedule-now', [
                'state'      => $view['state'],
                'schoolDay'  => $view['schoolDay'],
                'hasLessons' => $view['today'] !== [],
                'day'        => $view['day'],
            ]) ?>
        </div>
        <section class="card span-5" aria-labelledby="today-title">
            <header class="card__head">
                <h2 class="card__title" id="today-title">Orët e sotme</h2>
                <a class="link-arrow" href="<?= e(url('/mesimdhenesi/orari')) ?>">Java <?= icon('arrow-right') ?></a>
            </header>
            <?php if ($view['today'] === []): ?>
                <p class="meta"><?= $view['schoolDay'] ? 'Sot nuk keni orë mësimi.' : 'Sot nuk ka mësim.' ?></p>
            <?php else: ?>
                <ol class="lessons" role="list">
                    <?php foreach ($view['today'] as $item): ?>
                        <?php $isNow = $view['state']['current'] !== null && $view['state']['current']['starts_at'] === $item['starts_at']; ?>
                        <li class="lesson<?= $isNow ? ' is-current' : '' ?>"<?= $isNow ? ' aria-current="time"' : '' ?>>
                            <span class="lesson__time"><?= e($item['starts_at']) ?><span><?= e($item['ends_at']) ?></span></span>
                            <span>
                                <span class="lesson__period">Ora <?= e($item['period']) ?></span>
                                <span class="lesson__subject"><?= e($item['lesson']['subject']) ?></span>
                                <span class="lesson__meta"><?= e(implode(' · ', $item['lesson']['meta'])) ?></span>
                            </span>
                            <?php if ($isNow): ?><span class="badge badge--info">Tani</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>

<div class="empty portal-section">
    <div class="empty__mark motif" aria-hidden="true"></div>
    <h2 class="empty__title">Së shpejti edhe më shumë këtu</h2>
    <p class="empty__text">Dorëzimet që presin vlerësim, detyrat e fundit dhe testet e ardhshme të klasave tuaja do të shfaqen në këtë panel.</p>
</div>
