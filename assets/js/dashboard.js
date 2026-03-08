/* Path: assets/js/dashboard.js
 * 說明：主儀表板資料載入與畫面渲染（僅顯示最新一期）
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

  /* 渲染號碼球 */
  function renderBalls(containerId, numbers) {
    var el = $(containerId);
    if (!el) return;

    if (!Array.isArray(numbers) || numbers.length === 0) {
      el.innerHTML = '<span class="typ-small">無資料</span>';
      return;
    }

    el.innerHTML = numbers.map(function (num) {
      return '<span class="ball">' + escapeHtml(pad2(num)) + '</span>';
    }).join('');
  }

  /* API */
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

  /* 取得最新一期 */
  async function loadLatest() {

    var json = await fetchJson('api/bingo_latest.php');
    var data = json.data || json;

    safeText(
      $('bingoIssue'),
      data.issue_no || data.issue || '--'
    );

    safeText(
      $('bingoTime'),
      formatDateTime(
        data.draw_time ||
        data.draw_date ||
        data.created_at ||
        '--'
      )
    );

    renderBalls(
      'bingoBalls',
      data.numbers || []
    );

    safeText(
      $('latestUpdatedAt'),
      formatDateTime(
        data.updated_at ||
        data.draw_time ||
        data.created_at ||
        '--'
      )
    );

    safeText(
      $('todayCount'),
      data.today_count || '--'
    );

    safeText(
      $('dataStatus'),
      '正常'
    );

  }

  /* 初始化登入時間 */
  function initLastLoginText() {

    var el = $('lastLoginText');
    if (!el) return;

    var raw = el.getAttribute('data-last-login');
    if (!raw) return;

    el.textContent = '最後登入時間：' + formatDateTime(raw);

  }

  /* 主初始化 */
  async function init() {

    try {

      await loadLatest();

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