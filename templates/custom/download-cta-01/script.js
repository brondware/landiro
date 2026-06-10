(function () {
  var section = document.querySelector('.download-cta-01');
  if (!section) return;

  // Scroll animations
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var delay = parseInt(e.target.dataset.delay || 0, 10);
      setTimeout(function () { e.target.classList.add('visible'); }, delay);
      io.unobserve(e.target);
    });
  }, { threshold: 0.15 });
  section.querySelectorAll('[data-animate]').forEach(function (el) { io.observe(el); });

  // Live download counter
  var countEl = document.getElementById('dc1-count');
  if (countEl) {
    fetch('/download-count.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var n = parseInt(data.count, 10) || 0;
        countEl.textContent = n.toLocaleString('uk-UA');
      })
      .catch(function () { countEl.textContent = '0'; });
  }
})();
