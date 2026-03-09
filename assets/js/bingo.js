/*
 * Path: assets/js/bingo.js
 * 說明：賓果賓果分析頁 v2 控制，負責最新開獎、系統統計分析、使用者自選組合與歷史開獎。
 */

(function () {
    'use strict';

    var state = {
        analysisRange: 100,
        analysisStar: 5,
        analysisData: null,

        userStar: 5,
        userSelectedNumbers: [],
        userComboList: [],
        userHitExpanded: false,

        latestData: null,
        historyData: []
    };

    var analysisTimer = null;

    function $(id) {
        return document.getElementById(id);
    }

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function safeArray(val) {
        return Array.isArray(val) ? val : [];
    }

    function normalizeNumbers(list) {
        return safeArray(list)
            .map(function (n) { return parseInt(n, 10); })
            .filter(function (n) { return Number.isInteger(n) && n >= 1 && n <= 80; })
            .sort(function (a, b) { return a - b; });
    }

    function uniqueNumbers(list, max) {
        var seen = {};
        var out = [];

        normalizeNumbers(list).forEach(function (n) {
            if (!seen[n]) {
                seen[n] = true;
                out.push(n);
            }
        });

        if (typeof max === 'number') {
            return out.slice(0, max);
        }

        return out;
    }

    function ball(n, extraClass, sizeClass, attrs) {
        var cls = 'ball';
        var attrHtml = '';

        if (extraClass) cls += ' ' + extraClass;
        if (sizeClass) cls += ' ' + sizeClass;

        if (attrs && typeof attrs === 'object') {
            Object.keys(attrs).forEach(function (key) {
                attrHtml += ' ' + key + '="' + escapeHtml(attrs[key]) + '"';
            });
        }

        return '<span class="' + cls + '"' + attrHtml + '>' + escapeHtml(pad(n)) + '</span>';
    }

    function rankedBall(item, valueKey, ballClass, suffix) {
        var number = item && item.number != null ? parseInt(item.number, 10) : 0;
        var value = item && item[valueKey] != null ? item[valueKey] : '--';

        if (!number) return '';

        return (
            '<div class="ball-stat">' +
            ball(number, ballClass) +
            '<span class="typ-small">' + escapeHtml(value) + (suffix ? ' ' + escapeHtml(suffix) : '') + '</span>' +
            '</div>'
        );
    }

    function renderTagList(id, rows, formatter) {
        var el = $(id);
        if (!el) return;

        rows = safeArray(rows);

        if (!rows.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = rows.map(function (row) {
            return '<div class="bingo-tag-item">' + formatter(row) + '</div>';
        }).join('');
    }

    async function fetchApi(url) {
        var res = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }

        return await res.json();
    }

    function safeSetHtml(id, html) {
        var el = $(id);
        if (el) {
            el.innerHTML = html;
        }
    }

    function safeSetText(id, text) {
        var el = $(id);
        if (el) {
            el.textContent = text == null || text === '' ? '--' : String(text);
        }
    }

    function combination(arr, k) {
        var result = [];

        function helper(start, path) {
            if (path.length === k) {
                result.push(path.slice());
                return;
            }

            for (var i = start; i < arr.length; i++) {
                path.push(arr[i]);
                helper(i + 1, path);
                path.pop();
            }
        }

        if (k <= 0 || k > arr.length) {
            return [];
        }

        helper(0, []);
        return result;
    }

    /* 第1區：最新開獎 */
    async function loadLatest() {
        var json = await fetchApi('api/bingo_latest.php');
        var data = json.data || {};

        state.latestData = data;

        safeSetText('latestIssue', data.issue_no || data.draw_term || '--');
        safeSetText('latestTime', data.draw_time || '--');

        safeSetHtml(
            'latestBalls',
            safeArray(data.numbers).length
                ? safeArray(data.numbers).map(function (n) { return ball(n); }).join('')
                : '<span class="typ-small">無資料</span>'
        );
    }

    /* 第2區：系統統計分析 */
    async function loadAnalysis() {
        var url =
            'api/bingo_analysis.php?range=' + encodeURIComponent(state.analysisRange) +
            '&star=' + encodeURIComponent(state.analysisStar);

        var json = await fetchApi(url);
        var data = json.data || {};

        state.analysisData = data;

        renderAnalysis(data);
    }

    function renderAnalysis(data) {
        data = data || {};

        safeSetText('statOddCount', data.odd_even_stats && data.odd_even_stats.odd_count != null ? data.odd_even_stats.odd_count : '--');
        safeSetText('statEvenCount', data.odd_even_stats && data.odd_even_stats.even_count != null ? data.odd_even_stats.even_count : '--');
        safeSetText('statSmallCount', data.big_small_stats && data.big_small_stats.small_count != null ? data.big_small_stats.small_count : '--');
        safeSetText('statBigCount', data.big_small_stats && data.big_small_stats.big_count != null ? data.big_small_stats.big_count : '--');

        renderBallStatList('hotList', data.hot_top10 || [], 'hit_count', 'ball-hot', '次');
        renderBallStatList('coldList', data.cold_top10 || [], 'hit_count', 'ball-cold', '次');
        renderBallStatList('missList', data.miss_top10 || [], 'miss', 'ball-miss', '期');
        renderBallStatList('streakList', data.streak_top10 || [], 'streak', 'ball-selected', '期');
        renderBallOnlyList('uptrendList', data.uptrend_top10 || [], 'ball-hot');
        renderBallOnlyList('downtrendList', data.downtrend_top10 || [], 'ball-cold');

        renderTagList('pairStatsList', data.pair_stats || [], function (row) {
            return '<span class="typ-body">' + escapeHtml(row.pair || '--') + '</span>' +
                '<span class="typ-small"> ' + escapeHtml(row.count || 0) + ' 次</span>';
        });

        renderTagList('tailStatsList', data.tail_stats || [], function (row) {
            return '<span class="typ-body">尾 ' + escapeHtml(row.tail || 0) + '</span>' +
                '<span class="typ-small"> ' + escapeHtml(row.count || 0) + ' 次</span>';
        });

        safeSetText('analysisRecommendTitle', '推薦 ' + escapeHtml(data.star || state.analysisStar) + ' 星');

        safeSetHtml(
            'analysisRecommendedNumbers',
            safeArray(data.recommended_numbers).length
                ? safeArray(data.recommended_numbers).map(function (n) { return ball(n, 'ball-active'); }).join('')
                : '<span class="typ-small">無資料</span>'
        );

        renderRecommendedReasons(data.recommended_reasons || []);
        renderAnalysisHitSummary(data.hit_summary || {}, data.star || state.analysisStar);
    }

    function renderBallStatList(id, list, valueKey, ballClass, suffix) {
        var el = $(id);
        if (!el) return;

        list = safeArray(list);

        if (!list.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = list.map(function (item) {
            return rankedBall(item, valueKey, ballClass, suffix);
        }).join('');
    }

    function renderBallOnlyList(id, list, ballClass) {
        var el = $(id);
        if (!el) return;

        list = safeArray(list);

        if (!list.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = list.map(function (item) {
            var number = item && item.number != null ? parseInt(item.number, 10) : 0;
            if (!number) return '';

            return (
                '<div class="ball-stat">' +
                ball(number, ballClass) +
                '</div>'
            );
        }).join('');
    }

    function renderRecommendedReasons(list) {
        var el = $('analysisRecommendedReasons');
        if (!el) return;

        list = safeArray(list);

        if (!list.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = list.map(function (row) {
            return (
                '<div class="bingo-reason-item">' +
                '<div class="bingo-reason-ball">' + ball(row.number, 'ball-active') + '</div>' +
                '<div class="bingo-reason-text typ-small">' + escapeHtml(safeArray(row.reasons).join('、')) + '</div>' +
                '</div>'
            );
        }).join('');
    }

    function renderAnalysisHitSummary(summary, star) {
        var el = $('analysisHitSummary');
        if (!el) return;

        var html = [];
        var i;

        for (i = parseInt(star, 10) || 0; i >= 0; i--) {
            html.push(
                '<div class="bingo-hit-row">' +
                '<span class="typ-body">命中 ' + escapeHtml(i) + ' 星</span>' +
                '<strong>' + escapeHtml(summary[String(i)] || 0) + '</strong>' +
                '</div>'
            );
        }

        el.innerHTML = html.join('');
    }

    /* 第3區：使用者自選組合 */
    function renderUserBallBoard() {
        var el = $('userBallBoard');
        if (!el) return;

        var html = [];
        var i;

        for (i = 1; i <= 80; i++) {
            var isSelected = state.userSelectedNumbers.indexOf(i) !== -1;
            html.push(
                ball(
                    i,
                    isSelected ? 'ball-selected ball-sm' : 'ball-sm',
                    '',
                    {
                        'data-number': i,
                        'data-role': 'user-ball'
                    }
                )
            );
        }

        el.innerHTML = html.join('');
    }

    function renderUserSelectedBalls() {
        safeSetHtml(
            'userSelectedBalls',
            state.userSelectedNumbers.length
                ? state.userSelectedNumbers.map(function (n) { return ball(n, 'ball-selected'); }).join('')
                : '<span class="typ-small">尚未選取號碼</span>'
        );
    }

    function renderHitStars(hit) {
        hit = parseInt(hit, 10) || 0;

        if (hit <= 0) {
            return '0';
        }

        return '★'.repeat(hit);
    }

    function renderUserHitTrace() {
        var el = $('userHitTrace');
        var actionsEl = $('userHitTraceActions');
        if (!el) return;

        var sourceList = [];
        var mapped = [];
        var showList = [];
        var maxVisible = 12;

        if (state.analysisData && safeArray(state.analysisData.hit_trace).length) {
            sourceList = safeArray(state.analysisData.hit_trace);
        } else {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            if (actionsEl) actionsEl.innerHTML = '';
            return;
        }

        if (!state.userSelectedNumbers.length) {
            el.innerHTML = '<div class="typ-small">請先選號</div>';
            if (actionsEl) actionsEl.innerHTML = '';
            return;
        }

        mapped = sourceList.map(function (row) {
            var drawNumbers = normalizeNumbers(row.numbers || []);
            var hit = 0;

            state.userSelectedNumbers.forEach(function (n) {
                if (drawNumbers.indexOf(n) !== -1) {
                    hit++;
                }
            });

            return {
                draw_term: row.draw_term || '--',
                hit: hit
            };
        });

        showList = state.userHitExpanded ? mapped : mapped.slice(0, maxVisible);

        el.innerHTML = showList.map(function (row) {
            return (
                '<div class="bingo-trace-row">' +
                '<span class="typ-body">第 ' + escapeHtml(row.draw_term) + ' 期</span>' +
                '<span class="typ-small">' + escapeHtml(renderHitStars(row.hit)) + '</span>' +
                '</div>'
            );
        }).join('');

        if (actionsEl) {
            if (mapped.length > maxVisible) {
                actionsEl.innerHTML =
                    '<button class="btn btn--secondary" id="btnToggleUserHitTrace" type="button">' +
                    (state.userHitExpanded ? '收合' : '展開全部') +
                    '</button>';
            } else {
                actionsEl.innerHTML = '';
            }
        }
    }

    function renderUserComboResults() {
        var el = $('userComboResults');
        if (!el) return;

        if (!state.userComboList.length) {
            el.innerHTML = '<div class="typ-small">尚未產生組合</div>';
            return;
        }

        el.innerHTML = state.userComboList.map(function (combo) {
            return (
                '<div class="bingo-combo-result-row">' +
                combo.map(function (n) { return ball(n, 'ball-active'); }).join('') +
                '</div>'
            );
        }).join('');
    }

    function toggleUserNumber(number) {
        number = parseInt(number, 10);
        if (!number || number < 1 || number > 80) return;

        var idx = state.userSelectedNumbers.indexOf(number);

        if (idx !== -1) {
            state.userSelectedNumbers.splice(idx, 1);
        } else {
            if (state.userSelectedNumbers.length >= 10) {
                alert('最多只能選 10 顆');
                return;
            }
            state.userSelectedNumbers.push(number);
        }

        state.userSelectedNumbers = uniqueNumbers(state.userSelectedNumbers, 10);
        state.userHitExpanded = false;
        renderUserBallBoard();
        renderUserSelectedBalls();
        renderUserHitTrace();
    }

    function buildUserCombos() {
        if (state.userSelectedNumbers.length <= state.userStar) {
            alert('選號數需大於玩法星數才可提供組合號碼');
            return;
        }

        var combos = combination(state.userSelectedNumbers, state.userStar);
        state.userComboList = combos;
        renderUserComboResults();
    }

    function clearUserCombos() {
        state.userComboList = [];
        renderUserComboResults();
    }

    /* 第4區：歷史開獎 */
    async function loadHistoryByLimit(limit) {
        limit = parseInt(limit, 10) || 10;

        var json = await fetchApi('api/bingo_history.php?limit=' + encodeURIComponent(limit));
        var list = (json.data && json.data.list) ? json.data.list : [];

        state.historyData = list;
        renderHistoryList(list);
    }

    async function loadHistoryByTermRange(startTerm, endTerm) {
        var url =
            'api/bingo_history.php?start_term=' + encodeURIComponent(startTerm) +
            '&end_term=' + encodeURIComponent(endTerm);

        var json = await fetchApi(url);
        var list = (json.data && json.data.list) ? json.data.list : [];

        state.historyData = list;
        renderHistoryList(list);
    }

    function renderHistoryList(list) {
        list = safeArray(list);

        safeSetHtml(
            'historyList',
            list.length
                ? list.map(function (row) {
                    var issueNo = row.issue_no || row.draw_term || '--';
                    var drawTime = row.draw_time || row.draw_at || '--';
                    var numbers = normalizeNumbers(row.numbers);

                    return (
                        '<div class="history-row">' +
                        '<div class="history-head">' +
                        '<span>第 ' + escapeHtml(issueNo) + ' 期</span>' +
                        '<span class="typ-small">' + escapeHtml(drawTime) + '</span>' +
                        '</div>' +
                        '<div class="balls-wrap">' +
                        numbers.map(function (n) { return ball(n); }).join('') +
                        '</div>' +
                        '</div>'
                    );
                }).join('')
                : '<div class="typ-small">無資料</div>'
        );
    }

    /* bind */
    function bindAnalysisControls() {
        if ($('analysisRangeInput')) {
            $('analysisRangeInput').addEventListener('input', function () {
                var value = parseInt(this.value || '100', 10);

                if (!value || value < 10) value = 10;
                if (value > 500) value = 500;

                state.analysisRange = value;

                if (analysisTimer) {
                    clearTimeout(analysisTimer);
                }

                analysisTimer = setTimeout(async function () {
                    try {
                        await loadAnalysis();
                        renderUserHitTrace();
                    } catch (err) {
                        console.error('loadAnalysis error:', err);
                    }
                }, 300);
            });
        }

        if ($('analysisStarSelect')) {
            $('analysisStarSelect').addEventListener('change', async function () {
                state.analysisStar = parseInt(this.value || '5', 10) || 5;

                try {
                    await loadAnalysis();
                    renderUserHitTrace();
                } catch (err) {
                    console.error('loadAnalysis error:', err);
                }
            });
        }
    }

    function bindUserControls() {
        if ($('userStarSelect')) {
            $('userStarSelect').addEventListener('change', function () {
                state.userStar = parseInt(this.value || '5', 10) || 5;
                state.userHitExpanded = false;
                renderUserHitTrace();
            });
        }

        if ($('userBallBoard')) {
            $('userBallBoard').addEventListener('click', function (e) {
                var target = e.target;
                if (!target || !target.matches('[data-role="user-ball"]')) {
                    return;
                }

                toggleUserNumber(target.getAttribute('data-number'));
            });
        }

        if ($('btnBuildUserCombos')) {
            $('btnBuildUserCombos').addEventListener('click', function () {
                buildUserCombos();
            });
        }

        if ($('btnClearUserCombos')) {
            $('btnClearUserCombos').addEventListener('click', function () {
                clearUserCombos();
            });
        }

        document.addEventListener('click', function (e) {
            var target = e.target;
            if (!target || target.id !== 'btnToggleUserHitTrace') {
                return;
            }

            state.userHitExpanded = !state.userHitExpanded;
            renderUserHitTrace();
        });
    }

    function bindHistorySearch() {
        if ($('btnHistorySearch')) {
            $('btnHistorySearch').addEventListener('click', async function () {
                var startTerm = ($('historyStartTerm') && $('historyStartTerm').value || '').trim();
                var endTerm = ($('historyEndTerm') && $('historyEndTerm').value || '').trim();

                if (!startTerm || !endTerm) {
                    alert('請輸入起始期數與結束期數');
                    return;
                }

                try {
                    await loadHistoryByTermRange(startTerm, endTerm);
                } catch (err) {
                    console.error('loadHistoryByTermRange error:', err);
                    safeSetHtml('historyList', '<div class="typ-small">無資料</div>');
                }
            });
        }

        if ($('btnHistoryReset')) {
            $('btnHistoryReset').addEventListener('click', async function () {
                if ($('historyStartTerm')) $('historyStartTerm').value = '';
                if ($('historyEndTerm')) $('historyEndTerm').value = '';

                try {
                    await loadHistoryByLimit(10);
                } catch (err) {
                    console.error('loadHistoryByLimit error:', err);
                    safeSetHtml('historyList', '<div class="typ-small">無資料</div>');
                }
            });
        }
    }

    /* init */
    async function init() {
        bindAnalysisControls();
        bindUserControls();
        bindHistorySearch();

        if ($('analysisRangeInput')) {
            state.analysisRange = parseInt($('analysisRangeInput').value || '100', 10) || 100;
        }

        if ($('analysisStarSelect')) {
            state.analysisStar = parseInt($('analysisStarSelect').value || '5', 10) || 5;
        }

        if ($('userStarSelect')) {
            state.userStar = parseInt($('userStarSelect').value || '5', 10) || 5;
        }

        renderUserBallBoard();
        renderUserSelectedBalls();
        renderUserComboResults();

        try {
            await loadLatest();
        } catch (err) {
            console.error('loadLatest error:', err);
        }

        try {
            await loadAnalysis();
        } catch (err) {
            console.error('loadAnalysis error:', err);
        }

        try {
            renderUserHitTrace();
        } catch (err) {
            console.error('renderUserHitTrace error:', err);
        }

        try {
            await loadHistoryByLimit(10);
        } catch (err) {
            console.error('loadHistoryByLimit error:', err);
            safeSetHtml('historyList', '<div class="typ-small">無資料</div>');
        }

        setInterval(async function () {
            try {
                await loadLatest();
            } catch (err) {
                console.error('auto loadLatest error:', err);
            }

            try {
                await loadAnalysis();
            } catch (err) {
                console.error('auto loadAnalysis error:', err);
            }

            try {
                renderUserHitTrace();
            } catch (err) {
                console.error('auto renderUserHitTrace error:', err);
            }

            try {
                await loadHistoryByLimit(10);
            } catch (err) {
                console.error('auto loadHistoryByLimit error:', err);
            }
        }, 60000);
    }

    document.addEventListener('DOMContentLoaded', init);
})();