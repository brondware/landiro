(function () {
  var section = document.querySelector('.stats-bar-01');
  if (!section) return;

  function countUp(el, target, duration) {
    var start = 0, step = target / (duration / 16);
    var timer = setInterval(function () {
      start = Math.min(start + step, target);
      el.textContent = Math.round(start);
      if (start >= target) clearInterval(timer);
    }, 16);
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var delay = parseInt(e.target.dataset.delay || 0, 10);
      setTimeout(function () {
        e.target.classList.add('visible');
        var valEl = e.target.querySelector('.sb1-val');
        var target = parseInt(valEl.dataset.target, 10);
        if (!isNaN(target)) countUp(valEl, target, 1200);
      }, delay);
      io.unobserve(e.target);
    });
  }, { threshold: 0.3 });

  section.querySelectorAll('[data-animate]').forEach(function (el) { io.observe(el); });
})();
