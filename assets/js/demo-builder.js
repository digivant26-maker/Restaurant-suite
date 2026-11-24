/* Live preview on the demo builder: name, initials, colour and logo update as the visitor types. */
(function () {
  var f = document.getElementById('demo-form'), pv = document.getElementById('demo-preview');
  if (!f || !pv) return;
  var name = f.querySelector('#shop_name'), color = f.querySelector('#brand_color'), logo = f.querySelector('#logo');
  var pvName = document.getElementById('pv-name'), pvIni = document.getElementById('pv-ini'), pvLogo = document.getElementById('pv-logo');
  var small = ['the', 'and', 'of'];
  function initials(s) {
    var w = s.replace(/['‘’]/g, '').split(/[^A-Za-z0-9À-ɏ]+/).filter(Boolean);
    var big = w.filter(function (x) { return small.indexOf(x.toLowerCase()) < 0; });
    var use = big.length ? big : w;
    return (use.length > 1 ? use[0][0] + use[1][0] : (use[0] || 'YS').slice(0, 2)).toUpperCase();
  }
  function lum(hex) {
    var c = [1, 3, 5].map(function (i) { var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function paint() {
    var n = name.value.trim() || 'Your Sports Bar', c = /^#[0-9a-f]{6}$/i.test(color.value) ? color.value : '#D4A017';
    pvName.textContent = n; pvIni.textContent = initials(n);
    pv.style.setProperty('--pv', c);
    pv.querySelector('.pv-site').style.setProperty('--pv', c);
    // Same rule as the server: white text unless the colour is light.
    pv.querySelector('.pv-site').style.setProperty('--pv-on', (1.05 / (lum(c) + 0.05)) >= 4.5 ? '#FFFFFF' : '#111111');
  }
  name.addEventListener('input', paint); color.addEventListener('input', paint);
  logo.addEventListener('change', function () {
    var file = logo.files && logo.files[0];
    pvLogo.querySelectorAll('img').forEach(function (i) { i.remove(); });
    if (!file || !/^image\/(png|jpeg|webp)$/.test(file.type)) { pvIni.hidden = false; return; }
    var img = new Image(); img.alt = ''; img.src = URL.createObjectURL(file);
    pvIni.hidden = true; pvLogo.appendChild(img);
  });
  paint();
})();
