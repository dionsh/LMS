<?php
/**
 * Messages flashed by the previous request:
 *   Session::flash('success', 'Detyra u dorëzua.');
 * Types: success, info, warning, error.
 */

use App\Core\Session;

$types = [
    'success' => ['success', 'check-circle', 'status'],
    'info'    => ['info', 'info', 'status'],
    'warning' => ['warning', 'warning', 'status'],
    'error'   => ['danger', 'alert', 'alert'],
];

$messages = [];
foreach ($types as $type => [$variant, $iconName, $role]) {
    $message = Session::flashed($type);
    if (is_string($message) && $message !== '') {
        $messages[] = [$variant, $iconName, $role, $message];
    }
}

if ($messages === []) {
    return;
}
?>
<div class="flash-stack">
    <?php foreach ($messages as [$variant, $iconName, $role, $message]): ?>
        <div class="alert alert--<?= e($variant) ?>" role="<?= e($role) ?>">
            <?= icon($iconName) ?>
            <p class="alert__body"><?= e($message) ?></p>
            <button type="button" class="icon-btn alert__dismiss" data-dismiss aria-label="Mbyll mesazhin"><?= icon('close', 'icon--sm') ?></button>
        </div>
    <?php endforeach; ?>
</div>
