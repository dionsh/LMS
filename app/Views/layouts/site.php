<?php
/**
 * Public website layout. (Minimal until the design system is built in T03.)
 *
 * @var string      $content  rendered view HTML
 * @var string|null $title    page title; null/empty = school name only
 */
$schoolName = setting('school_name', 'Gjimnazi “Kuvendi i Arbërit”');
$pageTitle = !empty($title) ? $title . ' · ' . $schoolName : $schoolName;
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
