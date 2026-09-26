<?php
/** Ballina — a placeholder until the public website is built (T17). */
?>
<section class="hero">
    <div class="hero__media">
        <img src="<?= e(asset('img/school.jpg')) ?>" alt="Ndërtesa e shkollës" width="640" height="480">
    </div>
    <div class="hero__lines motif" aria-hidden="true"></div>
    <div class="container hero__content">
        <p class="kicker kicker--on-ink">Së shpejti</p>
        <h1 class="hero__title"><?= e(setting('school_name')) ?></h1>
        <p class="hero__lead">Faqja e re e shkollës dhe platforma mësimore janë në ndërtim.</p>
        <div class="cluster">
            <a class="btn btn--primary btn--lg" href="<?= e(url('/hyr')) ?>">Hyr në portal <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>
