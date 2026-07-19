/* Hostel Samobor — gallery lightbox: click a photo to view it full-screen */
(function () {
  "use strict";
  var items = document.querySelectorAll(".gallery-item");
  if (!items.length || typeof HTMLDialogElement === "undefined") return;

  var en = (document.documentElement.lang || "").indexOf("en") === 0;
  var t = en
    ? { close: "Close", prev: "Previous photo", next: "Next photo", label: "Photo gallery" }
    : { close: "Zatvori", prev: "Prethodna fotografija", next: "Sljedeća fotografija", label: "Galerija fotografija" };

  var dialog = document.createElement("dialog");
  dialog.className = "lightbox";
  dialog.setAttribute("aria-label", t.label);
  dialog.setAttribute("data-lenis-prevent", ""); /* keep Lenis from scrolling the page behind the modal */
  dialog.innerHTML =
    '<button class="lb-close" aria-label="' + t.close + '">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
    '</button>' +
    '<button class="lb-nav" data-dir="-1" aria-label="' + t.prev + '">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>' +
    '</button>' +
    '<figure class="lb-figure"><img alt=""><figcaption></figcaption></figure>' +
    '<button class="lb-nav" data-dir="1" aria-label="' + t.next + '">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>' +
    '</button>' +
    '<p class="lb-count" aria-hidden="true"></p>';
  document.body.appendChild(dialog);

  var img = dialog.querySelector("img");
  var caption = dialog.querySelector("figcaption");
  var count = dialog.querySelector(".lb-count");
  var current = 0;

  function show(i) {
    current = (i + items.length) % items.length;
    var src = items[current].querySelector("img");
    var cap = items[current].querySelector("figcaption");
    img.src = src.currentSrc || src.src;
    img.alt = src.alt;
    caption.textContent = cap ? cap.textContent : "";
    count.textContent = (current + 1) + " / " + items.length;
  }

  items.forEach(function (item, i) {
    var photo = item.querySelector("img");
    if (!photo) return;
    photo.setAttribute("role", "button");
    photo.setAttribute("tabindex", "0");
    function open() {
      show(i);
      dialog.showModal();
      document.body.style.overflow = "hidden";
    }
    photo.addEventListener("click", open);
    photo.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") { e.preventDefault(); open(); }
    });
  });

  dialog.querySelector(".lb-close").addEventListener("click", function () { dialog.close(); });
  dialog.querySelectorAll(".lb-nav").forEach(function (btn) {
    btn.addEventListener("click", function () { show(current + +btn.dataset.dir); });
  });
  dialog.addEventListener("keydown", function (e) {
    if (e.key === "ArrowLeft") show(current - 1);
    if (e.key === "ArrowRight") show(current + 1);
  });
  /* click on the backdrop (the dialog itself, not its children) closes */
  dialog.addEventListener("click", function (e) {
    if (e.target === dialog) dialog.close();
  });
  dialog.addEventListener("close", function () {
    document.body.style.overflow = "";
    img.removeAttribute("src");
  });
})();
