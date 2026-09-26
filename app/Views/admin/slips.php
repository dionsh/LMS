<?php
/**
 * Printable login slips (8 per A4 page, with cut lines).
 *
 * @var string $batch
 * @var array  $slips   ['title', 'slips' => [[name, username, detail, password]], 'return_to', 'created_at']
 * @var string $portal  full sign-in address printed on each slip
 */

use App\Services\SlipStore;

$count = count($slips['slips']);
$minutesLeft = max(1, (int) ceil((SlipStore::LIFETIME - (time() - $slips['created_at'])) / 60));
?>
<header class="page-header no-print">
    <div>
        <p class="kicker">Fletët e hyrjes</p>
        <h1 class="page-header__title"><?= e($slips['title']) ?></h1>
        <p class="lead"><?= e($count) ?> fletë · 8 në një faqe A4</p>
    </div>
    <div class="cluster">
        <button class="btn btn--primary" type="button" data-print><?= icon('file') ?>Printo</button>
        <form method="post" action="<?= e(url('/admin/fletet-e-hyrjes/' . $batch . '/mbaro')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn--secondary" type="submit">Mbaro</button>
        </form>
    </div>
</header>

<div class="alert alert--warning admin-note no-print" role="note">
    <?= icon('lock') ?>
    <p class="alert__body">
        <strong class="alert__title">Printojini tani dhe shpërndajini personalisht.</strong>
        Fjalëkalimet e përkohshme nuk ruhen askund: kjo faqe zhduket pas <?= e($minutesLeft) ?> <?= $minutesLeft === 1 ? 'minute' : 'minutash' ?> ose kur shtypni “Mbaro”.
        Për të printuar sërish më vonë duhet të lëshoni fletë të reja.
    </p>
</div>

<div class="slips">
    <?php foreach ($slips['slips'] as $slip): ?>
        <article class="slip">
            <header class="slip__head">
                <img class="slip__mark" src="<?= e(asset('img/brand/mark.svg')) ?>" alt="" width="28" height="28">
                <span class="slip__school"><?= e(setting('school_name')) ?></span>
                <span class="slip__kind">Fleta e hyrjes</span>
            </header>
            <p class="slip__name"><?= e($slip['name']) ?></p>
            <p class="slip__detail"><?= e($slip['detail']) ?></p>
            <dl class="slip__credentials">
                <div>
                    <dt>Emri i përdoruesit</dt>
                    <dd class="slip__value slip__username"><?= e($slip['username']) ?></dd>
                </div>
                <div>
                    <dt>Fjalëkalimi i përkohshëm</dt>
                    <dd class="slip__value slip__password"><?= e($slip['password']) ?></dd>
                </div>
            </dl>
            <p class="slip__how">
                Hyni te <strong><?= e($portal) ?></strong>. Në hyrjen e parë do t’ju kërkohet të zgjidhni fjalëkalimin tuaj.
                Mos ia tregoni askujt këtë fletë.
            </p>
        </article>
    <?php endforeach; ?>
</div>
