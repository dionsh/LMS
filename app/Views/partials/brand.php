<?php
/**
 * Logo lockup: the SVG mark + the wordmark set in type.
 *
 * @var string|null $variant 'light' on dark backgrounds
 * @var string|null $href    link target (default: Ballina)
 */
$variant = $variant ?? null;
$href = $href ?? url('/');
?>
<a class="brand<?= $variant === 'light' ? ' brand--light' : '' ?>" href="<?= e($href) ?>">
    <img class="brand__mark" src="<?= e(asset('img/brand/mark.svg')) ?>" alt="" width="42" height="42">
    <span class="brand__text">
        <span class="brand__kicker">Gjimnazi</span>
        <span class="brand__name"><?= e(setting('school_short_name', 'Kuvendi i Arbërit')) ?></span>
    </span>
</a>
