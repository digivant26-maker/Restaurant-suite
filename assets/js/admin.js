(function () {
  var b = document.querySelector('[data-side]'), s = document.getElementById('side');
  if (b && s) b.addEventListener('click', function () { var o = s.classList.toggle('open'); b.setAttribute('aria-expanded', o ? 'true' : 'false'); });
  // Auto-refresh order and reservation lists every 30 s unless the user is typing
  if (document.body.dataset.refresh || document.querySelector('[data-autorefresh]')) setInterval(function () {
    var a = document.activeElement; if (a && /INPUT|TEXTAREA|SELECT/.test(a.tagName)) return; location.reload();
  }, 30000);
})();
