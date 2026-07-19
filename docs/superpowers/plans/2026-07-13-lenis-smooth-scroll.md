# Lenis Smooth Scroll + Full-Bleed Showcase Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Lenis inertia smooth scrolling site-wide and make the Swiper showcase carousels full-bleed so cards slide in from the viewport edge.

**Architecture:** Static two-page HTML site with vendored libraries (no build step). Lenis is vendored like Swiper and initialized by a tiny guard script; the carousel fix is CSS-only, moving the clip boundary from the 1200px `.wrap` to the full-width `section`.

**Tech Stack:** Plain HTML/CSS/JS, Lenis 1.x (vendored), existing Swiper bundle.

## Global Constraints

- No build tools, no npm, no runtime CDN dependencies — all JS vendored into `js/`.
- Every HTML change applies to BOTH `index.html` and `en.html` (identical structure, lines match).
- `prefers-reduced-motion: reduce` must fully disable Lenis (native scroll preserved).
- Keep `html { scroll-behavior: smooth }` as the no-JS fallback.
- Anchor offset is exactly `-90` (matches existing `scroll-margin-top: 90px`).
- **No git repository exists in this folder.** Skip all commit steps unless the user initializes one.
- Working directory: `C:\Users\user\Desktop\Hostel Samobor`.

---

### Task 1: Vendor Lenis and wire up smooth scrolling

**Files:**
- Create: `js/lenis.min.js` (downloaded, vendored)
- Create: `js/smooth-scroll.js`
- Modify: `index.html:15` (script tags), `index.html:132`, `index.html:188` (`data-lenis-prevent`)
- Modify: `en.html:15` (script tags), `en.html:132`, `en.html:188` (`data-lenis-prevent`)
- Modify: `css/style.css` (append Lenis snippet at end of file)

**Interfaces:**
- Consumes: nothing from other tasks.
- Produces: global `Lenis` constructor from `js/lenis.min.js`; auto-initialized instance from `js/smooth-scroll.js`. No later task depends on symbols from this one.

- [ ] **Step 1: Download Lenis (one-time vendor, not a runtime CDN)**

Run in PowerShell from the project root:

```powershell
Invoke-WebRequest -Uri "https://unpkg.com/lenis@1/dist/lenis.min.js" -OutFile "js/lenis.min.js"
(Get-Item "js/lenis.min.js").Length
```

Expected: file size roughly 20,000–40,000 bytes. If the download returns HTML (size under ~5,000 or file starts with `<!DOCTYPE`), the URL redirected wrong — use `https://cdn.jsdelivr.net/npm/lenis@1/dist/lenis.min.js` instead and re-check.

- [ ] **Step 2: Sanity-check the vendored file**

Open the first line of `js/lenis.min.js` and confirm it is minified JavaScript (starts with a comment banner or `!function`/`(()=>` style code, NOT `<!DOCTYPE html>`), and contains the string `Lenis`.

- [ ] **Step 3: Create `js/smooth-scroll.js`**

Exact content:

```js
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof Lenis === 'undefined') return;
  new Lenis({
    autoRaf: true,
    anchors: { offset: -90 }
  });
})();
```

Guards: reduced-motion users keep native scroll; if the vendored file failed to load, the site silently falls back to CSS `scroll-behavior: smooth`.

- [ ] **Step 4: Add script tags to both pages**

In `index.html`, line 15 currently reads:

```html
<script src="js/showcase.js" defer></script>
```

Insert AFTER it (keeping showcase.js line untouched):

```html
<script src="js/lenis.min.js" defer></script>
<script src="js/smooth-scroll.js" defer></script>
```

`defer` scripts execute in document order, so `lenis.min.js` runs before `smooth-scroll.js`.

Make the identical edit in `en.html` (same line, same content).

- [ ] **Step 5: Add `data-lenis-prevent` to the two `.car-track` elements per page**

`index.html:132`:

```html
<div class="car-track" role="region" aria-label="Sobe i spavaonice" tabindex="0">
```

becomes

```html
<div class="car-track" data-lenis-prevent role="region" aria-label="Sobe i spavaonice" tabindex="0">
```

`index.html:188`:

```html
<div class="car-track" role="region" aria-label="Galerija fotografija hostela" tabindex="0">
```

becomes

