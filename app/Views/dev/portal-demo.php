<?php
/**
 * SAMPLE student dashboard inside the real portal layout (development only).
 * The real dashboards are built on live data in T15.
 *
 * @var array $user @var string $roleParam @var array $periods @var array $week
 * @var array $homework @var array $grades @var array $announcements
 */

use App\Controllers\Dev\DemoData;

$day = DemoData::DAY;
$period = DemoData::PERIOD;
$lesson = $week[$day][$period];
$next = $week[$day][$period + 1] ?? null;
$minutes = DemoData::MINUTES_INTO_LESSON;
$roles = ['nxenes' => 'Nxënës', 'mesimdhenes' => 'Mësimdhënës', 'admin' => 'Administrator'];
?>
<div class="alert portal-demo-note" role="note">
    <?= icon('info') ?>
    <p class="alert__body">
        <strong class="alert__title">Shembull i portalit (T03)</strong>
        Të dhënat janë ilustruese. Shiko menunë si:
        <?php foreach ($roles as $param => $label): ?>
            <a href="<?= e(route('dev.portal', [], ['roli' => $param])) ?>"<?= $param === $roleParam ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?= $param !== 'admin' ? ' ·' : '' ?>
        <?php endforeach; ?>
    </p>
</div>

<header class="page-header">
    <div>
        <p class="kicker">E premte, 2 tetor 2026</p>
        <h1 class="page-header__title">Mirëmëngjes, <?= e($user['first_name']) ?>.</h1>
        <p class="lead">Klasa XI-5 · Kujdestari: Prof. Drita Berisha</p>
    </div>
    <a class="btn btn--secondary" href="#orari-javor"><?= icon('calendar') ?>Orari javor</a>
</header>

