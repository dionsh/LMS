<?php
/**
 * Edit any account; issue a login slip; activate or deactivate.
 *
 * @var array $account  User::details()
 * @var array $values @var array $errors @var array $classes @var array $subjects @var string $back @var bool $isSelf
 * @var list<array> $teaching  a teacher's class-subjects this year (ClassSubject::forTeacher())
 */

use App\Support\Format;
use App\Support\Labels;

$name = $account['first_name'] . ' ' . $account['last_name'];
$role = $account['role'];
$listName = match ($role) { 'student' => 'Nxënësit', 'teacher' => 'Mësimdhënësit', default => 'Llogaritë' };
$hasCredentials = (int) $account['has_credentials'] === 1;
$active = $account['status'] === 'active';
?>
<header class="page-header">
    <div>
        <nav class="breadcrumbs" aria-label="Gjurma">
            <ol><li><a href="<?= e(url($back)) ?>"><?= e($listName) ?></a></li><li aria-current="page"><?= e($name) ?></li></ol>
        </nav>
        <h1 class="page-header__title"><?= e(Format::personName($role === 'teacher' ? $account['title'] : null, $account['first_name'], $account['last_name'])) ?></h1>
        <p class="lead">
            <?= e(Labels::role($role)) ?>
            <?php if ($role === 'student' && $account['class_id'] !== null): ?>
                · Klasa <?= e(Format::classLabel((int) $account['class_grade'], (int) $account['class_section'])) ?>
            <?php endif; ?>
            <?php if ($role === 'teacher' && $account['homeroom_class_id'] !== null): ?>
                · Kujdestar i klasës <?= e(Format::classLabel((int) $account['homeroom_grade'], (int) $account['homeroom_section'])) ?>
            <?php endif; ?>
        </p>
    </div>
</header>

