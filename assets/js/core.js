(function (global) {
  'use strict';

  function qs(selector, scope) {
    return (scope || document).querySelector(selector);
  }

  function qsa(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  function on(el, evt, handler, opts) {
    if (!el) return;
    el.addEventListener(evt, handler, opts || false);
  }

  function addClass(el, className) {
    if (el) el.classList.add(className);
  }

  function removeClass(el, className) {
    if (el) el.classList.remove(className);
  }

  function toggleClass(el, className, force) {
    if (el) el.classList.toggle(className, force);
  }

  function formatDateTime(value) {
    if (!value) return '';
    var d = new Date(value.replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(value);
    return d.getFullYear() + '-' +
      String(d.getMonth() + 1).padStart(2, '0') + '-' +
      String(d.getDate()).padStart(2, '0') + ' ' +
      String(d.getHours()).padStart(2, '0') + ':' +
      String(d.getMinutes()).padStart(2, '0') + ':' +
      String(d.getSeconds()).padStart(2, '0');
  }

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  global.AppCore = {
    qs: qs,
    qsa: qsa,
    on: on,
    addClass: addClass,
    removeClass: removeClass,
    toggleClass: toggleClass,
    formatDateTime: formatDateTime,
    pad2: pad2,
    escapeHtml: escapeHtml
  };
})(window);