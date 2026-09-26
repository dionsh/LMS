<?php
/**
 * Portal layout for students, teachers and admins.
 *
 * @var string       $content
 * @var string|null  $title
 * @var array        $user     ['first_name', 'last_name', 'role'] — the signed-in user
 * @var string|null  $active   nav key to highlight (default: derived from the URL)
 * @var int|null     $unread   unread notifications
 * @var string|null  $context  short text in the top bar (default: today's date)
 */

use App\Core\Auth;
use App\Support\Format;
use App\Support\Labels;
use App\Support\Navigation;

$user = $user ?? Auth::user();
$role = $user['role'];
$fullName = $user['first_name'] . ' ' . $user['last_name'];
$initials = Format::initials($user['first_name'], $user['last_name']);
$sections = Navigation::portal($role);
$tabbar = Navigation::tabbar($role);
$path = current_path();
$active = $active ?? null;
$unread = (int) ($unread ?? 0);
$context = $context ?? Format::ucfirst(Format::date(new DateTimeImmutable(), 'long'));
?>
<!doctype html>
<html lang="sq">
<head>
<?= partial('partials/head', ['title' => $title ?? null, 'styles' => array_merge(['portal'], $styles ?? [])]) ?>
</head>
<body>
    <a class="skip-link" href="#main">Kalo te përmbajtja</a>

    <div class="portal<?= $tabbar !== [] ? ' portal--tabbar' : '' ?>">
        <aside class="sidebar" id="portal-sidebar" aria-label="Navigimi i portalit">
            <div class="sidebar__brand">
                <?= partial('partials/brand', ['variant' => 'light', 'href' => url($sections[0]['items'][0]['path'] ?? '/')]) ?>
                <button class="icon-btn sidebar__close" type="button" data-close="portal-sidebar" aria-label="Mbyll menynë"><?= icon('close') ?></button>
            </div>
            <p class="sidebar__role">Portali · <?= e(Labels::role($role)) ?></p>

            <nav class="sidebar__nav">
                <?php foreach ($sections as $section): ?>
                    <div class="sidebar__section">
                        <?php if ($section['heading'] !== null): ?>
                            <p class="sidebar__heading"><?= e($section['heading']) ?></p>
                        <?php endif; ?>
                        <ul class="sidebar__list" role="list">
                            <?php foreach ($section['items'] as $item): ?>
                                <li>
                                    <a class="sidebar__link" href="<?= e(url($item['path'])) ?>"<?= Navigation::isActive($item, $path, $active) ? ' aria-current="page"' : '' ?>>
                                        <?= icon($item['icon']) ?><?= e($item['label']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar__user">
                <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
                <span>
                    <span class="sidebar__user-name"><?= e($fullName) ?></span>
                    <span class="sidebar__user-meta"><?= e(Labels::role($role)) ?></span>
                </span>
                <form method="post" action="<?= e(url('/dil')) ?>">
                    <?= csrf_field() ?>
                    <button class="icon-btn sidebar__logout" type="submit" aria-label="Dil" title="Dil"><?= icon('logout') ?></button>
                </form>
            </div>
        </aside>
        <div class="scrim" data-scrim="portal-sidebar"></div>

        <div class="portal__main">
            <header class="topbar">
                <button class="icon-btn topbar__menu" type="button" data-toggle="portal-sidebar" aria-controls="portal-sidebar" aria-expanded="false" aria-label="Hap menynë"><?= icon('menu') ?></button>
                <a class="topbar__brand" href="<?= e(url($sections[0]['items'][0]['path'] ?? '/')) ?>">
                    <img class="brand__mark" src="<?= e(asset('img/brand/mark.svg')) ?>" alt="<?= e(setting('school_name', 'Ballina')) ?>" width="32" height="32">
                </a>
                <p class="topbar__context"><?= e($context) ?></p>

                <div class="topbar__actions">
                    <a class="icon-btn" href="<?= e(url('/lajmerimet')) ?>" aria-label="Lajmërimet<?= $unread > 0 ? ' (' . e($unread) . ' të palexuara)' : '' ?>">
                        <?= icon('bell') ?>
                        <?php if ($unread > 0): ?>
                            <span class="count-badge" aria-hidden="true"><?= e($unread > 9 ? '9+' : $unread) ?></span>
                        <?php endif; ?>
                    </a>

                    <details class="menu">
                        <summary class="topbar__user" aria-label="Llogaria: <?= e($fullName) ?>">
                            <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
                            <span class="topbar__user-name"><?= e($user['first_name']) ?></span>
                            <?= icon('chevron-down', 'icon--sm') ?>
                        </summary>
                        <div class="menu__panel">
                            <div class="menu__header">
                                <strong><?= e($fullName) ?></strong>
                                <span class="meta"><?= e(Labels::role($role)) ?></span>
                            </div>
                            <a class="menu__item" href="<?= e(url('/profili')) ?>"><?= icon('user') ?>Profili</a>
                            <a class="menu__item" href="<?= e(url('/')) ?>"><?= icon('home') ?>Faqja e shkollës</a>
                            <div class="menu__separator"></div>
                            <form method="post" action="<?= e(url('/dil')) ?>">
                                <?= csrf_field() ?>
                                <button class="menu__item" type="submit"><?= icon('logout') ?>Dil</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main id="main" class="portal__content" tabindex="-1">
                <?= partial('partials/flash') ?>
                <?= $content ?>
            </main>
        </div>

        <?php if ($tabbar !== []): ?>
            <nav class="tabbar" aria-label="Navigimi i shpejtë">
                <?php foreach ($tabbar as $item): ?>
                    <a class="tabbar__item" href="<?= e(url($item['path'])) ?>"<?= Navigation::isActive($item, $path, $active) ? ' aria-current="page"' : '' ?>>
                        <?= icon($item['icon']) ?><?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
                <button class="tabbar__item" type="button" data-toggle="portal-sidebar" aria-controls="portal-sidebar" aria-expanded="false">
                    <?= icon('more') ?>Më shumë
                </button>
            </nav>
        <?php endif; ?>
    </div>

    <?= partial('partials/confirm-dialog') ?>
</body>
</html>
