# Carousel Redesign (Swiper + detail pages) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace events/activities carousels on `index.html`/`en.html` with Swiper full-width image-background cards (clamped excerpt + "view more"), and move full text to 4 new detail pages.

**Architecture:** Self-hosted Swiper 11 drives the two showcase carousels via new `js/showcase.js`; existing `js/carousel.js` keeps hero + gallery. Full item text migrates verbatim from current cards/`<details>` into `dogadanja.html`, `aktivnosti.html` (HR) and `events.html`, `activities.html` (EN), anchored per item.

**Tech Stack:** Static HTML/CSS/vanilla JS, Swiper 11 (self-hosted bundle).

## Global Constraints

- No CDN/runtime external requests (fonts already external — leave as-is; add nothing new).
- No build step; plain files.
- Bilingual parity: every UI string exists in HR and EN versions.
- Design tokens from `css/style.css` `:root` — `--paper #FAF8F3`, `--ink #26221B`, `--pine #2E5A2B`, `--terracotta #C2571F`, `--cream #F2EDE1`, `--radius-card 12px`, fonts Fraunces (display) / Karla (body).
- Anchor slugs (same in both languages): `fasnik`, `bitka`, `greblica`, `dan-grada`, `salamijada`, `planinarenje`, `biciklizam`, `jahanje`, `motori`, `strelicarstvo`.
- No git repo in this project — skip all commit steps.
- Keep `loading="lazy"`, `width`/`height`, meaningful `alt` on all images.

---

### Task 1: Vendor Swiper 11

**Files:**
- Create: `js/swiper-bundle.min.js`
- Create: `css/swiper-bundle.min.css`

**Interfaces:**
- Produces: global `Swiper` constructor + `.swiper/.swiper-wrapper/.swiper-slide` CSS used by Tasks 2–4.

- [ ] **Step 1: Download bundle files**

```powershell
Invoke-WebRequest "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" -OutFile "js/swiper-bundle.min.js"
Invoke-WebRequest "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" -OutFile "css/swiper-bundle.min.css"
```

- [ ] **Step 2: Verify** — JS > 100 KB, CSS > 10 KB, JS contains `Swiper`, no HTML error page inside.

### Task 2: Showcase CSS

**Files:**
- Modify: `css/style.css` (append new section after `.content-card` rules, ~line 492)

**Interfaces:**
- Produces: classes `.showcase`, `.show-card`, `.show-overlay`, `.excerpt`, `.btn-more` consumed by Tasks 3–4.

- [ ] **Step 1: Append CSS**

```css
/* ---- Showcase carousels (events & activities) ---- */
.showcase { overflow: hidden; }
.showcase .swiper { overflow: visible; }

.show-card {
  position: relative;
  height: min(70vh, 560px);
  border-radius: var(--radius-card);
  overflow: hidden;
  box-shadow: var(--shadow-rest);
}
.show-card > img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
}
.show-overlay {
  position: absolute; inset: auto 0 0 0;
  padding: 96px 32px 32px;
  background: linear-gradient(transparent, rgba(20, 16, 10, .82));
  color: #fff;
}
.show-overlay .when {
  font-size: .8rem; font-weight: 800; letter-spacing: .08em;
  text-transform: uppercase; color: var(--sun); margin: 0 0 6px;
}
.show-overlay h3 {
  font-family: var(--font-display);
  font-size: clamp(1.4rem, 3vw, 2rem);
  margin: 0 0 8px; color: #fff;
}
.show-overlay .excerpt {
  max-width: 60ch; margin: 0 0 16px;
  color: rgba(255, 255, 255, .88);
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.btn-more {
  display: inline-block; padding: 10px 22px;
  background: var(--terracotta); color: #fff;
  border-radius: var(--radius-pill);
  font-weight: 700; text-decoration: none;
}
.btn-more:hover { background: #A64818; color: #fff; }

.showcase .swiper-pagination-bullet { background: var(--ink-soft); opacity: .4; }
.showcase .swiper-pagination-bullet-active { background: var(--pine); opacity: 1; }
.showcase .swiper-pagination { position: static; margin-top: 18px; }

@media (max-width: 700px) {
  .show-card { height: min(60vh, 420px); }
  .show-overlay { padding: 72px 20px 20px; }
  .show-overlay .excerpt { -webkit-line-clamp: 3; }
}
```

- [ ] **Step 2: Verify** — open `index.html` (before markup change nothing visual yet); CSS parses (no syntax errors in DevTools).

### Task 3: Rebuild sections on index.html + en.html

**Files:**
- Modify: `index.html` — `<head>` (add swiper css before style.css, swiper js + showcase.js defer after carousel.js), sections `#dogadanja` (lines ~230–318) and `#aktivnosti` (~320–395)
- Modify: `en.html` — same for `#events` (~230–309) and `#activities` (~311–376)

**Interfaces:**
- Consumes: classes from Task 2; `Swiper` from Task 1.
- Produces: DOM structure `section > .wrap.showcase > .swiper > .swiper-wrapper > .swiper-slide` + `.car-btn[data-dir]` inside `.car-head` + `.swiper-pagination` div — consumed by Task 4 (`js/showcase.js` queries `.showcase .swiper`).
- Produces: links `dogadanja.html#<slug>` etc. — targets created in Tasks 5–6.

- [ ] **Step 1: head includes (both files)**

```html
<link rel="stylesheet" href="css/swiper-bundle.min.css">   <!-- before style.css -->
<script src="js/swiper-bundle.min.js" defer></script>       <!-- after carousel.js -->
<script src="js/showcase.js" defer></script>
```

