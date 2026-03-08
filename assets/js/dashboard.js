/* Path: assets/js/dashboard.js
 * 說明：主儀表板資料載入與畫面渲染。
 */

(function () {
  'use strict';

  function $(id) {
    return document.getElementById(id);
  }

  function safeText(el, text) {
    if (el) el.textContent = text == null || text === '' ? '--' : String(text);
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function pad2(val) {
    return String(val).padStart(2, '0');
  }

  function formatDateTime(input) {
    if (!input) return '--';

    var d = new Date(input);
    if (isNaN(d.getTime())) return String(input);

    return d.getFullYear() + '-' +
      pad2(d.getMonth() + 1) + '-' +
      pad2(d.getDate()) + ' ' +
      pad2(d.getHours()) + ':' +
      pad2(d.getMinutes()) + ':' +
      pad2(d.getSeconds());
  }

  function renderBalls(containerId, numbers, extraClass) {
    var el = $(containerId);
    if (!el) return;

    if (!Array.isArray(numbers) || numbers.length === 0) {
      el.innerHTML = '<span class="typ-small">無資料</span>';
      return;
    }

    el.innerHTML = numbers.map(function (num) {
      var cls = 'ball';
      if (extraClass) cls += ' ' + extraClass;
      return '<span class="' + cls + '">' + escapeHtml(pad2(num)) + '</span>';
    }).join('');
  }

  function renderRankBalls(containerId, items, className) {
    var el = $(containerId);
    if (!el) return;

    if (!Array.isArray(items) || items.length === 0) {
      el.innerHTML = '<span class="typ-small">無資料</span>';
      return;
    }

    el.innerHTML = items.map(function (item) {
      var no = item.number || item.num || item.ball || '--';
      var cnt = item.count || item.value || item.miss || 0;
      return (
        '<div class="ball-stat">' +
          '<span class="ball ' + escapeHtml(className || '') + '">' + escapeHtml(pad2(no)) + '</span>' +
          '<span class="typ-small">' + escapeHtml(cnt) + '</span>' +
        '</div>'
      );
    }).join('');
  }

  function renderRecentHistory(list) {
    var el = $('recentHistory');
    if (!el) return;

    if (!Array.isArray(list) || list.length === 0) {
      el.innerHTML = '<div class="typ-small">無資料</div>';
      return;
    }

    el.innerHTML = list.map(function (row) {
      var issueNo = row.issue_no || row.issue || '--';
      var drawTime = row.draw_time || row.draw_date || row.created_at || '--';
      var balls = Array.isArray(row.numbers) ? row.numbers : [];

      return (
        '<article class="history-item">' +
          '<div class="history-item__head">' +
            '<strong>第 ' + escapeHtml(issueNo) + ' 期</strong>' +
            '<span class="typ-small">' + escapeHtml(formatDateTime(drawTime)) + '</span>' +
          '</div>' +
          '<div class="balls-wrap">' +
            balls.map(function (num) {
              return '<span class="ball ball-sm">' + escapeHtml(pad2(num)) + '</span>';
            }).join('') +
          '</div>' +
        '</article>'
      );
    }).join('');
  }

  async function fetchJson(url) {
    var res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!res.ok) {
      throw new Error('HTTP ' + res.status);
    }

    return await res.json();
  }

  async function loadLatest() {
    var json = await fetchJson('api/bingo_latest.php');
    var data = json.data || json;

    safeText($('latestIssueNo'), data.issue_no || data.issue || '--');
    safeText($('latestDrawTime'), formatDateTime(data.draw_time || data.draw_date || data.created_at || '--'));
    renderBalls('latestBalls', data.numbers || [], '');
    safeText($('latestUpdatedAt'), formatDateTime(data.updated_at || data.draw_time || data.created_at || '--'));
  }

  async function loadHistory() {
    var json = await fetchJson('api/bingo_history.php?limit=5');
    var data = json.data || json;

    renderRecentHistory(Array.isArray(data) ? data : (data.list || []));
    safeText($('todayCount'), data.today_count || (Array.isArray(data) ? data.length : '--'));
  }

  async function loadAnalysis() {
    var json = await fetchJson('api/bingo_analysis.php?range=10');
    var data = json.data || json;

    renderRankBalls('hotTop5', data.hot_top5 || data.hot || [], 'ball-hot');
    renderRankBalls('coldTop5', data.cold_top5 || data.cold || [], 'ball-cold');
    renderRankBalls('missTop5', data.miss_top5 || data.miss || [], 'ball-miss');
    safeText($('dataStatus'), data.status || '正常');
  }

  function initLastLoginText() {
    var el = $('lastLoginText');
    if (!el) return;

    var raw = el.getAttribute('data-last-login');
    if (!raw) return;

    el.textContent = '最後登入時間：' + formatDateTime(raw);
  }

  async function init() {
    try {
      await Promise.all([
        loadLatest(),
        loadHistory(),
        loadAnalysis()
      ]);
    } catch (err) {
      console.error('dashboard init error:', err);
      safeText($('dataStatus'), '異常');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initLastLoginText();
    init();
  });
})();