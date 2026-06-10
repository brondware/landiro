(function() {
  var slider = document.getElementById('baSlider');
  var divider = document.getElementById('baDivider');
  if (!slider || !divider) return;
  var after = slider.querySelector('.ba-after');
  var dragging = false;

  function setPos(x) {
    var rect = slider.getBoundingClientRect();
    var pct = Math.min(100, Math.max(0, (x - rect.left) / rect.width * 100));
    divider.style.left = pct + '%';
    after.style.clipPath = 'inset(0 0 0 ' + pct + '%)';
  }

  divider.addEventListener('mousedown', function(e) { dragging = true; e.preventDefault(); });
  slider.addEventListener('mousedown', function(e) { dragging = true; setPos(e.clientX); });
  document.addEventListener('mousemove', function(e) { if (dragging) setPos(e.clientX); });
  document.addEventListener('mouseup', function() { dragging = false; });

  divider.addEventListener('touchstart', function(e) { dragging = true; }, { passive: true });
  document.addEventListener('touchmove', function(e) { if (dragging && e.touches[0]) setPos(e.touches[0].clientX); }, { passive: true });
  document.addEventListener('touchend', function() { dragging = false; });
})();
