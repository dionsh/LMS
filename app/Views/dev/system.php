<?php
/**
 * @var list<array{0: string, 1: string, 2: bool}> $checks
 * @var string|null $status
 */
$failed = count(array_filter($checks, static fn (array $check): bool => !$check[2]));
?>
<main class="shell">
    <p class="kicker">Vetëm për zhvillim</p>
    <h1>Gjendja e sistemit</h1>
    <p class="lead">
        <?= $failed === 0
            ? 'Të gjitha kontrollet kaluan.'
            : e($failed) . ' kontroll(e) kërkojnë vëmendje.' ?>
    </p>

    <?php if ($status): ?>
        <p class="notice"><?= e($status) ?></p>
    <?php endif; ?>

    <table class="checks">
        <thead>
            <tr><th>Kontrolli</th><th>Vlera</th><th>Gjendja</th></tr>
        </thead>
        <tbody>
        <?php foreach ($checks as [$label, $value, $ok]): ?>
            <tr>
                <td><?= e($label) ?></td>
                <td><code><?= e($value) ?></code></td>
                <td class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? 'Në rregull' : 'Problem' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Mbrojtja CSRF</h2>
    <div class="actions">
        <form method="post" action="<?= e(route('dev.csrf')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="button">Dërgo me token</button>
        </form>
        <form method="post" action="<?= e(route('dev.csrf')) ?>">
            <button type="submit" class="button button--quiet">Dërgo pa token (duhet të refuzohet)</button>
        </form>
    </div>
</main>
