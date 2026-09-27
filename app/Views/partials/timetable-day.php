<?php
/**
 * One day's lessons as a list (phones, dashboard "Sot").
 *
 * @var array<int, array{number: int, starts_at: string, ends_at: string, break_after: ?int}> $periods
 * @var array<int, ?array{subject: string, meta: list<string>}>                                $lessons  period => lesson
 * @var int|null $current  period in progress (lessons before it are shown as past)
 * @var bool     $compact  hide the details (teacher, room)
 * @var bool     $hideFree leave out free periods (a teacher's day)
 */
$current = $current ?? null;
$compact = $compact ?? false;
$hideFree = $hideFree ?? false;
?>
<ol class="lessons" role="list">
    <?php foreach ($periods as $number => $period): ?>
        <?php
        $lesson = $lessons[$number] ?? null;
        if ($lesson === null && $hideFree) {
            continue;
        }
        $state = $current === null ? '' : ($number < $current ? ' is-past' : ($number === $current ? ' is-current' : ''));
        ?>
        <li class="lesson<?= $state ?>"<?= $number === $current ? ' aria-current="time"' : '' ?>>
            <span class="lesson__time"><?= e($period['starts_at']) ?><span><?= e($period['ends_at']) ?></span></span>
            <span>
                <span class="lesson__period">Ora <?= e($number) ?></span>
                <?php if ($lesson !== null): ?>
                    <span class="lesson__subject"><?= e($lesson['subject']) ?></span>
                    <?php if (!$compact && $lesson['meta'] !== []): ?>
                        <span class="lesson__meta"><?= e(implode(' · ', $lesson['meta'])) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="lesson__subject lesson__subject--free">Orë e lirë</span>
                <?php endif; ?>
            </span>
            <?php if ($number === $current): ?>
                <span class="badge badge--info">Tani</span>
            <?php endif; ?>
        </li>
        <?php if (!$hideFree && ($period['break_after'] ?? 0) >= 10): ?>
            <li class="lesson-break" aria-hidden="true">Pushimi i madh · <?= e($period['break_after']) ?> min</li>
        <?php endif; ?>
    <?php endforeach; ?>
</ol>