```html
<div class="car-track" data-lenis-prevent role="region" aria-label="Galerija fotografija hostela" tabindex="0">
```

`en.html:132` and `en.html:188` are the same elements with English `aria-label`s (`"Rooms and dormitories"`, `"Hostel photo gallery"`) — add `data-lenis-prevent` in the same position.

- [ ] **Step 6: Append Lenis CSS to `css/style.css`**

Append at the end of the file:

```css
/* ---------- Lenis smooth scroll ---------- */

html.lenis, html.lenis body { height: auto; }
.lenis.lenis-smooth { scroll-behavior: auto !important; }
.lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }
.lenis.lenis-stopped { overflow: hidden; }
```

The `!important` on `scroll-behavior: auto` is what lets the existing
`html { scroll-behavior: smooth }` (style.css:30) stay as a fallback: it only
applies while Lenis is running and has tagged `<html>` with its classes.

- [ ] **Step 7: Verify wiring in browser**

Open `index.html` in a browser (double-click or `Start-Process index.html`). Confirm:
- Mouse-wheel scrolling glides with inertia (clearly different from stepped native scroll).
- DevTools console shows no errors.
- `document.documentElement.classList` contains `lenis` (check in console).

Expected: all three true. If `lenis` class missing, check the two script tags load (Network tab) and that `smooth-scroll.js` ran.

- [ ] **Step 8: Commit** — SKIP: no git repository in this folder.

### Task 2: Full-bleed showcase carousels

**Files:**
- Modify: `css/style.css:444` (`.showcase` overflow)

**Interfaces:**
- Consumes: nothing.
- Produces: nothing consumed by other tasks.

- [ ] **Step 1: Change the clip boundary**

`css/style.css` lines 444–445 currently read:

```css
.showcase { overflow: hidden; }
.showcase .swiper { overflow: visible; }
```

Replace with:

```css
section:has(.wrap.showcase) { overflow: clip; }
.showcase { overflow: visible; }
.showcase .swiper { overflow: visible; }
```

Why: `.wrap` is a 1200px centered container, so `overflow: hidden` on
`.showcase` cuts slides off mid-screen. Moving the clip to the full-width
`section` lets slides render across the whole viewport and disappear at the
screen edge. `clip` (not `hidden`) guarantees no horizontal page scrollbar
and no accidental scroll container.

- [ ] **Step 2: Verify in browser**

Reload `index.html`, scroll to the "Što raditi u Samoboru i okolici" and the events showcase sections. Confirm:
- Cards are visible all the way to both viewport edges (partially visible cards at the edges, not cut at the 1200px container).
- No horizontal scrollbar on the page at desktop width, 700px width, and 375px width (DevTools responsive mode).
- Swiper drag and pagination dots still work (NOTE: `js/showcase.js` is missing from disk — a known separate bug. If Swiper is not initialized, slides render as a static column; the full-bleed clip change is still verifiable by checking no horizontal page scrollbar and that `.showcase` content is not clipped at 1200px. Do not fix showcase.js in this task.)

Repeat the visual check on `en.html`.

- [ ] **Step 3: Commit** — SKIP: no git repository in this folder.

### Task 3: Cross-feature manual verification

**Files:** none created or modified — verification only.

**Interfaces:**
- Consumes: everything from Tasks 1–2.
- Produces: pass/fail report to the user.

- [ ] **Step 1: Anchor navigation**

Click each header nav link on `index.html`. Expected: page glides to the section, heading lands ~90px below the viewport top (not hidden under the sticky header).

- [ ] **Step 2: `.car-track` carousels**

On the rooms carousel and photo gallery (`.car-track` sections):
- Vertical mouse-wheel with cursor OVER the carousel: page scrolls (Lenis).
- Horizontal drag / shift+wheel inside the carousel: native scroll-snap works, cards snap to start.
- Arrow buttons still page through cards.

- [ ] **Step 3: Reduced motion**

In DevTools: Rendering panel → "Emulate CSS media feature prefers-reduced-motion: reduce", then reload. Expected: `document.documentElement.classList` has NO `lenis` class; scrolling is native and instant (existing style.css:659 rules apply).

- [ ] **Step 4: Both pages**

Repeat Steps 1–3 on `en.html`. Expected: identical behavior.

- [ ] **Step 5: Report**

Report results to the user, including the pre-existing missing `js/showcase.js` bug status.
