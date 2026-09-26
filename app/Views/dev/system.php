<?php
/**
 * @var list<array{0: string, 1: string, 2: bool}> $checks
 * @var string|null $status
 */
$failed = count(array_filter($checks, static fn (array $check): bool => !$check[2]));
?>
<section class="section">
    <div class="container stack--lg">
        <header class="page-header">
            <div>
                <p class="kicker">Vetëm për zhvillim</p>
                <h1 class="page-header__title">Gjendja e sistemit</h1>
                <p class="lead">
                    <?= $failed === 0 ? 'Të gjitha kontrollet kaluan.' : e($failed) . ' kontroll(e) kërkojnë vëmendje.' ?>
                </p>
            </div>
            <a class="btn btn--secondary" href="<?= e(route('dev.styleguide')) ?>">Sistemi i dizajnit <?= icon('arrow-right') ?></a>
        </header>

        <?php if ($status): ?>
            <div class="alert alert--success" role="status"><?= icon('check-circle') ?><p class="alert__body"><?= e($status) ?></p></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="table table--stack">
                <thead>
                    <tr><th scope="col">Kontrolli</th><th scope="col">Vlera</th><th scope="col">Gjendja</th></tr>
                </thead>
                <tbody>
                <?php foreach ($checks as [$label, $value, $ok]): ?>
                    <tr>
                        <td data-label="Kontrolli"><span class="table__primary"><?= e($label) ?></span></td>
                        <td data-label="Vlera"><code><?= e($value) ?></code></td>
                        <td data-label="Gjendja">
                            <span class="badge badge--<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'Në rregull' : 'Problem' ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div>
            <h2 class="section-head__title">Mbrojtja CSRF</h2>
            <div class="cluster">
                <form method="post" action="<?= e(route('dev.csrf')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary">Dërgo me token</button>
                </form>
                <form method="post" action="<?= e(route('dev.csrf')) ?>">
                    <button type="submit" class="btn btn--secondary">Dërgo pa token (duhet të refuzohet)</button>
                </form>
            </div>
        </div>
    </div>
</section>
