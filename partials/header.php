<?php
declare(strict_types=1);

/* Path: partials/header.php */

if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Bingo Analysis');
}

$pageTitle = $pageTitle ?? APP_TITLE;
$baseUrl   = $baseUrl ?? '';
$assetTs   = $assetTs ?? time();
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/colors.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/core.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/typography.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/layout.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/navbar.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/cards.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tables.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/forms.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/balls.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/animations.css?v=<?= $assetTs ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css?v=<?= $assetTs ?>">
</head>
<body>
<script>
window.BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>