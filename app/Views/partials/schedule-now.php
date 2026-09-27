<?php
/**
 * "Tani / Në vazhdim": the lesson in progress and the next one, or a short
 * note when there is nothing left today.
 *
 * @var array $state      Timetable::nowNext(): current, next, minutes_in, minutes_left, length
 * @var bool  $schoolDay  today is Monday–Friday
 * @var bool  $hasLessons there are lessons today at all
 * @var int   $day        today, ISO (1 = Monday)
 * @var string $headingId id for the card's heading
 */

$current = $state['current'];
$next = $state['next'];
$headingId = $headingId ?? 'now-title';
?>
<?php if ($current !== null || $next !== null): ?>
    <?php $shown = $current ?? $next; ?>
    <section class="now" aria-labelledby="<?= e($headingId) ?>">
        <p class="kicker kicker--on-ink">
            <?= $current !== null ? 'Tani' : 'Në vazhdim' ?> · Ora <?= e($shown['period']) ?> · <?= e($shown['starts_at']) ?>–<?= e($shown['ends_at']) ?>
        </p>
        <div>
            <h2 class="now__subject" id="<?= e($headingId) ?>"><?= e($shown['lesson']['subject']) ?></h2>
            <?php if ($shown['lesson']['meta'] !== []): ?>
                <p class="now__meta"><?= e(implode(' · ', $shown['lesson']['meta'])) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($current !== null): ?>
            <div>
                <progress class="progress" value="<?= e($state['minutes_in']) ?>" max="<?= e($state['length']) ?>"><?= e($state['minutes_in']) ?> nga <?= e($state['length']) ?> minuta</progress>
                <p class="now__meta">Edhe <?= e($state['minutes_left']) ?> <?= (int) $state['minutes_left'] === 1 ? 'minutë' : 'minuta' ?></p>
            </div>
            <?php if ($next !== null): ?>
                <p class="now__next">
                    <span>Në vazhdim</span>
                    <strong><?= e($next['lesson']['subject']) ?></strong>
                    <span>Ora <?= e($next['period']) ?> · <?= e($next['starts_at']) ?><?= $next['lesson']['meta'] !== [] ? ' · ' . e($next['lesson']['meta'][0]) : '' ?></span>
                </p>
            <?php else: ?>
                <p class="now__next"><span>Kjo është ora e fundit për sot.</span></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="now__meta">Fillon në <?= e($next['starts_at']) ?>.</p>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="card card--tint now-note" aria-labelledby="<?= e($headingId) ?>">
        <h2 class="card__title" id="<?= e($headingId) ?>"><?= !$schoolDay ? 'Sot nuk ka mësim.' : ($hasLessons ? 'Mësimi për sot mbaroi.' : 'Sot nuk keni orë mësimi.') ?></h2>
        <?php if (!$schoolDay || $day === 5): ?><p class="meta">Mësimi rifillon të hënën.</p><?php endif; ?>
    </section>
<?php endif; ?>
