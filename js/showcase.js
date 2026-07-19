/* Hostel Samobor — Swiper init for events & activities showcases */
(function () {
  "use strict";
  if (typeof Swiper === "undefined") return;
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.querySelectorAll(".showcase .swiper").forEach(function (el) {
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
})();
