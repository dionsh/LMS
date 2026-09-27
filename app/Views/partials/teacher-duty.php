<?php
/**
 * A teacher's daily duty days (kujdestaria e ditës).
 *
 * @var list<array{shift: int, day: int, post: string}> $duty  Duty::forTeacher()
 * @var int|null $today  ISO day, to mark today's duty
 */

use App\Support\Format;
use App\Support\Labels;

$today = $today ?? null;
?>
<ul class="duty-days" role="list">
    <?php foreach ($duty as $item): ?>
        <li>
            <strong><?= e(Format::ucfirst(Labels::day((int) $item['day']))) ?></strong>
            · <?= e($item['post']) ?> · <?= e(Labels::shift((int) $item['shift'], true)) ?>
            <?php if ((int) $item['day'] === $today): ?><span class="badge badge--info">Sot</span><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
