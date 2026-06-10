(function () {
  var section = document.querySelector('.steps-numbered-01');
  if (!section) return;
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var delay = parseInt(e.target.dataset.delay || 0, 10);
      setTimeout(function () { e.target.classList.add('visible'); }, delay);
      io.unobserve(e.target);
    });
  }, { threshold: 0.15 });
  section.querySelectorAll('[data-animate]').forEach(function (el) { io.observe(el); });
})();
