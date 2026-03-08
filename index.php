<?php

declare(strict_types=1);

/* Path: index.php */

require_once __DIR__ . '/services/auth_service.php';
auth_require_login();

$user = auth_user();

$pageTitle   = '主儀表板';
$currentPage = 'dashboard';
$baseUrl     = '';
$assetTs     = time();
$pageJs      = 'dashboard.js';
$userName    = $user['display_name'] ?? '';
$userRole    = $user['role'] ?? 'USER';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/navbar.php';
?>

<main class="page-shell">
    <div class="container">
        <section class="section">
            <div class="dashboard-grid">
                <!-- 歡迎資訊卡 -->
                <article class="card">
                    <div class="card__head">
                        <h1 class="typ-h1 mb-0">主儀表板</h1>
                    </div>
                    <div class="card__body">
                        <p class="typ-body mb-8">
                            歡迎，<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <div class="dashboard-meta">
                            <span class="badge"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></span>
                            <span
                                class="typ-small"
                                id="lastLoginText"
                                data-last-login="<?= htmlspecialchars((string)($user['last_login_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">最後登入時間：--</span>
                        </div>
                    </div>
                </article>

                <!-- 最新一期卡 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">最新一期</h2>
                    </div>
                    <div class="card__body">
                        <div class="dashboard-kpi">
                            <div>
                                <div class="typ-small">期數</div>
                                <div class="typ-h3" id="latestIssueNo">--</div>
                            </div>
                            <div>
                                <div class="typ-small">開獎時間</div>
                                <div class="typ-body" id="latestDrawTime">--</div>
                            </div>
                        </div>

                        <div class="balls-wrap" id="latestBalls">
                            <span class="typ-small">載入中...</span>
                        </div>
                    </div>
                </article>

                <!-- 系統狀態卡 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">系統狀態</h2>
                    </div>
                    <div class="card__body">
                        <div class="dashboard-stat-list">
                            <div class="dashboard-stat-item">
                                <span class="typ-small">今日已收錄期數</span>
                                <strong id="todayCount">--</strong>
                            </div>
                            <div class="dashboard-stat-item">
                                <span class="typ-small">最新更新時間</span>
                                <strong id="latestUpdatedAt">--</strong>
                            </div>
                            <div class="dashboard-stat-item">
                                <span class="typ-small">資料狀態</span>
                                <strong id="dataStatus">--</strong>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- 快速分析摘要卡 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">快速分析摘要</h2>
                    </div>
                    <div class="card__body">
                        <div class="summary-grid">
                            <div>
                                <div class="typ-small mb-8">最近 10 期熱號 Top 5</div>
                                <div class="balls-wrap" id="hotTop5"></div>
                            </div>
                            <div>
                                <div class="typ-small mb-8">最近 10 期冷號 Top 5</div>
                                <div class="balls-wrap" id="coldTop5"></div>
                            </div>
                            <div>
                                <div class="typ-small mb-8">最高遺漏 Top 5</div>
                                <div class="balls-wrap" id="missTop5"></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- 模組入口卡 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">模組入口</h2>
                    </div>
                    <div class="card__body">
                        <div class="module-links">
                            <a class="btn btn-primary" href="<?= $baseUrl ?>/bingo.php">賓果賓果</a>
                            <button type="button" class="btn btn-secondary" disabled>威力彩（預留）</button>
                            <button type="button" class="btn btn-secondary" disabled>大樂透（預留）</button>
                        </div>
                    </div>
                </article>

                <!-- 最近更新區 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">最近更新</h2>
                    </div>
                    <div class="card__body">
                        <div class="history-list" id="recentHistory">
                            <div class="typ-small">載入中...</div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>