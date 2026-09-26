<?php
/**
 * Public website header: utility bar, masthead with navigation, and the
 * full-screen menu used on phones and tablets.
 */

use App\Core\Auth;
use App\Models\AcademicYear;
use App\Support\Navigation;

$navigation = Navigation::site();
$signedIn = Auth::check();
$portalUrl = url($signedIn ? Auth::homePath() : '/hyr');
$portalLabel = $signedIn ? 'Paneli im' : 'Hyr';
$path = current_path();
$phone = setting('school_phone');
$email = setting('school_email');
$year = AcademicYear::current();
?>
<div class="utility-bar">
    <div class="container utility-bar__inner">
        <div class="utility-bar__contact">
            <?php if ($phone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= icon('phone', 'icon--sm') ?><?= e($phone) ?></a>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <a href="mailto:<?= e($email) ?>"><?= icon('mail', 'icon--sm') ?><?= e($email) ?></a>
            <?php endif; ?>
            <?php if ($phone === '' && $email === '' && $year !== null): ?>
                <span>Viti shkollor <?= e($year['name']) ?></span>
            <?php endif; ?>
        </div>
        <a class="utility-bar__portal" href="<?= e($portalUrl) ?>">Portali mësimor <?= icon('arrow-right', 'icon--sm') ?></a>
    </div>
</div>

<header class="masthead">
    <div class="container masthead__inner">
        <?= partial('partials/brand') ?>

        <nav class="masthead__nav" aria-label="Navigimi kryesor">
            <ul class="site-nav" role="list">
                <?php foreach ($navigation as $item): ?>
                    <li>
                        <a href="<?= e(url($item['path'])) ?>"<?= Navigation::isActive($item, $path) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="masthead__actions">
            <a class="btn btn--secondary btn--sm" href="<?= e($portalUrl) ?>"><?= icon($signedIn ? 'dashboard' : 'user', 'icon--sm') ?><?= e($portalLabel) ?></a>
            <button class="menu-toggle" type="button" data-toggle="site-menu" aria-controls="site-menu" aria-expanded="false">
                <?= icon('menu') ?>Menyja
            </button>
        </div>
    </div>
</header>

<div class="site-menu" id="site-menu" role="dialog" aria-modal="true" aria-label="Menyja">
    <div class="site-menu__top">
        <?= partial('partials/brand', ['variant' => 'light']) ?>
        <button class="icon-btn site-menu__close" type="button" data-close="site-menu" aria-label="Mbyll menynë"><?= icon('close') ?></button>
    </div>

    <ul class="site-menu__links" role="list">
        <?php foreach ($navigation as $index => $item): ?>
            <li>
                <a href="<?= e(url($item['path'])) ?>"<?= Navigation::isActive($item, $path) ? ' aria-current="page"' : '' ?>>
                    <span><?= e(sprintf('%02d', $index + 1)) ?></span><?= e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="site-menu__footer">
        <a class="btn btn--on-ink btn--block" href="<?= e($portalUrl) ?>"><?= icon($signedIn ? 'dashboard' : 'user', 'icon--sm') ?><?= $signedIn ? 'Paneli im' : 'Hyr në portal' ?></a>
        <?php if ($phone !== ''): ?><span><?= e($phone) ?></span><?php endif; ?>
        <?php if ($email !== ''): ?><span><?= e($email) ?></span><?php endif; ?>
    </div>
</div>
