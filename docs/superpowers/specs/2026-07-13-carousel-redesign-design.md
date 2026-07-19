# Carousel Redesign — Događanja & Aktivnosti (Swiper + detail pages)

Date: 2026-07-13
Status: approved by user (library, page structure, layout chosen via Q&A)

## Goal

Replace the events (`#dogadanja` / `#events`) and activities (`#aktivnosti` / `#activities`) carousels on `index.html` and `en.html` with a Swiper-powered full-width card carousel. Each card uses its image as the background with a text overlay showing a truncated excerpt. "Saznajte više / View more" links to new per-section detail pages containing the full text.

Out of scope: hero slideshow and gallery carousel keep the existing custom scroll-snap implementation in `js/carousel.js`. Transport, rooms, contact sections untouched.

## Constraints

- Static site, no build step, no CDN — Swiper self-hosted.
- Bilingual: every change lands in both HR and EN.
- Keep lazy-loading and alt text on images; keep keyboard accessibility at least as good as current (`car-track` is focusable today).

## Components

### 1. Swiper (self-hosted)

- Files: `js/swiper-bundle.min.js`, `css/swiper-bundle.min.css` (Swiper 11.x), loaded on `index.html` and `en.html` only.
- Init in a new `js/showcase.js` (keeps `carousel.js` untouched), one Swiper instance per section:
  - `slidesPerView: 1.08`, `spaceBetween: 20` (peek of next slide), mobile `slidesPerView: 1.02`.
  - `loop: true`, `keyboard: { enabled: true }`, a11y module (bundle includes it).
  - `navigation`: reuse existing `.car-btn` prev/next buttons (pass elements per instance).
  - `pagination`: dots, clickable.
  - No autoplay.
  - Respect `prefers-reduced-motion`: set `speed: 0` (or `allowTouchMove` stays, just no animated transition).

### 2. Card design (both sections, both languages)

Markup per slide:

```html
<div class="swiper-slide">
  <article class="show-card">
    <img src="images/event-fasnik.jpg" alt="…" loading="lazy" width="800" height="533">
    <div class="show-overlay">
      <p class="when">Kraj veljače</p>
      <h3>Samoborski fašnik</h3>
      <p class="excerpt">Svake godine krajem mjeseca veljače…</p>
      <a class="btn-more" href="dogadanja.html#fasnik">Saznajte više →</a>
    </div>
  </article>
</div>
```

CSS (in `style.css`, new section):

- `.show-card`: relative, `height: min(70vh, 560px)` desktop / `min(60vh, 420px)` ≤700px, `border-radius` matching existing cards, overflow hidden.
- `img`: absolute inset 0, `object-fit: cover`.
- `.show-overlay`: absolute bottom, dark gradient (`linear-gradient(transparent, rgba(0,0,0,.75))`), padding; text white.
- `.excerpt`: `-webkit-line-clamp: 2` (3 on mobile if room), `overflow: hidden`.
- `.btn-more`: styled link/button consistent with site accent color.
- Existing `.content-card` / `<details>` markup in these two sections is removed. `<details>` full text migrates to detail pages (see below). If `.content-card` styles become unused elsewhere, leave the CSS (gallery/rooms may share) — only delete rules verified unused.

### 3. Detail pages (4 new files)

| Page | Language | Contains |
|---|---|---|
| `dogadanja.html` | HR | 5 events, full text |
| `aktivnosti.html` | HR | 5 activities, full text |
| `events.html` | EN | 5 events, full text |
| `activities.html` | EN | 5 activities, full text |

- Same `<head>` (meta, css), header/nav, footer as parent page. Nav section links point back to `index.html#…` / `en.html#…`.
- Lang switcher cross-links: `dogadanja.html ↔ events.html`, `aktivnosti.html ↔ activities.html`, with `hreflang`.
- Each item: `<section id="…">` with anchor slug (`#fasnik`, `#bitka`, `#greblica`, `#dan-grada`, `#salamijada`; `#planinarenje`, `#biciklizam`, `#jahanje`, `#motori`, `#strelicarstvo` — EN pages use same slugs so links are symmetric), image (smaller, side or top), full text = current card paragraph + former `<details>` paragraphs merged. No text is lost.
- "Natrag / Back" link to `index.html#dogadanja` etc.
- EN pages: text taken from existing `en.html` cards (same structure assumed; verify during implementation).

### 4. Excerpts

Excerpt = first sentence(s) of existing intro paragraph, cut by CSS line-clamp (keep full first paragraph in markup; clamp handles truncation). No manual text rewriting needed.

## Error handling / degradation

- No JS: Swiper markup renders as stacked block slides (`.swiper-wrapper` children full width) — content readable, links work.
- Missing anchor: links plain `#id`; if slug typo, page still loads at top — verify all 20 links (10 items × 2 languages) during implementation.

## Testing

- Open `index.html` and `en.html` locally: both carousels swipe, arrows work, dots work, keyboard arrows work, loop works.
- Every "Saznajte više / View more" lands on correct page + anchor (20 links).
- Lang switchers on all 6 pages point to correct counterpart.
- Mobile viewport (~375px): card readable, overlay text not clipped, touch swipe works.
- `prefers-reduced-motion`: no animated slide transitions.
- No console errors; no external network requests (self-hosted check).
