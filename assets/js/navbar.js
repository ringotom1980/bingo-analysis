(function (global) {
  'use strict';

  var core = global.AppCore;

  function initNavbar() {
    var toggleBtn = core.qs('[data-nav-toggle]');
    var drawer = core.qs('[data-nav-drawer]');

    if (!toggleBtn || !drawer) return;

    core.on(toggleBtn, 'click', function () {
      var isOpen = drawer.classList.contains('is-open');
      core.toggleClass(drawer, 'is-open', !isOpen);
      toggleBtn.setAttribute('aria-expanded', String(!isOpen));
    });

    core.on(document, 'click', function (e) {
      var isToggle = toggleBtn.contains(e.target);
      var isDrawer = drawer.contains(e.target);
      if (!isToggle && !isDrawer) {
        core.removeClass(drawer, 'is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  global.Navbar = {
    init: initNavbar
  };
})(window);