<div class="account-layout">
    <form class="card form account-form" method="post" action="<?= e(url('/admin/perdoruesit/' . $account['id'] . '/ndrysho')) ?>" novalidate>
        <?= csrf_field() ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert--danger" role="alert">
                <?= icon('alert') ?>
                <p class="alert__body">Formulari ka <?= e(count($errors)) ?> <?= count($errors) === 1 ? 'gabim' : 'gabime' ?>. Shikoni fushat e shënuara më poshtë.</p>
            </div>
        <?php endif; ?>
        <?= partial('admin/partials/account-fields', ['role' => $role, 'values' => $values, 'errors' => $errors, 'classes' => $classes, 'subjects' => $subjects, 'creating' => false]) ?>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Ruaj ndryshimet</button>
            <a class="btn btn--quiet" href="<?= e(url($back)) ?>">Kthehu te lista</a>
        </div>
    </form>

    <aside class="account-side">
        <section class="card" aria-labelledby="account-title">
            <header class="card__head">
                <h2 class="card__title" id="account-title">Llogaria</h2>
                <?= partial('admin/partials/account-state', ['person' => $account]) ?>
            </header>
            <dl class="details">
                <div><dt>Emri i përdoruesit</dt><dd><?= e($account['username']) ?></dd></div>
                <div><dt>Roli</dt><dd><?= e(Labels::role($role)) ?></dd></div>
                <div><dt>Hyrja e fundit</dt><dd><?= $account['last_login_at'] ? e(sq_date($account['last_login_at'], 'datetime')) : 'Asnjëherë' ?></dd></div>
                <div><dt>Shtuar më</dt><dd><?= e(sq_date($account['created_at'])) ?></dd></div>
                <?php if ($role === 'teacher'): ?>
                    <div><dt>Kujdestar i klasës</dt><dd><?= $account['homeroom_class_id'] !== null ? e(Format::classLabel((int) $account['homeroom_grade'], (int) $account['homeroom_section'])) : '—' ?></dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <?php if ($role === 'teacher'): ?>
            <?php $weekly = array_sum(array_map(static fn (array $t): int => (int) $t['hours'], $teaching)); ?>
            <section class="card" aria-labelledby="teaching-title">
                <header class="card__head">
                    <h2 class="card__title" id="teaching-title">Mësimi këtë vit</h2>
                    <span class="<?= $weekly > (int) $account['weekly_norm'] ? 'badge badge--warning' : 'meta num' ?>"><?= e($weekly) ?> nga <?= e($account['weekly_norm']) ?> orë në javë</span>
                </header>
                <?php if ($teaching === []): ?>
                    <p class="meta">Ende nuk i është caktuar asnjë lëndë në ndonjë klasë. Lëndët u caktohen mësimdhënësve te faqja e secilës klasë.</p>
                <?php else: ?>
                    <a class="link-arrow teaching-link" href="<?= e(url('/admin/orari/mesimdhenesi/' . $account['id'])) ?>">Orari javor <?= icon('arrow-right') ?></a>
                    <ul class="item-list" role="list">
                        <?php foreach ($teaching as $item): ?>
                            <li>
                                <div>
                                    <a class="item__title" href="<?= e(url('/admin/klasat/' . $item['class_id'])) ?>"><?= e(Format::classLabel((int) $item['grade_level'], (int) $item['section'])) ?> · <?= e($item['subject_name']) ?></a>
                                    <span class="item__meta"><?= $item['hours'] !== null ? e($item['hours']) . ' orë në javë' : 'Orët pa caktuar' ?> · <?= e(Labels::shift((int) $item['shift'], true)) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!$isSelf): ?>
            <section class="card" aria-labelledby="access-title">
                <h2 class="card__title" id="access-title">Hyrja në portal</h2>
                <p class="meta account-side__text">
                    <?php if (!$active): ?>
                        Llogaria është e çaktivizuar: nuk mund të hyjë në portal. Të dhënat ruhen të plota.
                    <?php elseif (!$hasCredentials): ?>
                        Ende nuk ka fletë hyrjeje, prandaj nuk mund të hyjë në portal.
                    <?php else: ?>
                        Një fletë e re e zëvendëson fjalëkalimin aktual. Në hyrjen e parë do t’i kërkohet të zgjedhë një fjalëkalim të ri.
                    <?php endif; ?>
                </p>

                <div class="stack--sm">
                    <?php if ($active): ?>
                        <form method="post" action="<?= e(url('/admin/perdoruesit/' . $account['id'] . '/fleta')) ?>"
                              <?php if ($hasCredentials): ?>
                              data-confirm="Të lëshohet një fletë e re hyrjeje?"
                              data-confirm-text="Fjalëkalimi aktual i <?= e($name) ?> nuk do të vlejë më."
                              data-confirm-button="Lësho fletën"
                              <?php endif; ?>>
                            <?= csrf_field() ?>
                            <button class="btn btn--secondary btn--block" type="submit"><?= icon('file') ?><?= $hasCredentials ? 'Lësho fletë të re hyrjeje' : 'Lësho fletën e hyrjes' ?></button>
                        </form>
                        <form method="post" action="<?= e(url('/admin/perdoruesit/' . $account['id'] . '/statusi')) ?>"
                              data-confirm="Të çaktivizohet llogaria?"
                              data-confirm-text="<?= e($name) ?> nuk do të mund të hyjë në portal derisa llogaria të aktivizohet përsëri. Asnjë e dhënë nuk fshihet."
                              data-confirm-button="Çaktivizo">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="inactive">
                            <button class="btn btn--quiet btn--block account-side__danger" type="submit"><?= icon('lock') ?>Çaktivizo llogarinë</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('/admin/perdoruesit/' . $account['id'] . '/statusi')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="active">
                            <button class="btn btn--primary btn--block" type="submit"><?= icon('check') ?>Aktivizo llogarinë</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="card">
                <h2 class="card__title">Kjo është llogaria juaj</h2>
                <p class="meta account-side__text">Fjalëkalimin tuaj e ndryshoni te <a href="<?= e(url('/profili')) ?>">Profili</a>.</p>
            </section>
        <?php endif; ?>
    </aside>
</div>
