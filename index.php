<?php

declare(strict_types=1);

/*
 * Path: index.php
 * 說明：主儀表板（Professional Dashboard UI）
 */

require_once __DIR__ . '/services/auth_service.php';
auth_require_login();

$user = auth_user();

$pageTitle   = '主儀表板';
$currentPage = 'dashboard';
$baseUrl     = '';
$assetTs     = time();
$pageJs      = 'dashboard.js';

$userName = $user['display_name'] ?? '';
$userRole = $user['role'] ?? 'USER';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/navbar.php';
?>

<main class="page-shell">
    <div class="container">

        <section class="section">

            <div class="dashboard-grid">

                <!-- 使用者資訊 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">登入資訊</h2>
                    </div>

                    <div class="card__body">

                        <p class="typ-body">
                            歡迎，
                            <strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong>
                        </p>

                        <div class="dashboard-meta">

                            <span class="badge">
                                <?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span
                                class="typ-small"
                                id="lastLoginText"
                                data-last-login="<?= htmlspecialchars((string)($user['last_login_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                最後登入時間：--
                            </span>

                        </div>

                    </div>

                </article>



                <!-- 賓果賓果 -->
                <article class="card lottery-card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">賓果賓果</h2>
                    </div>

                    <div class="card__body">

                        <div class="dashboard-kpi">

                            <div>
                                <div class="typ-small">最新期數</div>
                                <div class="typ-h3" id="bingoIssue">--</div>
                            </div>

                            <div>
                                <div class="typ-small">開獎時間</div>
                                <div class="typ-body" id="bingoTime">--</div>
                            </div>

                        </div>

                        <div class="balls-wrap lottery-balls" id="bingoBalls">
                            <span class="typ-small">載入中...</span>
                        </div>

                    </div>

                    <div class="card__foot">

                        <a class="btn btn-primary" href="<?= $baseUrl ?>/bingo.php">
                            進入分析
                        </a>

                    </div>

                </article>



                <!-- 威力彩 -->
                <article class="card lottery-card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">威力彩</h2>
                    </div>

                    <div class="card__body">

                        <div class="typ-small">
                            資料來源尚未建置
                        </div>

                    </div>

                    <div class="card__foot">

                        <button class="btn btn-secondary" disabled>
                            即將推出
                        </button>

                    </div>

                </article>



                <!-- 大樂透 -->
                <article class="card lottery-card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">大樂透</h2>
                    </div>

                    <div class="card__body">

                        <div class="typ-small">
                            資料來源尚未建置
                        </div>

                    </div>

                    <div class="card__foot">

                        <button class="btn btn-secondary" disabled>
                            即將推出
                        </button>

                    </div>

                </article>



                <!-- 系統狀態 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">系統狀態</h2>
                    </div>

                    <div class="card__body">

                        <div class="dashboard-stat-list">

                            <div class="dashboard-stat-item">
                                <span class="typ-small">今日收錄期數</span>
                                <strong id="todayCount">--</strong>
                            </div>

                            <div class="dashboard-stat-item">
                                <span class="typ-small">最新更新</span>
                                <strong id="latestUpdatedAt">--</strong>
                            </div>

                            <div class="dashboard-stat-item">
                                <span class="typ-small">資料狀態</span>
                                <strong id="dataStatus">--</strong>
                            </div>

                        </div>

                    </div>

                </article>


            </div>

        </section>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>