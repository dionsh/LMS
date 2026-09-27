<?php
/**
 * The bell schedule of both shifts.
 *
 * @var array<int, array{label: string, periods: array, used: int, classes: int, posted: ?array, errors: array}> $shifts
 */

use App\Support\Labels;
use App\Support\Timetable;
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url('/admin/orari')) ?>">Orari</a></li><li aria-current="page">Orët e mësimit</li></ol>
        </nav>
        <h1 class="page-header__title">Orët e mësimit</h1>
        <p class="lead">Kur fillon dhe mbaron secila orë, për të dy ndërrimet. Orari i klasave ruan vetëm numrin e orës, prandaj kohët e reja vlejnë menjëherë për të gjitha klasat.</p>
    </div>
</header>

<div class="bells-grid">
    <?php foreach ($shifts as $shift => $data): ?>
        <?php
        $posted = $data['posted'];
        $errors = $data['errors'];
        $count = count($data['periods']);
        $rows = max($count + 1, $posted !== null ? max(array_keys(($posted['starts'] ?? []) + ($posted['ends'] ?? [])) ?: [0]) : 0);
        $rows = min($rows, 12);
        $shiftName = Labels::shift($shift, true);
        ?>
        <section class="card" aria-labelledby="shift-<?= e($shift) ?>">
            <header class="card__head">
                <h2 class="card__title" id="shift-<?= e($shift) ?>"><?= e($data['label']) ?></h2>
                <span class="meta"><?= e($data['classes']) ?> klasa · <?= e($count) ?> orë në ditë</span>
            </header>

            <?php if ($errors !== []): ?>
                <div class="alert alert--danger admin-note" role="alert">
                    <?= icon('alert') ?>
                    <p class="alert__body">Orët nuk u ruajtën. Shikoni rreshtat e shënuar.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/admin/orari/oret/' . $shift)) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="table-wrap">
                    <table class="table bells-table">
                        <thead>
                            <tr>
                                <th scope="col">Ora</th>
                                <th scope="col">Fillon</th>
                                <th scope="col">Mbaron</th>
                                <th scope="col" class="num">Pushimi pas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($number = 1; $number <= $rows; $number++): ?>
                                <?php
                                $period = $data['periods'][$number] ?? null;
                                $start = $posted !== null ? ($posted['starts'][$number] ?? '') : ($period['starts_at'] ?? '');
                                $end = $posted !== null ? ($posted['ends'][$number] ?? '') : ($period['ends_at'] ?? '');
                                $error = 'period-' . $number;
                                ?>
                                <tr<?= $period === null ? ' class="bells-table__new"' : '' ?>>
                                    <th scope="row">
                                        Ora <?= e($number) ?>
                                        <?php if ($period === null): ?><span class="table__secondary">E re (opsionale)</span><?php endif; ?>
                                    </th>
                                    <td>
                                        <label class="visually-hidden" for="s<?= e($shift) ?>-start-<?= e($number) ?>">Ora <?= e($number) ?> fillon</label>
                                        <input class="input input--time" id="s<?= e($shift) ?>-start-<?= e($number) ?>" name="starts[<?= e($number) ?>]" value="<?= e($start) ?>" inputmode="numeric" maxlength="5" placeholder="00:00" autocomplete="off"<?= field_invalid($errors, $error) ?>>
                                    </td>
                                    <td>
                                        <label class="visually-hidden" for="s<?= e($shift) ?>-end-<?= e($number) ?>">Ora <?= e($number) ?> mbaron</label>
                                        <input class="input input--time" id="s<?= e($shift) ?>-end-<?= e($number) ?>" name="ends[<?= e($number) ?>]" value="<?= e($end) ?>" inputmode="numeric" maxlength="5" placeholder="00:00" autocomplete="off"<?= field_invalid($errors, $error) ?>>
                                        <?= field_error($errors, $error) ?>
                                    </td>
                                    <td class="num">
                                        <?php if ($posted === null && $period !== null && $period['break_after'] !== null): ?>
                                            <span class="<?= $period['break_after'] >= 10 ? 'bells-table__long' : 'meta' ?>"><?= e($period['break_after']) ?> min</span>
                                        <?php else: ?>
                                            <span class="meta">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <p class="field__hint bells-hint">
                    <?php $lengths = array_unique(array_map([Timetable::class, 'length'], $data['periods'])); ?>
                    <?php if (count($lengths) === 1): ?>Secila orë zgjat <?= e(reset($lengths)) ?> minuta. <?php endif; ?>
                    Kohët shkruhen si 08:00. Për të shtuar një orë, plotësoni rreshtin e ri; për të hequr orën e fundit, fshini kohët e saj.
                </p>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Ruaj orët e <?= e($shiftName === 'paradite' ? 'paradites' : 'pasdites') ?></button>
                </div>
            </form>
        </section>
    <?php endforeach; ?>
</div>
