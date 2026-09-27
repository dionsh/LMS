<?php
/**
 * @var list<array> $rooms   Room::overview()
 * @var array<int, list<string>> $homeOf  room id => classes that call it home (this year)
 * @var array $values @var array $errors  the add form
 */
?>
<header class="page-header">
    <div>
        <p class="kicker">Shkolla</p>
        <h1 class="page-header__title">Sallat</h1>
        <p class="lead">Çdo klasë ka sallën e vet, ku mësimdhënësit vijnë për mësim. Salla të tjera (palestra, laboratorët) shënohen në orar vetëm për orët që mbahen aty.</p>
    </div>
</header>

<section class="card admin-note" aria-labelledby="add-room-title">
    <h2 class="card__title" id="add-room-title">Shto sallë</h2>
    <form class="inline-form inline-form--room" method="post" action="<?= e(url('/admin/sallat/shto')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label class="field__label" for="name">Emri<span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="60" placeholder="P.sh. Salla 12" autocomplete="off"<?= field_invalid($errors, 'name') ?>>
            <?= field_error($errors, 'name') ?>
        </div>
        <div class="field">
            <label class="field__label" for="capacity">Kapaciteti</label>
            <input class="input input--short" id="capacity" name="capacity" value="<?= e($values['capacity']) ?>" inputmode="numeric" maxlength="3"<?= field_invalid($errors, 'capacity') ?>>
            <?= field_error($errors, 'capacity') ?>
        </div>
        <button class="btn btn--secondary" type="submit"><?= icon('plus') ?>Shto</button>
    </form>
</section>

<?php if ($rooms === []): ?>
    <div class="empty">
        <div class="empty__mark motif" aria-hidden="true"></div>
        <h2 class="empty__title">Ende nuk ka salla.</h2>
        <p class="empty__text">Pa salla, orari tregon vetëm lëndën dhe mësimdhënësin. Shtojini kur t’i keni gati.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--stack">
            <thead>
                <tr>
                    <th scope="col">Salla</th>
                    <th scope="col">Salla e klasës</th>
                    <th scope="col" class="num">Kapaciteti</th>
                    <th scope="col" class="num">Orë në orar</th>
                    <th scope="col">Gjendja</th>
                    <th scope="col"><span class="visually-hidden">Veprime</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td data-label="Salla"><a class="table__primary table__link" href="<?= e(url('/admin/sallat/' . $room['id'] . '/ndrysho')) ?>"><?= e($room['name']) ?></a></td>
                        <td data-label="Salla e klasës"><?= isset($homeOf[(int) $room['id']]) ? e(implode(', ', $homeOf[(int) $room['id']])) : '<span class="meta">—</span>' ?></td>
                        <td data-label="Kapaciteti" class="num"><?= $room['capacity'] !== null ? e($room['capacity']) : '—' ?></td>
                        <td data-label="Orë në orar" class="num"><?= e($room['lessons']) ?></td>
                        <td data-label="Gjendja"><?= (int) $room['is_active'] === 1 ? '<span class="badge badge--success">Aktive</span>' : '<span class="badge">Joaktive</span>' ?></td>
                        <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="<?= e(url('/admin/sallat/' . $room['id'] . '/ndrysho')) ?>">Ndrysho</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
