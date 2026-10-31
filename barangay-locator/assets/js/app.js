// Shared UI: kiosk mode (idle reset), CSRF helper. No frameworks.
(function () {
  'use strict';
  var body = document.body;
  var base = body.getAttribute('data-base') || '';

  window.BRGY = {
    base: base,
    csrf: body.getAttribute('data-csrf') || '',
    lang: body.getAttribute('data-lang') || 'en',
    api: function (path, params) {
      var qs = params ? '?' + new URLSearchParams(params).toString() : '';
      // ?r= form works even when mod_rewrite is unavailable.
      return fetch(base + '/api/index.php?r=' + encodeURIComponent(path).replace(/%2F/g, '/') + (qs ? '&' + qs.slice(1) : ''), {
        headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
      }).then(function (r) { return r.json().then(function (j) { if (!r.ok || j.ok === false) { throw new Error(j.error || 'Request failed'); } return j; }); });
    },
    esc: function (s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
  };

  // Kiosk mode: enabled with ?kiosk=1 (remembered for the tab), ?kiosk=0 turns it off.
  var params = new URLSearchParams(location.search);
  try {
    if (params.get('kiosk') === '1') { sessionStorage.setItem('kiosk', '1'); }
    if (params.get('kiosk') === '0') { sessionStorage.removeItem('kiosk'); }
  } catch (e) { /* storage may be blocked; kiosk then lasts for this page only */ }
  var kiosk = params.get('kiosk') === '1';
  try { kiosk = kiosk || sessionStorage.getItem('kiosk') === '1'; } catch (e) {}
  if (params.get('kiosk') === '0') { kiosk = false; }
  if (!kiosk) { return; }

  body.classList.add('kiosk');
  var overlay = document.getElementById('kiosk-overlay');
  var idleMs = (parseInt(body.getAttribute('data-idle'), 10) || 90) * 1000;
  var home = base + '/index.php';
  var timer = null;

  function atHome() { return location.pathname.replace(/\/+$/, '') === (base + '/index.php') || location.pathname === base + '/'; }
  function reset() {
    clearTimeout(timer);
    if (overlay && !overlay.hidden) { return; }
    timer = setTimeout(function () {
      // Idle: return to the home screen and show the "touch to start" cover. Clears any typed search.
      if (!atHome()) { location.href = home + '?kiosk=1'; return; }
      var inputs = document.querySelectorAll('input[type=search],input[type=text]');
      for (var i = 0; i < inputs.length; i++) { inputs[i].value = ''; }
      if (overlay) { overlay.hidden = false; }
    }, idleMs);
  }
  ['pointerdown', 'keydown', 'touchstart', 'mousemove'].forEach(function (ev) {
    window.addEventListener(ev, reset, { passive: true });
  });
  var start = document.getElementById('kiosk-start');
  if (start) { start.addEventListener('click', function () { overlay.hidden = true; reset(); }); }
  reset();
})();
