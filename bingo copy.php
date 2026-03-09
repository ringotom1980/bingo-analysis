<?php

declare(strict_types=1);

/*
 * Path: bingo.php
 * 說明：賓果賓果分析頁，包含最新一期、熱冷號統計、未出現期數、最佳組合與歷史開獎。
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
            <div class="dashboard-grid bingo-grid">

                <!-- 最新一期 -->
                <article class="card bingo-card bingo-card--latest">
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

                <!-- 熱號 / 冷號 / 統計 -->
                <article class="card bingo-card bingo-card--analysis">
                    <div class="card__head">
                        <div class="bingo-head-row">
                            <h2 class="typ-h2 mb-0">熱號 / 冷號分析</h2>

                            <div class="filter-row" id="analysisRangeButtons">
                                <button class="btn btn--primary filter-btn is-active" data-range="10" type="button">最近10期</button>
                                <button class="btn btn--secondary filter-btn" data-range="30" type="button">最近30期</button>
                                <button class="btn btn--secondary filter-btn" data-range="50" type="button">最近50期</button>
                                <button class="btn btn--secondary filter-btn" data-range="100" type="button">最近100期</button>
                            </div>
                        </div>
                    </div>

                    <div class="card__body stack-4">
                        <div class="bingo-stats-grid">
                            <div class="bingo-stat-box">
                                <div class="typ-small">單號累計次數</div>
                                <div class="typ-h3" id="statOddCount">--</div>
                            </div>

                            <div class="bingo-stat-box">
                                <div class="typ-small">雙號累計次數</div>
                                <div class="typ-h3" id="statEvenCount">--</div>
                            </div>

                            <div class="bingo-stat-box">
                                <div class="typ-small">1-40 累計次數</div>
                                <div class="typ-h3" id="statLowCount">--</div>
                            </div>

                            <div class="bingo-stat-box">
                                <div class="typ-small">41-80 累計次數</div>
                                <div class="typ-h3" id="statHighCount">--</div>
                            </div>
                        </div>

                        <div class="bingo-dual-grid">
                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">熱號 TOP10</h3>
                                    <span class="typ-small">查詢區間內累計最多</span>
                                </div>
                                <div class="balls-wrap" id="hotList"></div>
                            </section>

                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">冷號 TOP10</h3>
                                    <span class="typ-small">查詢區間內累計最少</span>
                                </div>
                                <div class="balls-wrap" id="coldList"></div>
                            </section>
                        </div>
                    </div>
                </article>

                <!-- 未出現期數 -->
                <article class="card bingo-card bingo-card--miss">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">未出現期數 TOP10</h2>
                    </div>

                    <div class="card__body">
                        <div class="balls-wrap" id="missList"></div>
                    </div>
                </article>

                <!-- 最佳組合 -->
                <article class="card bingo-card bingo-card--combo">
                    <div class="card__head">
                        <div class="bingo-head-row">
                            <h2 class="typ-h2 mb-0">最佳組合</h2>

                            <div class="bingo-combo-controls">
                                <select class="select bingo-select" id="comboStarSelect">
                                    <option value="1">1星</option>
                                    <option value="2">2星</option>
                                    <option value="3">3星</option>
                                    <option value="4">4星</option>
                                    <option value="5" selected>5星</option>
                                    <option value="6">6星</option>
                                    <option value="7">7星</option>
                                    <option value="8">8星</option>
                                    <option value="9">9星</option>
                                    <option value="10">10星</option>
                                </select>

                                <select class="select bingo-select" id="comboHourSelect">
                                    <option value="1">近1小時</option>
                                    <option value="2">近2小時</option>
                                    <option value="3" selected>近3小時</option>
                                    <option value="4">近4小時</option>
                                    <option value="5">近5小時</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card__body stack-4">
                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">號碼盤</h3>
                                <span class="typ-small">可直接點擊彩球調整最終組合</span>
                            </div>
                            <div class="balls-wrap bingo-ball-board" id="comboBallBoard"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">最終號碼</h3>
                                <span class="typ-small">依星數限制顯示目前組合</span>
                            </div>
                            <div class="balls-wrap" id="comboSelectedBalls"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">命中統計</h3>
                                <span class="typ-small">回朔查詢期間內各期命中星數統計</span>
                            </div>
                            <div class="bingo-hit-stats" id="comboHitStats"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">回朔結果</h3>
                                <span class="typ-small">僅顯示期數與命中星數</span>
                            </div>
                            <div class="bingo-trace-list" id="comboTraceList"></div>
                        </section>
                    </div>
                </article>

                <!-- 歷史開獎 -->
                <article class="card bingo-card bingo-card--history">
                    <div class="card__head">
                        <div class="bingo-head-row">
                            <h2 class="typ-h2 mb-0">歷史開獎</h2>

                            <div class="bingo-history-filters">
                                <input
                                    type="text"
                                    class="input bingo-history-input"
                                    id="historyStartTerm"
                                    inputmode="numeric"
                                    placeholder="起始期數，例如 115013542">

                                <input
                                    type="text"
                                    class="input bingo-history-input"
                                    id="historyEndTerm"
                                    inputmode="numeric"
                                    placeholder="結束期數，例如 115013550">

                                <button class="btn btn--primary" id="btnHistorySearch" type="button">查詢</button>
                                <button class="btn btn--secondary" id="btnHistoryReset" type="button">近10期</button>
                            </div>
                        </div>
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