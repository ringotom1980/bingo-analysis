(function (global) {
  'use strict';

  function base() {
    try {
      return String(global.BASE_URL || '').replace(/\/+$/, '');
    } catch (e) {
      return '';
    }
  }

  function buildUrl(path) {
    path = String(path || '');
    if (!path.startsWith('/')) path = '/' + path;
    return base() + path;
  }

  function request(opts) {
    opts = opts || {};

    var method = String(opts.method || 'GET').toUpperCase();
    var url = buildUrl(opts.url || '/');
    var headers = opts.headers || {};
    var body = opts.body || null;

    var config = {
      method: method,
      credentials: 'same-origin',
      headers: headers
    };

    if (body instanceof FormData) {
      config.body = body;
    } else if (body && method !== 'GET') {
      headers['Content-Type'] = 'application/json';
      config.body = JSON.stringify(body);
    }

    return fetch(url, config).then(function (res) {
      return res.text().then(function (text) {
        var json = null;
        try { json = JSON.parse(text); } catch (e) {}
        if (!res.ok) {
          throw new Error((json && json.error && json.error.message) || ('HTTP ' + res.status));
        }
        return json || text;
      });
    });
  }

  function get(url) {
    return request({ method: 'GET', url: url });
  }

  function post(url, body) {
    return request({ method: 'POST', url: url, body: body });
  }

  global.Api = {
    request: request,
    get: get,
    post: post
  };
})(window);