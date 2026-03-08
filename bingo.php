<?php

declare(strict_types=1);

/*
 * Path: bingo.php
 * 說明：賓果賓果完整分析頁
 */

require_once __DIR__ . '/services/auth_service.php';
auth_require_login();

$user = auth_user();

$pageTitle   = '賓果賓果分析';
$currentPage = 'bingo';
$baseUrl     = '';
$assetTs     = time();
$pageJs      = 'bingo.js';

$userName    = $user['display_name'] ?? '';
$userRole    = $user['role'] ?? 'USER';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/navbar.php';
?>

<main class="page-shell">
    <div class="container">

        <section class="section">

            <div class="dashboard-grid">

                <!-- 查詢控制 -->
                <article class="card">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">查詢條件</h2>
                    </div>

                    <div class="card__body">

                        <div class="filter-row">

                            <button class="btn btn-primary filter-btn" data-range="10">最近10期</button>
                            <button class="btn btn-secondary filter-btn" data-range="30">最近30期</button>
                            <button class="btn btn-secondary filter-btn" data-range="50">最近50期</button>
                            <button class="btn btn-secondary filter-btn" data-range="100">最近100期</button>

                        </div>

                    </div>
                </article>

                <!-- 最新一期 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">最新一期</h2>
                    </div>

                    <div class="card__body">

                        <div class="dashboard-kpi">

                            <div>
                                <div class="typ-small">期數</div>
                                <div class="typ-h3" id="latestIssue">--</div>
                            </div>

                            <div>
                                <div class="typ-small">開獎時間</div>
                                <div class="typ-body" id="latestTime">--</div>
                            </div>

                        </div>

                        <div class="balls-wrap" id="latestBalls"></div>

                    </div>
                </article>

                <!-- 熱號 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">熱號 TOP5</h2>
                    </div>

                    <div class="card__body">
                        <div id="hotList"></div>
                    </div>

                </article>

                <!-- 冷號 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">冷號 TOP5</h2>
                    </div>

                    <div class="card__body">
                        <div id="coldList"></div>
                    </div>

                </article>

                <!-- 未出現期數 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">未出現期數 TOP5</h2>
                    </div>

                    <div class="card__body">
                        <div id="missList"></div>
                    </div>

                </article>

                <!-- 指定號碼 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">指定號碼分析</h2>
                    </div>

                    <div class="card__body">

                        <div class="number-search">

                            <input
                                type="number"
                                min="1"
                                max="80"
                                class="input"
                                id="numberInput"
                                placeholder="輸入1~80" />

                            <button class="btn btn-primary" id="btnSearch">
                                分析
                            </button>

                        </div>

                        <div id="numberResult"></div>

                    </div>

                </article>

                <!-- 歷史列表 -->
                <article class="card">

                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">歷史開獎</h2>
                    </div>

                    <div class="card__body">

                        <div id="historyList"></div>

                    </div>

                </article>

            </div>

        </section>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>