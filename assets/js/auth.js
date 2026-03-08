(function (global) {
  'use strict';

  function logout() {
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
    logout: logout,
    bindLogout: bindLogout
  };
})(window);