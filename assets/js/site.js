/* Small progressive enhancements; every page works without JavaScript. */
(function () {
  var t = document.querySelector('[data-navtoggle]'), nav = document.getElementById('nav');
  if (t && nav) t.addEventListener('click', function () { var o = nav.classList.toggle('open'); t.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  // Confirm before destructive actions
  document.querySelectorAll('form[data-confirm]').forEach(function (f) { f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); }); });
  // Reservation form: refresh the available times when the day or party size changes
  var rf = document.getElementById('res-form');
  if (rf) {
    var day = rf.querySelector('[name=day]'), party = rf.querySelector('[name=party]'), box = document.getElementById('res-times');
    var load = function () {
      fetch('/reservations/times?day=' + encodeURIComponent(day.value) + '&party=' + encodeURIComponent(party.value), { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); }).then(function (times) {
          box.innerHTML = '';
          var keys = Object.keys(times);
          if (!keys.length) { box.innerHTML = '<p class="hint">No times left on this day for your party size. Try another day.</p>'; return; }
          keys.forEach(function (k, i) {
            var l = document.createElement('label'), inp = document.createElement('input'), s = document.createElement('span');
            inp.type = 'radio'; inp.name = 'time'; inp.value = k; if (i === 0) inp.required = true;
            s.textContent = times[k]; l.appendChild(inp); l.appendChild(s); box.appendChild(l);
          });
        }).catch(function () {});
    };
    day.addEventListener('change', load); party.addEventListener('change', load);
  }
  // Kitchen screen: reload every 20 s so new orders appear
  if (document.body.classList.contains('kitchen')) setTimeout(function () { location.reload(); }, 20000);
  // Live "x min" counters
  document.querySelectorAll('[data-since]').forEach(function (el) {
    var t0 = +el.getAttribute('data-since'); var tick = function () { var m = Math.max(0, Math.round((Date.now() - t0) / 60000)); el.textContent = m + ' min'; }; tick(); setInterval(tick, 30000);
  });
})();
