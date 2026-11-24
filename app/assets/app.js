(function () {
  var root = document.documentElement;
  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  var theme = document.getElementById('themeBtn');
  if (theme) theme.addEventListener('click', function () {
    var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next; store('gsw:theme', next);
  });
  var side = document.getElementById('sideBtn');
  if (side) side.addEventListener('click', function () {
    if (window.matchMedia('(max-width:860px)').matches) { root.classList.toggle('side-open'); return; }
    var closed = root.classList.toggle('side-closed'); store('gsw:side', closed ? '0' : '1');
  });
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  var add = document.getElementById('addLine'), lines = document.getElementById('lines');
  if (add && lines) add.addEventListener('click', function () {
    var c = lines.firstElementChild.cloneNode(true);
    c.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    c.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
    lines.appendChild(c);
  });
})();
