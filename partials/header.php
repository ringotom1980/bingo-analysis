<?php
declare(strict_types=1);

if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Bingo Analysis');
}

$pageTitle = $pageTitle ?? APP_TITLE;
$baseUrl   = $baseUrl ?? '';
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/colors.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/core.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/typography.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/cards.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/forms.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/balls.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css">
</head>
<body>
<script>
window.BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>