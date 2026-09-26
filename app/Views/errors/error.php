<?php
/**
 * Friendly error page for every HTTP error status.
 *
 * @var int $status
 */

use App\Support\Labels;

[$heading, $message] = Labels::httpError($status);
?>
<main class="shell">
    <p class="error-code"><?= e($status) ?></p>
    <h1><?= e($heading) ?></h1>
    <hr class="rule">
    <p class="lead"><?= e($message) ?></p>
    <p><a href="<?= e(url('/')) ?>">Kthehu në Ballinë</a></p>
</main>
