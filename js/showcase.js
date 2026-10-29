/* Hostel Samobor — Swiper init for events & activities showcases */
(function () {
  "use strict";
  var showcases = document.querySelectorAll(".showcase .swiper");
  if (!showcases.length) return;
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function init() {
    showcases.forEach(function (el) {
      var section = el.closest("section");
      var prev = section.querySelector('.car-btn[data-dir="-1"]');
      var next = section.querySelector('.car-btn[data-dir="1"]');

      new Swiper(el, {
        slidesPerView: 1.02,
        spaceBetween: 16,
        loop: true,
        speed: reducedMotion ? 0 : 400,
        keyboard: { enabled: true },
        navigation: { prevEl: prev, nextEl: next },
        pagination: { el: el.querySelector(".swiper-pagination"), clickable: true },
        breakpoints: { 700: { slidesPerView: 1.08, spaceBetween: 20 } }
      });
    });
  }

  if (typeof Swiper !== "undefined") { init(); return; }

  /* Home pages don't ship Swiper up front (the showcases sit far below the
     hero): fetch it once the first showcase gets near the viewport. */
  function load() {
    var s = document.createElement("script");
    s.src = "js/swiper-bundle.min.js?v=3";
    s.onload = init;
    document.head.appendChild(s);
  }
  if (!("IntersectionObserver" in window)) { load(); return; }
  var io = new IntersectionObserver(function (entries) {
    if (!entries.some(function (e) { return e.isIntersecting; })) return;
    io.disconnect();
    load();
  }, { rootMargin: "800px 0px" });
  showcases.forEach(function (el) { io.observe(el); });
})();
