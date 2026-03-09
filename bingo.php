<?php

declare(strict_types=1);

/*
 * Path: bingo.php
 * 說明：賓果賓果分析頁 v2，包含最新開獎、系統統計分析、使用者自選組合與歷史開獎。
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

                <!-- 第1區：最新開獎 -->
                <article class="card bingo-card bingo-card--latest">
                    <div class="card__head">
                        <h2 class="typ-h2 mb-0">最新開獎</h2>
                    </div>

                    <div class="card__body stack-4">
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

                <!-- 第2區：系統統計分析 -->
                <article class="card bingo-card bingo-card--analysis">
                    <div class="card__head">
                        <div class="bingo-head-row">
                            <h2 class="typ-h2 mb-0">系統統計分析</h2>

                            <div class="bingo-analysis-controls">
                                <div class="bingo-control-group">
                                    <label class="typ-small" for="analysisRangeInput">分析期數</label>
                                    <input
                                        type="number"
                                        class="input bingo-analysis-input"
                                        id="analysisRangeInput"
                                        min="10"
                                        max="500"
                                        step="1"
                                        value="100">
                                </div>

                                <div class="bingo-control-group">
                                    <label class="typ-small" for="analysisStarSelect">推薦星數</label>
                                    <select class="select bingo-select" id="analysisStarSelect">
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card__body stack-4">

                        <div class="bingo-triple-grid">
                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">熱號 TOP10<span class="typ-small">（查詢區間內出現最多）</span></h3>
                                </div>
                                <div class="balls-wrap" id="hotList"></div>
                            </section>

                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">冷號 TOP10<span class="typ-small">（查詢區間內出現最少）</span></h3>
                                </div>
                                <div class="balls-wrap" id="coldList"></div>
                            </section>

                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">未出現期數 TOP10<span class="typ-small">（距今連續未出現最多）</span></h3>
                                </div>
                                <div class="balls-wrap" id="missList"></div>
                            </section>
                        </div>

                        <div class="bingo-dual-grid">
                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">連續出現期數 TOP10<span class="typ-small">（由最新期往前連續出現）</span></h3>
                                </div>
                                <div class="balls-wrap" id="streakList"></div>
                            </section>

                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">尾數分析<span class="typ-small">（尾數 0~9 統計）</span></h3>
                                </div>
                                <div class="bingo-tag-list" id="tailStatsList"></div>
                            </section>
                        </div>

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
                                <div class="typ-small">1~40 累計次數</div>
                                <div class="typ-h3" id="statSmallCount">--</div>
                            </div>

                            <div class="bingo-stat-box">
                                <div class="typ-small">41~80 累計次數</div>
                                <div class="typ-h3" id="statBigCount">--</div>
                            </div>
                        </div>

                        <div class="bingo-dual-grid">
                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">升溫號 TOP10<span class="typ-small">（近10期相對近30期更活躍）</span></h3>
                                </div>
                                <div class="balls-wrap" id="uptrendList"></div>
                            </section>

                            <section class="stack-3">
                                <div class="bingo-subhead">
                                    <h3 class="typ-h3 mb-0">降溫號 TOP10<span class="typ-small">（近10期相對近30期轉弱）</span></h3>
                                </div>
                                <div class="balls-wrap" id="downtrendList"></div>
                            </section>
                        </div>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">連號分析<span class="typ-small">（常見連號組合）</span></h3>
                            </div>
                            <div class="bingo-tag-list" id="pairStatsList"></div>
                        </section>

                        <section class="stack-3 bingo-recommend-block">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">系統推薦最佳組合<span class="typ-small" id="analysisRecommendTitle">（推薦 5 星）</span></h3>
                            </div>

                            <div class="balls-wrap" id="analysisRecommendedNumbers"></div>

                            <div class="bingo-reason-list" id="analysisRecommendedReasons"></div>

                            <div class="bingo-hit-stats" id="analysisHitSummary"></div>
                        </section>

                    </div>
                </article>

                <!-- 第3區：使用者自選組合 -->
                <article class="card bingo-card bingo-card--user">
                    <div class="card__head">
                        <div class="bingo-head-row">
                            <h2 class="typ-h2 mb-0">使用者自選組合</h2>

                            <div class="bingo-analysis-controls">
                                <div class="bingo-control-group">
                                    <label class="typ-small" for="userStarSelect">玩法星數</label>
                                    <select class="select bingo-select" id="userStarSelect">
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card__body stack-4">

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">球號選擇區<span class="typ-small">（可選 1~80，最多選 10 顆）</span></h3>
                            </div>
                            <div class="balls-wrap bingo-ball-board" id="userBallBoard"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">已選球號<span class="typ-small">（不會因每分鐘刷新被清空）</span></h3>
                            </div>
                            <div class="balls-wrap" id="userSelectedBalls"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">命中分析<span class="typ-small">（依目前已選球號比對最近開獎）</span></h3>
                            </div>
                            <div class="bingo-trace-list" id="userHitTrace"></div>
                            <div class="bingo-tool-row" id="userHitTraceActions"></div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">組合工具<span class="typ-small">（當選號數大於玩法星數時可展開）</span></h3>
                            </div>
                            <div class="bingo-tool-row">
                                <button class="btn btn--primary" id="btnBuildUserCombos" type="button">提供組合號碼</button>
                                <button class="btn btn--secondary" id="btnClearUserCombos" type="button">清除組合</button>
                            </div>
                        </section>

                        <section class="stack-3">
                            <div class="bingo-subhead">
                                <h3 class="typ-h3 mb-0">組合結果<span class="typ-small">（不會因每分鐘刷新被清空）</span></h3>
                            </div>
                            <div class="bingo-combo-result-list" id="userComboResults"></div>
                        </section>

                    </div>
                </article>

                <!-- 第4區：歷史開獎 -->
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