- [ ] **Step 2: Replace carousel markup.** Keep `car-head` (eyebrow/title/intro/arrow buttons) unchanged. Replace `div.wrap.carousel[data-carousel]` block with (pattern; repeat per item, HR events example):

```html
<div class="wrap showcase">
  <div class="swiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide">
        <article class="show-card">
          <img src="images/event-fasnik.jpg" width="800" height="533" alt="Maskirani sudionici Samoborskog fašnika" loading="lazy">
          <div class="show-overlay">
            <p class="when">Kraj veljače</p>
            <h3>Samoborski fašnik</h3>
            <p class="excerpt">[first paragraph of existing card, verbatim]</p>
            <a class="btn-more" href="dogadanja.html#fasnik">Saznajte više</a>
          </div>
        </article>
      </div>
      <!-- … remaining 4 items -->
    </div>
    <div class="swiper-pagination"></div>
  </div>
</div>
```

Item map (image / slug / HR link / EN link):
| img | slug | HR page | EN page |
|---|---|---|---|
| event-fasnik | fasnik | dogadanja.html | events.html |
| event-bitka | bitka | dogadanja.html | events.html |
| event-greblica | greblica | dogadanja.html | events.html |
| event-dan-grada | dan-grada | dogadanja.html | events.html |
| event-salamijada | salamijada | dogadanja.html | events.html |
| akt-planinarenje | planinarenje | aktivnosti.html | activities.html |
| akt-biciklizam | biciklizam | aktivnosti.html | activities.html |
| akt-jahanje | jahanje | aktivnosti.html | activities.html |
| akt-motori | motori | aktivnosti.html | activities.html |
| akt-strelicarstvo | strelicarstvo | aktivnosti.html | activities.html |

Activities cards have no `.when` — omit that `<p>`. EN button text: "View more". Excerpt = existing first `<p>` copied unchanged (CSS clamps it). `<details>` content NOT copied here (goes to detail pages).

- [ ] **Step 3: Verify** — both pages: cards render image-background, no leftover `data-carousel` in those two sections, hero + gallery untouched.

### Task 4: js/showcase.js

**Files:**
- Create: `js/showcase.js`

**Interfaces:**
- Consumes: `Swiper` global; DOM from Task 3 (`section` containing `.showcase .swiper`, `.car-head .car-btn[data-dir]`).

- [ ] **Step 1: Write**

```js
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
```

- [ ] **Step 2: Verify** — arrows, swipe, dots, keyboard, loop work on both sections, both pages; hero slideshow unaffected; `.car-btn:disabled` styling not stuck (Swiper toggles `swiper-button-disabled`, not `disabled` — with loop no disabled state needed).

### Task 5: HR detail pages

**Files:**
- Create: `dogadanja.html`, `aktivnosti.html`

**Interfaces:**
- Consumes: anchor slugs from Task 3 links.

- [ ] **Step 1: Build pages.** Copy `<head>` + header + footer from `index.html`. Adjust:
  - `<title>`: "Događanja u Samoboru — Hostel Samobor" / "Aktivnosti u Samoboru — Hostel Samobor"; matching meta description.
  - Nav links → `index.html#smjestaj` etc. (absolute to index); brand `href="index.html"`.
  - Lang switch → `events.html` / `activities.html`.
  - Drop hero, drop carousel scripts (no swiper/carousel/showcase JS needed — plain page).
  - Body: intro block (eyebrow + h1 + back link `← Natrag na naslovnicu` → `index.html#dogadanja` / `#aktivnosti`), then per item:

```html
<section id="fasnik" class="detail-item">
  <div class="wrap">
    <img src="images/event-fasnik.jpg" width="800" height="533" alt="…" loading="lazy">
    <p class="when">Kraj veljače</p>
    <h2>Samoborski fašnik</h2>
    <p>…full text…</p>
  </div>
</section>
```

  Full text = current intro `<p>` + all `<details>` paragraphs from `index.html` (source lines: fašnik 255+258, bitka 268+271–272, greblica 282+285–286, dan grada 296+299, salamijada 309+312; planinarenje 344+347–349, biciklizam 358, jahanje 366+369, motori 378+381, streličarstvo 390). Verbatim, no rewrites.

- [ ] **Step 2: Add `.detail-item` CSS to `style.css`** (simple: wrap max-width ~800px, img rounded `var(--radius-card)`, `scroll-margin-top` for sticky header, alternate `band-cream`).

- [ ] **Step 3: Verify** — every anchor id present once; text complete vs. source; nav/lang/back links resolve.

### Task 6: EN detail pages

**Files:**
- Create: `events.html`, `activities.html`

Same as Task 5 with `en.html` as source (header/footer/nav → `en.html#…`, lang switch → `dogadanja.html`/`aktivnosti.html`, `lang="en"`). Titles: "Events in Samobor — Hostel Samobor" / "Things to do in Samobor — Hostel Samobor". Back link "← Back to homepage". Text sources in `en.html`: carnival 255+258, battle 268+271, greblica 281+284–285, town day 295, salami 304; hiking 335, cycling 343, riding 351, motorcycle 359+362, archery 371.

- [ ] Build, verify same checks as Task 5.

### Task 7: Cleanup + full verification

- [ ] `.content-card`/`acts-carousel` CSS: keep `.content-card` only if still used elsewhere (check `rooms-carousel` / gallery usage first); delete `.acts-carousel .content-card .when` rule if `.acts-carousel` gone from HTML.
- [ ] Link check (PowerShell): every `href="…#slug"` in index/en has matching `id="slug"` in target file — all 20.
- [ ] Browser pass per spec Testing section: swipe/arrows/dots/keyboard/loop, mobile 375px, reduced motion, console clean, no new external requests.
