<?php
/** @var array $user */

use App\Support\Format;
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= e(Format::ucfirst(sq_date(new DateTimeImmutable(), 'long'))) ?></p>
        <h1 class="page-header__title"><?= e(Format::greeting()) ?>, <?= e($user['first_name']) ?>.</h1>
        <p class="lead">Portali juaj mësimor.</p>
    </div>
</header>

<div class="empty">
    <div class="empty__mark motif" aria-hidden="true"></div>
    <h2 class="empty__title">Paneli juaj po përgatitet</h2>
    <p class="empty__text">
        Së shpejti këtu do të shihni orën që keni tani, orarin e sotëm, detyrat në pritje,
        notat e fundit dhe njoftimet e shkollës.
    </p>
    <a class="btn btn--secondary" href="<?= e(url('/profili')) ?>"><?= icon('user') ?>Profili</a>
</div>
