<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
$page_title = $page_title ?? 'RockBottom — Cute Things From The Depths';
$page_description = $page_description ?? 'Discover rare finds, mysterious characters, and collectibles waiting to be yours.';
$body_class = $body_class ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_description) ?>">
<meta name="theme-color" content="#30202D">
<script src="<?= e(asset('js/theme.js')) ?>"></script>
<meta property="og:site_name" content="RockBottom">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_description) ?>">
<meta property="og:type" content="website">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('images/logo/favicon.png')) ?>">
<link rel="preload" href="<?= e(asset('fonts/patrick-hand.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/bungee.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/kalam-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/kalam-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php require __DIR__ . '/funshop-fonts.php'; ?>
<link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/doodle.css')) ?>">
<?php
$rb_missing_fonts = $GLOBALS['rb_funshop_font_report']['missing'] ?? [];
$rb_fallback_classes = [];
if (in_array('Quirkie', $rb_missing_fonts, true)) {
    $rb_fallback_classes[] = 'has-fallback-display';
}
if (in_array('Goops', $rb_missing_fonts, true)) {
    $rb_fallback_classes[] = 'has-fallback-product';
}
$rb_body_classes = trim($body_class . ' ' . implode(' ', $rb_fallback_classes));
?>
</head>
<body class="<?= e($rb_body_classes) ?>">
<a class="skip-link" href="#main">Skip to content</a>
