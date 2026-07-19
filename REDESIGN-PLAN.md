# Hostel Samobor — Redesign Plan (v2)

Decisions locked with owner 2026-07-13:
- **Colors:** evolved green + terracotta (hostel's real brand colors, richer execution)
- **Style:** photo-forward editorial (magazine layout, big imagery, asymmetric grid)
- **Carousels:** all four — hero slideshow, gallery, rooms, events/activities
- **Structure:** one page per language (`index.html` HR, `en.html` EN), content unchanged (client review pending)

---

## 1. Design tokens

### Color (light mode, WCAG AA checked)

| Token | Hex | Use |
|---|---|---|
| `--paper` | `#FAF8F3` | page background |
| `--ink` | `#26221B` | body text (13.9:1 on paper) |
| `--ink-soft` | `#5C554A` | secondary text (6.7:1) |
| `--pine` | `#2E5A2B` | primary: CTAs, links, brand (7.4:1) |
| `--pine-dark` | `#1C3A19` | footer bg, hero gradient, hover |
| `--terracotta` | `#C2571F` | eyebrows, event accents, prices (4.6:1) |
| `--sun` | `#E9A13B` | small highlights only (badges, carousel active dot on dark) — never text on light |
| `--cream` | `#F2EDE1` | alternate section bands |
| `--line` | `#E6DFD2` | borders, dividers |

Rules: semantic tokens only in components (no raw hex). `--sun` fails contrast on paper — decorative/on-dark use only. Functional states (error on form-less site: n/a) not needed.

### Typography

Keep **Fraunces** (display) + **Karla** (body) — already fits editorial direction, has latin-ext (č ć š ž đ). Push harder:

- Display: Fraunces, `clamp(2.4rem, 6vw, 4.5rem)` for hero, optical size axis on, italic for accents
- Section titles: Fraunces 600, `clamp(1.8rem, 4vw, 2.8rem)`
- Eyebrows: Karla 800, 0.78rem, letter-spacing 0.16em, uppercase, terracotta
- Body: Karla 400, 1.0625rem, line-height 1.65, max-width 65ch
- Pull quotes / big numbers: Fraunces italic (kept from v1 room numerals)
- Type scale: 12.75 / 14 / 17 / 19 / 24 / 32 / 45 / 64

### Spacing & shape

- 8px rhythm: section padding 96px desktop / 56px mobile; card padding 24px; grid gaps 16–24px
- Radius: 12px cards, 999px pills, **0** on full-bleed editorial images
- Shadows: one scale only — `0 10px 24px rgba(38,34,27,.09)` hover, `0 2px 8px rgba(38,34,27,.06)` rest
- Z-index scale: 0 / 10 (cards) / 50 (header) / 100 (lightbox if added later)

---

## 2. Page structure (both languages, same skeleton)

```
┌ Sticky header — logo + anchor nav + language pill
├ 1. HERO SLIDESHOW (full viewport-ish)
├ 2. Intro strip — one editorial sentence + 3 quick facts
├ 3. SMJEŠTAJ — asymmetric editorial split + ROOMS CAROUSEL
├ 4. GALERIJA — photo carousel (new section)
├ 5. DOGAĐANJA — EVENTS CAROUSEL (cream band)
├ 6. AKTIVNOSTI — ACTIVITIES CAROUSEL
├ 7. PRIJEVOZ — price table + photo (unchanged content)
├ 8. KONTAKT — contact cards (unchanged content)
└ Footer — pine-dark
```

### 2.1 Hero slideshow
- 3–4 slides, full-width, height `min(88vh, 720px)`, crossfade (no slide — calmer, no CLS)
- Slides: Samobor square (current hero), attic dorm, dining room, biciklizam/gorje landscape
- Bottom gradient `--pine-dark` → transparent; headline + CTA + price pill fixed (don't rotate with slides — only the photo changes)
- Auto-advance 6s; **pauses** on hover, focus, touch, and permanently after user interacts; stops entirely under `prefers-reduced-motion`
- Controls: prev/next arrows (44×44px min) + dots (labelled buttons, `aria-current`)

### 2.2 Intro strip (new, tiny)
Editorial one-liner (e.g. HR: "Mali hostel u malom kraljevskom gradu — 20 min od Zagreba.") + 3 facts with SVG icons (Lucide, stroke 2): lokacija u centru / od 19 € / Wi-Fi + kuhinja + praonica. No new claims beyond existing content — facts pulled from existing texts.

### 2.3 Smještaj — editorial split + rooms carousel
- Top: asymmetric two-column — large attic-dorm photo left (60%), shared room description + monthly rental + reservation note right, overlapping the photo edge slightly (editorial signature)
- Below: **rooms carousel** — horizontal scroll-snap strip of 4 cards; keep v1's big Fraunces numerals (2/4/6/10) but add a room photo header to each card; 19 €/noć price row
- On desktop ≥1024px all 4 cards fit → carousel controls hidden automatically (it's just a grid); carousel behavior only kicks in below that. No JS needed: CSS `scroll-snap` + small JS only for arrow buttons

### 2.4 Galerija (new)
- Uses the ~21 unused photos from `sadrzaj/20151210/*-v-*.jpg` (dorms, kitchen, dining room, stairs, common areas)
- Curate to best 10–12 (several are near-duplicates / fisheye-distorted; skip worst)
- Editorial filmstrip: horizontal scroll-snap, mixed widths (portrait ~300px, landscape ~460px), 300px tall, `object-fit: cover`
- Arrows + swipe; every image real `alt`; `loading="lazy"`; width/height attributes set (CLS = 0)

### 2.5 Događanja — events carousel
- Cream band (`--cream`)
- Horizontal card carousel: 5 event cards, photo top (16:10), "when" label in terracotta, title, **shortened teaser** (first paragraph only) + "Saznajte više" toggle (`<details>` or expand) revealing the full existing text — full content stays in the page (SEO + client review), page gets dramatically shorter
- Scroll-snap, 1.15 cards visible mobile / 3 desktop

### 2.6 Aktivnosti — same carousel component as events (different accent: pine)

### 2.7–2.8 Prijevoz + Kontakt — keep v1 layout, restyle to new tokens. Van icon image stays until client provides real photo.

---

## 3. Carousel component spec (one implementation, four uses)

Vanilla, no library. Base: CSS `scroll-snap-type: x mandatory` + `overflow-x: auto` track; JS layer (~60 lines) only adds arrows, dots, and hero autoplay.

Accessibility/UX requirements (from UI skill, priority 1–2):
- [ ] Arrows are real `<button>`s, ≥44×44px, visible always on touch devices (no hover-only)
- [ ] `aria-label` on arrows/dots ("Prethodna slika" / "Previous image"); track has `role="region"` + `aria-label`
- [ ] Keyboard: track focusable, arrow keys scroll; focus ring visible
- [ ] Swipe = native scroll (no gesture hijack); vertical page scroll never blocked
- [ ] `prefers-reduced-motion`: no autoplay, `scroll-behavior: auto`
- [ ] Cards/images have explicit aspect-ratio → zero layout shift
- [ ] `loading="lazy"` on everything below hero; hero slide 1 eager, slides 2+ lazy-loaded after load event
- [ ] Momentum feels native (browser scroll, not JS animation)

---

## 4. Image production

Sources → `novi-web/images/` (all local, no downloads):

| Use | Source | Prep |
|---|---|---|
| Hero slides ×4 | square (have), `20151210` attic dorm `-v-913`, dining `-v-14`, `27102015055347-v-biciklizam` | resize 1600w, quality 78 |
| Rooms carousel ×4 | `20151202/*-v-sobe-samobor*` + attic shots | 800w |
| Gallery ×10–12 | curated `20151210/*-v-*` | 900w landscape / 600w portrait |
| Events/activities | already copied in v1 | keep |

- Old files are ~800px JPGs — fine for cards; hero square photo is 1900px original ✔
- Batch resize with ImageMagick if present, else use as-is (they're small files already; note in build step)
- Every image: descriptive `alt` in page language, `width`/`height` attributes

2015-era interior photos are dated (fisheye, old styling) — plan flags **recommend client orders new photos**; structure won't change when they arrive, just file swaps.

---

## 5. Implementation order

1. Extend `css/style.css` tokens (add `--pine-dark` band, `--sun`, `--cream`; keep names stable)
2. Build carousel CSS/JS component once (`js/carousel.js`, ~60 lines)
3. Prep + copy images
4. Rebuild `index.html` sections in new order (content strings untouched)
5. Mirror to `en.html`
6. Verify: screenshots desktop 1280 / mobile 390 (via iframe-wrapper trick — headless min-window is ~512px), keyboard pass, reduced-motion pass, contrast spot-check
7. Owner review → client review of content

## 6. Out of scope (unchanged from v1 decisions)

- No booking form/engine (static site; tel/mailto CTAs)
- No content edits — verbatim until client pass
- Prices (19 €, transfer EUR table) still pending client confirmation
