# Lenis Smooth Scroll + Full-Bleed Showcase Carousel — Design

Date: 2026-07-13
Status: approved in conversation, pending spec review

## Goal

1. Premium inertia scroll feel site-wide via Lenis.
2. Showcase (Swiper) carousels render full page width — cards slide in from
   behind the viewport edge instead of being clipped at the 1200px `.wrap`
   container.

## Context

- Static site, no build tools: `index.html` (hr), `en.html` (en),
  `css/style.css`, vendored Swiper (`js/swiper-bundle.min.js`).
- Existing native smooth scroll: `html { scroll-behavior: smooth }`.
- Horizontal scroll-snap carousels: `.car-track`.
- `prefers-reduced-motion` already disables smooth scroll (style.css ~659).
- Known separate bug (out of scope): `index.html` references `js/showcase.js`
  which does not exist on disk — Swiper init is missing from the folder.

## Part 1 — Lenis

### Approach (chosen)

Vendor Lenis locally, same pattern as Swiper. No CDN runtime dependency
(site was previously compromised; avoid third-party trust).

### Files

- `js/lenis.min.js` — vendored official Lenis 1.x minified build.
- `js/smooth-scroll.js` — init script (~15 lines):
  - If `prefers-reduced-motion: reduce` → do nothing (native scroll stays).
  - If `window.Lenis` missing (file failed to load) → do nothing.
  - Else `new Lenis({ autoRaf: true, anchors: { offset: -90 } })` —
    offset matches existing `scroll-margin-top: 90px`.
- Both HTML pages: add `<script src="js/lenis.min.js" defer>` and
  `<script src="js/smooth-scroll.js" defer>`.

### CSS

- Keep `html { scroll-behavior: smooth }` as no-JS / reduced-motion fallback.
- Add Lenis recommended snippet, including
  `.lenis.lenis-smooth { scroll-behavior: auto !important; }` so the fallback
  is overridden only while Lenis is active.

### Carousel interop

- Add `data-lenis-prevent` to each `.car-track` element (both pages) so
  native horizontal scroll-snap and touch scrolling are untouched.
- Swiper showcase is drag-based; no conflict expected — verify manually.

### Error handling

None beyond the guards above. Any failure degrades to current native
smooth scroll.

## Part 2 — Full-bleed showcase carousel

Current: `section > .wrap.showcase > .swiper`; `.showcase { overflow: hidden }`
clips slides at the 1200px wrap edge (visible mid-screen cutoff).

Change (CSS only, `css/style.css`):

- `.showcase { overflow: visible; }` (replaces `overflow: hidden`)
- `section:has(.wrap.showcase) { overflow: clip; }` — clipping moves to the
  full-width section, so slides are visible across the whole viewport and
  disappear at the screen edge; `clip` prevents a horizontal page scrollbar.

`:has()` is supported in all evergreen browsers (2023+). No HTML changes;
applies to every showcase section on both pages automatically.

## Testing (manual)

- Wheel scroll feel on desktop; touch scroll on mobile width.
- Anchor nav lands with correct 90px offset.
- `.car-track` carousels: vertical wheel over them scrolls page; horizontal
  drag/snap still native.
- Showcase Swiper: cards emerge from viewport edge both sides; no horizontal
  page scrollbar at any width; drag still works.
- OS reduced-motion enabled → Lenis inactive, native behavior intact.
- Both `index.html` and `en.html`.
