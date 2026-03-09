/*
 * Path: assets/js/bingo.js
 * 說明：賓果賓果分析頁控制，負責最新一期、熱冷號分析、未出現期數、最佳組合與歷史開獎。
 */

(function () {
    'use strict';

    var state = {
        analysisRange: 10,
        comboStar: 5,
        comboHour: 3,
        comboSelected: [],
        comboRecommended: [],
        comboHistory: []
    };

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

    function rankedBall(item, valueKey, ballClass) {
        var number = item && item.number != null ? parseInt(item.number, 10) : 0;
        var value = item && item[valueKey] != null ? item[valueKey] : '--';

        if (!number) {
            return '';
        }

        return (
            '<div class="ball-stat">' +
            ball(number, ballClass) +
            '<span class="typ-small">× ' + escapeHtml(value) + '</span>' +
            '</div>'
        );
    }

    function missBall(item) {
        var number = item && item.number != null ? parseInt(item.number, 10) : 0;
        var miss = item && item.miss != null ? item.miss : '--';

        if (!number) {
            return '';
        }

        return (
            '<div class="ball-stat">' +
            ball(number, 'ball-miss') +
            '<span class="typ-small">' + escapeHtml(miss) + ' 期</span>' +
            '</div>'
        );
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

    function setActiveRangeButton(range) {
        document.querySelectorAll('#analysisRangeButtons .filter-btn').forEach(function (btn) {
            var btnRange = parseInt(btn.getAttribute('data-range') || '0', 10);
            var active = btnRange === range;

            btn.classList.toggle('is-active', active);
            btn.classList.toggle('btn--primary', active);
            btn.classList.toggle('btn--secondary', !active);
        });
    }

    /* 最新一期 */
    async function loadLatest() {
        var json = await fetchApi('api/bingo_latest.php');
        var data = json.data || {};

        safeSetText('latestIssue', data.issue_no || data.draw_term || '--');
        safeSetText('latestTime', data.draw_time || '--');

        safeSetHtml(
            'latestBalls',
            safeArray(data.numbers).length
                ? safeArray(data.numbers).map(function (n) { return ball(n); }).join('')
                : '<span class="typ-small">無資料</span>'
        );
    }

    /* 熱冷號 / 統計 / 未出現期數 */
    async function loadAnalysis() {
        var url = 'api/bingo_analysis.php?range=' + encodeURIComponent(state.analysisRange);
        var json = await fetchApi(url);
        var data = json.data || {};

        safeSetText('statOddCount', data.stats && data.stats.odd_count != null ? data.stats.odd_count : '--');
        safeSetText('statEvenCount', data.stats && data.stats.even_count != null ? data.stats.even_count : '--');
        safeSetText('statLowCount', data.stats && data.stats.low_count != null ? data.stats.low_count : '--');
        safeSetText('statHighCount', data.stats && data.stats.high_count != null ? data.stats.high_count : '--');

        renderHotCold('hotList', data.hot_top10 || [], 'hit_count', 'ball-hot');
        renderHotCold('coldList', data.cold_top10 || [], 'hit_count', 'ball-cold');
        renderMiss('missList', data.miss_top10 || []);

        setActiveRangeButton(state.analysisRange);
    }

    function renderHotCold(id, list, valueKey, ballClass) {
        var el = $(id);
        if (!el) return;

        list = safeArray(list);

        if (!list.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = list.map(function (item) {
            return rankedBall(item, valueKey, ballClass);
        }).join('');
    }

    function renderMiss(id, list) {
        var el = $(id);
        if (!el) return;

        list = safeArray(list);

        if (!list.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = list.map(function (item) {
            return missBall(item);
        }).join('');
    }

    /* 最佳組合 */
    async function loadComboRecommendation() {
        var url =
            'api/bingo_analysis.php?mode=combo' +
            '&hours=' + encodeURIComponent(state.comboHour) +
            '&star=' + encodeURIComponent(state.comboStar);

        var json = await fetchApi(url);
        var data = json.data || {};

        state.comboRecommended = uniqueNumbers(data.recommended_numbers || [], state.comboStar);
        state.comboSelected = uniqueNumbers(
            (state.comboSelected && state.comboSelected.length ? state.comboSelected : state.comboRecommended),
            state.comboStar
        );
        state.comboHistory = safeArray(data.trace_list);

        renderComboBoard();
        renderComboSelected();
        renderComboStats(data.hit_stats || null, state.comboHistory);
        renderComboTrace(state.comboHistory);
    }

    function renderComboBoard() {
        var el = $('comboBallBoard');
        if (!el) return;

        var html = [];

        for (var n = 1; n <= 80; n++) {
            var isSelected = state.comboSelected.indexOf(n) !== -1;
            var isRecommended = state.comboRecommended.indexOf(n) !== -1;
            var cls = 'ball-sm';

            if (isSelected) {
                cls += ' ball-selected';
            } else if (isRecommended) {
                cls += ' ball-active';
            }

            html.push(
                ball(n, cls, '', {
                    'data-number': n,
                    'data-role': 'combo-ball'
                })
            );
        }

        el.innerHTML = html.join('');
    }

    function renderComboSelected() {
        var list = uniqueNumbers(state.comboSelected, state.comboStar);

        safeSetHtml(
            'comboSelectedBalls',
            list.length
                ? list.map(function (n) { return ball(n, 'ball-selected'); }).join('')
                : '<span class="typ-small">尚未選取號碼</span>'
        );
    }

    function renderComboStats(hitStats, traceList) {
        var el = $('comboHitStats');
        if (!el) return;

        var stats = {};
        var html = [];
        var i;

        if (hitStats && typeof hitStats === 'object') {
            for (i = state.comboStar; i >= 0; i--) {
                stats[i] = parseInt(hitStats[String(i)] || hitStats[i] || 0, 10) || 0;
            }
        } else {
            safeArray(traceList).forEach(function (row) {
                var hit = parseInt(row.hit_count || 0, 10);
                if (hit < 0) hit = 0;
                if (hit > state.comboStar) hit = state.comboStar;
                stats[hit] = (stats[hit] || 0) + 1;
            });

            for (i = state.comboStar; i >= 0; i--) {
                stats[i] = stats[i] || 0;
            }
        }

        for (i = state.comboStar; i >= 0; i--) {
            html.push(
                '<div class="bingo-hit-row">' +
                '<span class="typ-body">' + escapeHtml('命中 ' + i + ' 星') + '</span>' +
                '<strong>' + escapeHtml(stats[i]) + '</strong>' +
                '</div>'
            );
        }

        el.innerHTML = html.join('');
    }

    function renderComboTrace(traceList) {
        var el = $('comboTraceList');
        if (!el) return;

        traceList = safeArray(traceList);

        if (!traceList.length) {
            el.innerHTML = '<div class="typ-small">無資料</div>';
            return;
        }

        el.innerHTML = traceList.map(function (row) {
            var issueNo = row.issue_no || row.draw_term || '--';
            var hitCount = parseInt(row.hit_count || 0, 10);
            var stars = '';

            for (var i = 0; i < hitCount; i++) {
                stars += '★';
            }
            if (!stars) {
                stars = '0星';
            }

            return (
                '<div class="bingo-trace-row">' +
                '<span class="typ-body">第 ' + escapeHtml(issueNo) + ' 期</span>' +
                '<span class="typ-small">' + escapeHtml(stars) + '</span>' +
                '</div>'
            );
        }).join('');
    }

    function toggleComboNumber(number) {
        number = parseInt(number, 10);
        if (!number || number < 1 || number > 80) return;

        var idx = state.comboSelected.indexOf(number);

        if (idx !== -1) {
            state.comboSelected.splice(idx, 1);
        } else {
            if (state.comboSelected.length >= state.comboStar) {
                state.comboSelected.shift();
            }
            state.comboSelected.push(number);
        }

        state.comboSelected = uniqueNumbers(state.comboSelected, state.comboStar);
        renderComboBoard();
        renderComboSelected();
        recalcComboTraceFromCurrentSelection();
    }

    function recalcComboTraceFromCurrentSelection() {
        var selected = uniqueNumbers(state.comboSelected, state.comboStar);
        var baseList = safeArray(state.comboHistory);
        var mapped = [];
        var stats = {};

        baseList.forEach(function (row) {
            var numbers = normalizeNumbers(row.numbers || []);
            var hit = 0;

            selected.forEach(function (n) {
                if (numbers.indexOf(n) !== -1) {
                    hit++;
                }
            });

            mapped.push({
                issue_no: row.issue_no || row.draw_term || '--',
                hit_count: hit,
                numbers: numbers
            });

            stats[hit] = (stats[hit] || 0) + 1;
        });

        renderComboStats(stats, mapped);
        renderComboTrace(mapped);
    }

    /* 歷史 */
    async function loadHistoryByLimit(limit) {
        limit = parseInt(limit, 10) || 10;

        var json = await fetchApi('api/bingo_history.php?limit=' + encodeURIComponent(limit));
        renderHistoryList((json.data && json.data.list) ? json.data.list : []);
    }

    async function loadHistoryByTermRange(startTerm, endTerm) {
        var url =
            'api/bingo_history.php?start_term=' + encodeURIComponent(startTerm) +
            '&end_term=' + encodeURIComponent(endTerm);

        var json = await fetchApi(url);
        renderHistoryList((json.data && json.data.list) ? json.data.list : []);
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
    function bindAnalysisRange() {
        document.querySelectorAll('#analysisRangeButtons .filter-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                var range = parseInt(btn.getAttribute('data-range') || '10', 10) || 10;
                state.analysisRange = range;

                try {
                    await loadAnalysis();
                } catch (err) {
                    console.error('loadAnalysis error:', err);
                    safeSetHtml('hotList', '<div class="typ-small">無資料</div>');
                    safeSetHtml('coldList', '<div class="typ-small">無資料</div>');
                    safeSetHtml('missList', '<div class="typ-small">無資料</div>');
                }
            });
        });
    }

    function bindComboControls() {
        if ($('comboStarSelect')) {
            $('comboStarSelect').addEventListener('change', async function () {
                state.comboStar = parseInt(this.value || '5', 10) || 5;
                state.comboSelected = [];

                try {
                    await loadComboRecommendation();
                } catch (err) {
                    console.error('loadComboRecommendation error:', err);
                }
            });
        }

        if ($('comboHourSelect')) {
            $('comboHourSelect').addEventListener('change', async function () {
                state.comboHour = parseInt(this.value || '3', 10) || 3;
                state.comboSelected = [];

                try {
                    await loadComboRecommendation();
                } catch (err) {
                    console.error('loadComboRecommendation error:', err);
                }
            });
        }

        if ($('comboBallBoard')) {
            $('comboBallBoard').addEventListener('click', function (e) {
                var target = e.target;
                if (!target || !target.matches('[data-role="combo-ball"]')) {
                    return;
                }

                toggleComboNumber(target.getAttribute('data-number'));
            });
        }
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
        bindAnalysisRange();
        bindComboControls();
        bindHistorySearch();

        if ($('comboStarSelect')) {
            state.comboStar = parseInt($('comboStarSelect').value || '5', 10) || 5;
        }

        if ($('comboHourSelect')) {
            state.comboHour = parseInt($('comboHourSelect').value || '3', 10) || 3;
        }

        try {
            await loadLatest();
        } catch (err) {
            console.error('loadLatest error:', err);
        }

        try {
            await loadAnalysis();
        } catch (err) {
            console.error('loadAnalysis error:', err);
            safeSetHtml('hotList', '<div class="typ-small">無資料</div>');
            safeSetHtml('coldList', '<div class="typ-small">無資料</div>');
            safeSetHtml('missList', '<div class="typ-small">無資料</div>');
        }

        try {
            await loadComboRecommendation();
        } catch (err) {
            console.error('loadComboRecommendation error:', err);
            safeSetHtml('comboBallBoard', '<div class="typ-small">無資料</div>');
            safeSetHtml('comboSelectedBalls', '<div class="typ-small">無資料</div>');
            safeSetHtml('comboHitStats', '<div class="typ-small">無資料</div>');
            safeSetHtml('comboTraceList', '<div class="typ-small">無資料</div>');
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
                await loadAnalysis();
                await loadComboRecommendation();
            } catch (err) {
                console.error('auto refresh error:', err);
            }
        }, 60000);
    }

    document.addEventListener('DOMContentLoaded', init);
})();