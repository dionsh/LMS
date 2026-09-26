<?php
/**
 * @var array      $user
 * @var array|null $year
 * @var array      $users        active accounts per role
 * @var int        $classes
 * @var int        $neverSigned
 * @var list<array{0: string, 1: string, 2: bool}> $setup
 */

use App\Support\Format;

$done = count(array_filter($setup, static fn (array $step): bool => $step[2]));
?>
<header class="page-header">
    <div>
        <p class="kicker"><?= e(Format::ucfirst(sq_date(new DateTimeImmutable(), 'long'))) ?><?= $year ? ' · Viti shkollor ' . e($year['name']) : '' ?></p>
        <h1 class="page-header__title"><?= e(Format::greeting()) ?>, <?= e($user['first_name']) ?>.</h1>
        <p class="lead">Paneli i administratës.</p>
    </div>
</header>

<div class="grid grid--stats">
    <div class="stat"><span class="stat__label">Nxënës aktivë</span><span class="stat__value"><?= e(sq_number($users['student'])) ?></span></div>
    <div class="stat"><span class="stat__label">Mësimdhënës</span><span class="stat__value"><?= e(sq_number($users['teacher'])) ?></span></div>
    <div class="stat"><span class="stat__label">Klasa</span><span class="stat__value"><?= e(sq_number($classes)) ?></span></div>
    <div class="stat">
        <span class="stat__label">Pa hyrë asnjëherë</span>
        <span class="stat__value"><?= e(sq_number($neverSigned)) ?></span>
        <span class="stat__meta"><?= $withoutCredentials > 0 ? 'Prej tyre ' . e(sq_number($withoutCredentials)) . ' pa fletë hyrjeje' : 'Fleta e hyrjes ende e papërdorur' ?></span>
    </div>
</div>

<section class="card portal-section" aria-labelledby="setup-title">
    <header class="card__head">
        <h2 class="card__title" id="setup-title">Përgatitja e shkollës</h2>
        <span class="meta num"><?= e($done) ?> nga <?= e(count($setup)) ?> hapa</span>
    </header>
    <progress class="progress" value="<?= e($done) ?>" max="<?= e(count($setup)) ?>"><?= e($done) ?> nga <?= e(count($setup)) ?></progress>
    <ul class="item-list setup-list" role="list">
        <?php foreach ($setup as [$label, $detail, $complete]): ?>
            <li>
                <div class="cluster">
                    <span class="setup-list__icon<?= $complete ? ' is-done' : '' ?>"><?= icon($complete ? 'check-circle' : 'clock') ?></span>
                    <span class="item__title"><?= e($label) ?></span>
                </div>
                <span class="badge badge--<?= $complete ? 'success' : 'plain' ?>"><?= e($detail) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
