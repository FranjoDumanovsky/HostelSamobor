(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof Lenis === 'undefined') return;
  new Lenis({
    autoRaf: true,
    anchors: { offset: -90 }
  });
})();