<div class="dashboard">
    <section class="now span-7" aria-labelledby="now-title">
        <p class="kicker kicker--on-ink">Tani · Ora <?= e($period) ?> · <?= e($periods[$period]['starts_at']) ?>–<?= e($periods[$period]['ends_at']) ?></p>
        <div>
            <h2 class="now__subject" id="now-title"><?= e($lesson['subject']) ?></h2>
            <p class="now__meta"><?= e(implode(' · ', $lesson['meta'])) ?></p>
        </div>
        <div>
            <progress class="progress" value="<?= e($minutes) ?>" max="45"><?= e($minutes) ?> nga 45 minuta</progress>
            <p class="now__meta meta">Edhe <?= e(45 - $minutes) ?> minuta</p>
        </div>
        <?php if ($next !== null): ?>
            <p class="now__next">
                <span>Në vazhdim</span>
                <strong><?= e($next['subject']) ?></strong>
                <span>Ora <?= e($period + 1) ?> · <?= e($periods[$period + 1]['starts_at']) ?> · <?= e($next['meta'][1]) ?></span>
            </p>
        <?php endif; ?>
    </section>

    <section class="card span-5" aria-labelledby="today-title">
        <header class="card__head"><h2 class="card__title" id="today-title">Sot</h2><span class="meta">5 orë mësimi</span></header>
        <?= partial('partials/timetable-day', ['periods' => $periods, 'lessons' => $week[$day], 'current' => $period, 'compact' => true]) ?>
    </section>

    <section class="card span-7" aria-labelledby="homework-title">
        <header class="card__head"><h2 class="card__title" id="homework-title">Detyrat në pritje</h2><a class="link-arrow" href="<?= e(url('/nxenesi/detyrat')) ?>">Të gjitha <?= icon('arrow-right') ?></a></header>
        <ul class="item-list" role="list">
            <?php foreach ($homework as $item): ?>
                <li>
                    <div>
                        <span class="item__kicker"><?= e($item['subject']) ?></span>
                        <a class="item__title" href="<?= e(url('/nxenesi/detyrat')) ?>"><?= e($item['title']) ?></a>
                        <span class="item__meta">Afati: <?= e(sq_date($item['due'], 'long')) ?>, <?= e(sq_date($item['due'], 'time')) ?></span>
                    </div>
                    <span class="badge badge--<?= e($item['status'][1]) ?>"><?= e($item['status'][0]) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card span-5" aria-labelledby="grades-title">
        <header class="card__head"><h2 class="card__title" id="grades-title">Notat e fundit</h2><a class="link-arrow" href="<?= e(url('/nxenesi/notat')) ?>">Notat <?= icon('arrow-right') ?></a></header>
        <ul class="item-list" role="list">
            <?php foreach ($grades as $grade): ?>
                <li>
                    <div class="cluster">
                        <span class="grade grade--<?= e($grade['grade']) ?>" title="<?= e(App\Support\Labels::grade($grade['grade'])) ?>"><?= e($grade['grade']) ?></span>
                        <div>
                            <span class="item__title"><?= e($grade['subject']) ?></span>
                            <span class="item__meta"><?= e($grade['type']) ?></span>
                        </div>
                    </div>
                    <span class="meta"><?= e(sq_date($grade['date'], 'day_month')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card span-7" aria-labelledby="news-title">
        <header class="card__head"><h2 class="card__title" id="news-title">Njoftimet</h2><a class="link-arrow" href="<?= e(url('/nxenesi/njoftimet')) ?>">Të gjitha <?= icon('arrow-right') ?></a></header>
        <ul class="item-list" role="list">
            <?php foreach ($announcements as $announcement): ?>
                <li>
                    <div>
                        <span class="item__kicker"><?= e($announcement['by']) ?> · <?= e(sq_date($announcement['date'], 'day_month')) ?></span>
                        <span class="item__title"><?= e($announcement['title']) ?></span>
                        <span class="item__meta"><?= e($announcement['body']) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card card--tint span-5" aria-labelledby="school-title">
        <p class="kicker kicker--clay">Nga shkolla · Arritje</p>
        <h2 class="card__title" id="school-title">Dy nxënës të shkollës fitojnë Hackathonin Kombëtar</h2>
        <p class="meta portal-demo-gap">24 shtator 2026</p>
        <a class="link-arrow portal-demo-gap" href="<?= e(url('/lajme')) ?>">Lexo lajmin <?= icon('arrow-right') ?></a>
    </section>
</div>

<section class="portal-section" id="orari-javor" aria-labelledby="week-title">
    <header class="section-head"><h2 class="section-head__title" id="week-title">Orari javor</h2><span class="meta">Klasa XI-5 · paradite</span></header>

    <div class="schedule-days">
        <div class="tabs" role="tablist" aria-label="Ditët e javës">
            <?php foreach ([1, 2, 3, 4, 5] as $weekday): ?>
                <button class="tab" type="button" role="tab" id="demo-tab-<?= e($weekday) ?>" aria-controls="demo-day-<?= e($weekday) ?>"
                        aria-selected="<?= $weekday === $day ? 'true' : 'false' ?>" tabindex="<?= $weekday === $day ? '0' : '-1' ?>">
                    <?= e(App\Support\Labels::DAYS_SHORT[$weekday]) ?><?php if ($weekday === $day): ?><span class="tab__note">sot</span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php foreach ([1, 2, 3, 4, 5] as $weekday): ?>
            <div class="tab-panel" role="tabpanel" id="demo-day-<?= e($weekday) ?>" aria-labelledby="demo-tab-<?= e($weekday) ?>" <?= $weekday === $day ? '' : 'hidden' ?>>
                <?= partial('partials/timetable-day', ['periods' => $periods, 'lessons' => $week[$weekday], 'current' => $weekday === $day ? $period : null]) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="schedule-week">
        <?= partial('partials/timetable-week', ['periods' => $periods, 'week' => $week, 'today' => $day, 'current' => $period, 'caption' => 'Orari javor i klasës XI-5']) ?>
    </div>
</section>
