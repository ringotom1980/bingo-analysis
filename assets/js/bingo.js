/*
 * Path: assets/js/bingo.js
 * 說明：賓果賓果分析頁控制，負責最新一期、排行、歷史與指定號碼分析。
 */

(function () {
  'use strict';

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

  function ball(n, extraClass) {
    var cls = 'ball';
    if (extraClass) {
      cls += ' ' + extraClass;
    }
    return '<span class="' + cls + '">' + escapeHtml(pad(n)) + '</span>';
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

  /* 最新一期 */
  async function loadLatest() {
    var json = await fetchApi('api/bingo_latest.php');
    var data = json.data || {};

    safeSetText('latestIssue', data.issue_no || data.draw_term || '--');
    safeSetText('latestTime', data.draw_time || '--');

    safeSetHtml(
      'latestBalls',
      Array.isArray(data.numbers) && data.numbers.length
        ? data.numbers.map(function (n) { return ball(n); }).join('')
        : '<span class="typ-small">無資料</span>'
    );
  }

  /* 分析 */
  async function loadAnalysis() {
    var json = await fetchApi('api/bingo_analysis.php');
    var data = json.data || {};

    renderRank('hotList', data.hot_top5 || [], 'hit_count', 'ball-hot');
    renderRank('coldList', data.cold_top5 || [], 'hit_count', 'ball-cold');
    renderRank('missList', data.miss_top5 || [], 'miss', 'ball-miss');
  }

  function renderRank(id, list, valueKey, ballClass) {
    var el = $(id);
    if (!el) return;

    if (!Array.isArray(list) || list.length === 0) {
      el.innerHTML = '<div class="typ-small">無資料</div>';
      return;
    }

    el.innerHTML = list.map(function (r) {
      var number = r.number != null ? r.number : '--';
      var value = r[valueKey];

      if ((value == null || value === '') && valueKey === 'miss') {
        value = r.last_hit != null ? r.last_hit : '--';
      }

      return (
        '<div class="rank-row">' +
          ball(number, ballClass) +
          '<span class="rank-value">' + escapeHtml(value) + '</span>' +
        '</div>'
      );
    }).join('');
  }

  /* 歷史 */
  async function loadHistory(limit) {
    limit = limit || 10;

    var json = await fetchApi('api/bingo_history.php?limit=' + encodeURIComponent(limit));
    var data = (json.data && json.data.list) ? json.data.list : [];

    safeSetHtml(
      'historyList',
      Array.isArray(data) && data.length
        ? data.map(function (row) {
            var issueNo = row.issue_no || row.draw_term || '--';
            var drawTime = row.draw_time || '--';
            var numbers = Array.isArray(row.numbers) ? row.numbers : [];

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

  /* 指定號碼 */
  async function searchNumber() {
    var n = parseInt($('numberInput').value, 10);

    if (!n || n < 1 || n > 80) {
      alert('請輸入 1~80');
      return;
    }

    var json = await fetchApi('api/bingo_history.php?limit=100');
    var list = (json.data && json.data.list) ? json.data.list : [];
    var hit = 0;

    list.forEach(function (r) {
      if (Array.isArray(r.numbers) && r.numbers.includes(n)) {
        hit++;
      }
    });

    safeSetHtml(
      'numberResult',
      '<div class="analysis-result">' +
        ball(n, 'ball-active') +
        '<div>最近100期出現 <strong>' + escapeHtml(hit) + '</strong> 次</div>' +
      '</div>'
    );
  }

  /* range */
  function bindRange() {
    document.querySelectorAll('.filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var r = parseInt(btn.dataset.range, 10) || 10;
        loadHistory(r);
      });
    });
  }

  /* init */
  async function init() {
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
      await loadHistory(10);
    } catch (err) {
      console.error('loadHistory error:', err);
      safeSetHtml('historyList', '<div class="typ-small">無資料</div>');
    }

    bindRange();

    if ($('btnSearch')) {
      $('btnSearch').addEventListener('click', searchNumber);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();