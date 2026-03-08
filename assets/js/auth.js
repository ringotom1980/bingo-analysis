/* Path: assets/js/auth.js */
(function (global) {
  'use strict';

  function bindLoginForm(formSelector) {
    var form = document.querySelector(formSelector);
    if (!form || !global.Api) return;

    var msg = document.getElementById('loginMsg');
    var submitBtn = document.getElementById('loginSubmit');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var usernameEl = document.getElementById('username');
      var passwordEl = document.getElementById('password');

      var username = usernameEl ? usernameEl.value.trim() : '';
      var password = passwordEl ? passwordEl.value : '';

      if (msg) msg.textContent = '登入中...';
      if (submitBtn) submitBtn.disabled = true;

      global.Api.post('/api/auth_login.php', {
        username: username,
        password: password
      }).then(function (res) {
        if (res && res.success) {
          global.location.href = (global.BASE_URL || '') + '/index.php';
          return;
        }

        if (msg) {
          msg.textContent = (res && res.error && res.error.message)
            ? res.error.message
            : '登入失敗';
        }
      }).catch(function (err) {
        if (msg) msg.textContent = err.message || '登入失敗';
      }).finally(function () {
        if (submitBtn) submitBtn.disabled = false;
      });
    });
  }

  function logout() {
    if (!global.Api) return Promise.reject(new Error('Api 未載入'));

    return global.Api.post('/api/auth_logout.php', {}).then(function () {
      global.location.href = (global.BASE_URL || '') + '/login.php';
    });
  }

  function bindLogout(selector) {
    var btn = document.querySelector(selector);
    if (!btn) return;

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      logout().catch(function (err) {
        alert(err.message || '登出失敗');
      });
    });
  }

  global.Auth = {
    bindLoginForm: bindLoginForm,
    bindLogout: bindLogout,
    logout: logout
  };
})(window);