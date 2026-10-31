// Public suggestion form -> POST api reports. Server stores it as a pending request; nothing is published.
(function () {
  'use strict';
  var form = document.getElementById('report-form');
  if (!form) { return; }
  var msg = document.getElementById('report-msg');
  var btn = document.getElementById('report-submit');
  var label = btn.textContent;

  function say(text, cls) { msg.textContent = text; msg.className = 'notice ' + cls; msg.hidden = false; }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var data = {};
    new FormData(form).forEach(function (v, k) { data[k] = v; });
    btn.disabled = true; btn.textContent = btn.getAttribute('data-sending');
    fetch(BRGY.base + '/api/index.php?r=reports', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': BRGY.csrf, 'Accept': 'application/json' },
      body: JSON.stringify(data)
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j.ok) { say(res.j.message, 'ok'); form.reset(); }
        else { say(res.j.error || 'Could not send.', 'error'); }
      })
      .catch(function () { say('Could not send. Check your connection and try again.', 'error'); })
      .then(function () { btn.disabled = false; btn.textContent = label; });
  });
})();
