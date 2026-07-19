/* Hostel Samobor — carousel: scroll-snap tracks + hero crossfade slideshow */
(function () {
  "use strict";
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* Scroll-snap carousels: JS only drives the arrow buttons */
  document.querySelectorAll("[data-carousel]").forEach(function (root) {
    var track = root.querySelector(".car-track");
    /* buttons may sit in a sibling .car-head, so search the whole section */
    var scope = root.closest("section") || root;
    var buttons = scope.querySelectorAll(".car-btn[data-dir]");
    if (!track || !buttons.length) return;

    function step() {
      var item = track.firstElementChild;
      return item ? item.getBoundingClientRect().width + 20 : track.clientWidth;
    }
    function update() {
      var max = track.scrollWidth - track.clientWidth - 4;
      buttons.forEach(function (btn) {
        var dir = +btn.dataset.dir;
        btn.disabled = dir < 0 ? track.scrollLeft <= 4 : track.scrollLeft >= max;
      });
    }
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        track.scrollBy({ left: +btn.dataset.dir * step(), behavior: reducedMotion ? "auto" : "smooth" });
      });
    });
    track.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);
    update();
  });

  /* Hero slideshow: crossfade, autoplay 6 s, stops after any user interaction */
  var hero = document.querySelector("[data-hero]");
  if (!hero) return;
  var slides = hero.querySelectorAll(".hero-slide");
  var dots = hero.querySelectorAll(".hero-dots button");
  var current = 0, timer = null, stopped = false;

  function show(i) {
    current = (i + slides.length) % slides.length;
    slides.forEach(function (s, n) {
      s.classList.toggle("is-active", n === current);
      s.setAttribute("aria-hidden", n === current ? "false" : "true");
    });
    dots.forEach(function (d, n) { d.setAttribute("aria-current", n === current ? "true" : "false"); });
  }
  function pause() { clearTimeout(timer); timer = null; }
  function play() {
    if (stopped || reducedMotion || timer) return;
    timer = setTimeout(function () { timer = null; show(current + 1); play(); }, 6000);
  }
  function stop() { stopped = true; pause(); }

  hero.querySelectorAll(".car-btn[data-dir]").forEach(function (btn) {
    btn.addEventListener("click", function () { stop(); show(current + +btn.dataset.dir); });
  });
  dots.forEach(function (dot, n) {
    dot.addEventListener("click", function () { stop(); show(n); });
  });
  hero.addEventListener("mouseenter", pause);
  hero.addEventListener("mouseleave", function () { if (!stopped) play(); });
  hero.addEventListener("focusin", pause);
  hero.addEventListener("touchstart", stop, { passive: true });

  /* slides 2+ carry data-src: fetch them only after the page has loaded */
  window.addEventListener("load", function () {
    slides.forEach(function (s) {
      var img = s.querySelector("img[data-src]");
      if (img) { img.src = img.dataset.src; img.removeAttribute("data-src"); }
    });
    play();
  });
})();
