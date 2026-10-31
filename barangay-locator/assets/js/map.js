// Leaflet map module. Data comes only from the public API; nothing is hard-coded.
(function () {
  'use strict';
  var wrap = document.querySelector('.mapwrap');
  if (!wrap) { return; }
  var T = function (k) { return wrap.getAttribute('data-t-' + k) || ''; };
  var esc = BRGY.esc;
  var msg = document.getElementById('map-msg');
  var listEl = document.getElementById('maplist');
  var focusId = parseInt(wrap.getAttribute('data-focus'), 10) || 0;

  function say(text) { if (!msg) { return; } msg.textContent = text; msg.hidden = !text; }

  if (typeof L === 'undefined') { say(T('err')); return; }

  var map = L.map('map', { zoomControl: true }).setView([12.8797, 121.774], 6); // Philippines overview until data loads
  var markersLayer = L.layerGroup().addTo(map);
  var boundaryLayer = null;
  var userMarker = null, userPos = null;
  var items = [];
  var cfg = null;

  function haversine(a, b) {
    var R = 6371000, r = Math.PI / 180;
    var dLat = (b[0] - a[0]) * r, dLng = (b[1] - a[1]) * r;
    var x = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.sqrt(x));
  }
  function fmtDist(m) { return m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(2) + ' km'; }

  function icon(it) {
    var color = /^#[0-9a-fA-F]{6}$/.test(it.category.color) ? it.category.color : '#2563eb';
    return L.divIcon({
      className: 'brgy-pin',
      html: '<span style="display:flex;width:30px;height:30px;border-radius:50%;background:' + color +
            ';border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.5);align-items:center;justify-content:center;color:#fff;font-weight:700">' +
            (it.verified ? '✓' : '?') + '</span>',
      iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -14]
    });
  }

  function popupHtml(it) {
    var base = BRGY.base;
    return '<strong>' + esc(it.name) + '</strong><br>' + esc(it.category.name) +
      (it.address ? '<br>' + esc(it.address) : '') +
      (it.verified ? '' : '<br><em>unverified</em>') +
      '<br><a href="' + esc(base + '/establishment.php?id=' + it.id) + '">' + esc(T('open')) + '</a> · ' +
      '<a href="' + esc(base + '/directions.php?id=' + it.id) + '">' + esc(T('dir')) + '</a>';
  }

  function render() {
    markersLayer.clearLayers();
    listEl.innerHTML = '';
    if (!items.length) { say(T('none')); return; }
    say('');
    var pts = [];
    var ordered = items.slice();
    if (userPos) {
      ordered.forEach(function (it) { it._d = haversine(userPos, [it.lat, it.lng]); });
      ordered.sort(function (a, b) { return a._d - b._d; });
    }
    ordered.forEach(function (it) {
      var m = L.marker([it.lat, it.lng], { icon: icon(it), title: it.name, keyboard: true }).bindPopup(popupHtml(it));
      m.addTo(markersLayer);
      it._m = m;
      pts.push([it.lat, it.lng]);

      var card = document.createElement('article');
      card.className = 'card';
      card.innerHTML = '<strong>' + esc(it.name) + '</strong><div class="muted">' + esc(it.category.name) + '</div>' +
        (userPos ? '<div class="muted">' + fmtDist(it._d) + ' (' + esc(T('straight')) + ')</div>' : '') +
        '<button type="button" class="secondary" style="margin-top:.4rem">' + esc(T('details')) + '</button>';
      card.querySelector('button').addEventListener('click', function () {
        map.setView([it.lat, it.lng], Math.max(map.getZoom(), 18));
        m.openPopup();
        wrap.classList.remove('show-list'); wrap.classList.add('show-map');
      });
      listEl.appendChild(card);
    });
    if (userPos) { pts.push(userPos); }
    if (focusId) {
      var f = items.filter(function (i) { return i.id === focusId; })[0];
      if (f) { map.setView([f.lat, f.lng], 18); f._m.openPopup(); focusId = 0; return; }
    }
    if (!userPos) { map.fitBounds(pts, { padding: [30, 30], maxZoom: 18 }); }
    else { map.fitBounds(pts.slice(0, 6).concat([userPos]), { padding: [30, 30], maxZoom: 18 }); }
  }

  function load() {
    var f = document.getElementById('map-filters');
    var params = {};
    if (f.q.value.trim()) { params.q = f.q.value.trim(); }
    if (f.category_id.value) { params.category_id = f.category_id.value; }
    say(T('loading'));
    return BRGY.api('map/markers', params).then(function (r) { items = r.items; render(); })
      .catch(function () { items = []; markersLayer.clearLayers(); say(T('err')); });
  }

  function locate() {
    if (!navigator.geolocation) { say(T('unavail')); return; }
    navigator.geolocation.getCurrentPosition(function (p) {
      userPos = [p.coords.latitude, p.coords.longitude];
      if (userMarker) { map.removeLayer(userMarker); }
      userMarker = L.circleMarker(userPos, { radius: 9, color: '#fff', weight: 3, fillColor: '#2563eb', fillOpacity: 1 }).addTo(map).bindTooltip('You');
      render();
    }, function (err) {
      say(err && err.code === 1 ? T('denied') : T('unavail'));
    }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
  }

  Promise.all([BRGY.api('config'), BRGY.api('barangays')]).then(function (res) {
    cfg = res[0];
    L.tileLayer(cfg.tile_url, { attribution: cfg.tile_attribution || '', maxZoom: 19 }).addTo(map);
    if (!cfg.tile_is_production_ok && /tile\.openstreetmap\.org/.test(cfg.tile_url || '')) {
      var w = document.getElementById('tile-warn'); if (w) { w.hidden = false; }
    }
    var b = res[1].items[0];
    if (b && b.center) { map.setView(b.center, b.zoom || 16); }
    // Draw a boundary only when the barangay supplied one together with its source.
    res[1].items.forEach(function (br) {
      if (br.boundary && br.boundary_source) {
        boundaryLayer = L.geoJSON(br.boundary, { style: { color: '#1d4ed8', weight: 2, fill: false, dashArray: '6 4' } }).addTo(map);
        var bn = document.getElementById('boundary-note');
        if (bn) { bn.textContent = T('boundary').replace('{source}', br.boundary_source); bn.hidden = false; }
      }
    });
  }).catch(function () { say(T('err')); }).then(function () {
    return load();
  }).then(function () {
    if (wrap.getAttribute('data-autolocate') === '1') { locate(); }
  });

  document.getElementById('map-filters').addEventListener('submit', function (e) { e.preventDefault(); load(); });
  document.getElementById('locate-btn').addEventListener('click', locate);

  var tgl = document.getElementById('toggle-view');
  if (tgl) {
    wrap.classList.add('show-map');
    tgl.addEventListener('click', function () {
      var toList = wrap.classList.contains('show-map');
      wrap.classList.toggle('show-map', !toList);
      wrap.classList.toggle('show-list', toList);
      tgl.textContent = toList ? T('map') : T('list');
      setTimeout(function () { map.invalidateSize(); }, 50);
    });
  }
})();
