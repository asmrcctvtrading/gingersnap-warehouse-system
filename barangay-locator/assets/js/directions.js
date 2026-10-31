// Directions hand-off. Location is requested only when the visitor presses the button.
(function () {
  'use strict';
  var box = document.getElementById('dir');
  if (!box) { return; }
  var lat = parseFloat(box.getAttribute('data-lat')), lng = parseFloat(box.getAttribute('data-lng'));
  var tpl = box.getAttribute('data-tpl');
  var msg = document.getElementById('dir-msg');
  var link = document.getElementById('ext-link');

  function say(t) { msg.textContent = t; msg.hidden = !t; }
  function haversine(a, b) {
    var R = 6371000, r = Math.PI / 180, dLat = (b[0] - a[0]) * r, dLng = (b[1] - a[1]) * r;
    var x = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.sqrt(x));
  }

  document.getElementById('dir-locate').addEventListener('click', function () {
    if (!navigator.geolocation) { say(box.getAttribute('data-t-unavail')); return; }
    navigator.geolocation.getCurrentPosition(function (p) {
      var from = [p.coords.latitude, p.coords.longitude];
      link.href = tpl.replace('{from_lat}', from[0]).replace('{from_lng}', from[1]).replace('{to_lat}', lat).replace('{to_lng}', lng);
      say(box.getAttribute('data-t-straight').replace('{m}', Math.round(haversine(from, [lat, lng]))));
    }, function (err) {
      say(err && err.code === 1 ? box.getAttribute('data-t-denied') : box.getAttribute('data-t-unavail'));
    }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
  });
})();
