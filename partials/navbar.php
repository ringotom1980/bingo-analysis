<?php
declare(strict_types=1);

/* Path: partials/navbar.php */

$currentPage = $currentPage ?? '';
$userName    = $userName ?? '使用者';
$userRole    = $userRole ?? 'USER';
?>
<header class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__brand">
            <div class="topbar__logo"></div>
            <div class="topbar__title">
                <?= htmlspecialchars($pageTitle ?? 'Bingo Analysis', ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="topbar__actions">
            <button type="button"
                    class="nav-btn"
                    data-nav-toggle
                    aria-expanded="false"
                    aria-label="開啟選單">
                ☰
            </button>
        </div>
    </div>
</header>

<nav class="nav-drawer" data-nav-drawer>
    <div class="container">
        <div class="nav-menu">
            <a href="<?= $baseUrl ?>/index.php">
                <span>主儀表板</span>
                <?= $currentPage === 'dashboard' ? '<span>●</span>' : '' ?>
            </a>

            <a href="<?= $baseUrl ?>/bingo.php">
                <span>賓果賓果分析</span>
                <?= $currentPage === 'bingo' ? '<span>●</span>' : '' ?>
            </a>

            <?php if ($userRole === 'ADMIN'): ?>
                <a href="<?= $baseUrl ?>/users.php">
                    <span>使用者管理</span>
                    <?= $currentPage === 'users' ? '<span>●</span>' : '' ?>
                </a>
            <?php endif; ?>

            <button type="button" id="btnLogout">
                <span>登出</span>
                <span>→</span>
            </button>
        </div>
    </div>
</nav>