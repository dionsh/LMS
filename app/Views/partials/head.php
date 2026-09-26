<?php
/**
 * Shared <head> for every layout.
 *
 * @var string|null $title        page title (null/empty = school name only)
 * @var string|null $description  meta description
 * @var list<string> $styles      stylesheets after tokens/base/components, e.g. ['site']
 * @var list<string> $scripts     extra scripts after app.js, e.g. ['styleguide']
 */
$schoolName = setting('school_name', 'Gjimnazi “Kuvendi i Arbërit”');
$pageTitle = !empty($title) ? $title . ' · ' . $schoolName : $schoolName;
$description = $description ?? setting('school_tagline', 'Faqja e shkollës dhe portali mësimor i ' . $schoolName . '.');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="theme-color" content="#0D2530">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="alternate icon" href="<?= e(asset('img/favicon.png')) ?>" type="image/png">
<link rel="preload" href="<?= e(url('assets/fonts/manrope-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('assets/fonts/newsreader-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php foreach (array_merge(['tokens', 'base', 'components'], $styles ?? []) as $sheet): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . $sheet . '.css')) ?>">
<?php endforeach; ?>
<?php foreach (array_merge(['app'], $scripts ?? []) as $script): ?>
<script src="<?= e(asset('js/' . $script . '.js')) ?>" defer></script>
<?php endforeach; ?>
