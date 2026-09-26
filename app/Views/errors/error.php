<?php
/**
 * Friendly error page for every HTTP error status.
 *
 * @var int $status
 */

use App\Support\Labels;

[$heading, $message] = Labels::httpError($status);
?>
<section class="section error-page">
    <div class="container">
        <p class="error-page__code num" aria-hidden="true"><?= e($status) ?></p>
        <h1><?= e($heading) ?></h1>
        <hr class="rule">
        <p class="lead"><?= e($message) ?></p>
        <div class="cluster error-page__actions">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('arrow-left') ?>Kthehu në Ballinë</a>
        </div>
    </div>
    <div class="error-page__lines motif" aria-hidden="true"></div>
</section>
