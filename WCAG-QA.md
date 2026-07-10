# Basalio: WCAG 2.2 QA Matrix (Beta)

One row per checklist item in `basalio-product-spec.md`, every module, with the
implementation and how it was verified. **Verified** = exercised in a real
browser (Chrome DevTools) against the actual plugin CSS/JS. **Structural** =
confirmed by inspecting the shipped CSS/DOM the plugin emits. **By design** =
guaranteed by the architecture (e.g. no `history` API is ever called).

Legend: ✅ Pass · ⚠️ Pass with caveat (see notes).

---

## Module 1: Grid Reveal

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 1.1 | Respects `prefers-reduced-motion` → crossfade becomes instant opacity swap, no slide | ✅ | `@media (prefers-reduced-motion: reduce)` sets `transition:none` + `transform:none` on scrim & caption | Structural (`grid-reveal/style.css`) |
| 1.2 | Caption reachable & visible via Tab, not hover-only (2.4.7) | ✅ | Reveal driven by `:focus-within`; control is a real `<button>` in tab order with `:focus-visible` outline | Structural |
| 1.3 | Content on hover/focus is hoverable, dismissable, persistent (1.4.13) | ✅ | Caption is a child of the hovered `<figure>` (hoverable); **Escape** sets `data-basalio-dismissed` to hide it without moving focus (dismissable); no timeout (persistent) | Structural (JS + CSS) |
| 1.4 | Overlay caption text has an enforced scrim/gradient for contrast (1.4.3) | ✅ | Gradient scrim baked into CSS; its opacity is tied to the caption, so text is never shown without it | Structural |

**Caveat (1.4):** contrast is guaranteed for the default dark scrim + white caption. If a site overrides `--basalio-scrim` to a light gradient, the site owner must re-check contrast. Documented in README.

**Beta tester checks:** Tab to a tile → caption appears with a visible focus ring; press Escape → caption hides, focus stays; enable "Reduce motion" in OS → hovering shows caption with no slide; on a phone, tap a tile → caption reveals.

---

## Module 2: Case Study Transition

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 2.1 | Focus moves to the new page's H1 after the transition completes (2.4.3) | ✅ | Source click sets a per-tab `sessionStorage` handshake; destination consumes it and focuses the first `<h1>` (`tabindex="-1"`), awaiting `pagereveal`'s `viewTransition.finished` when a transition runs | **Verified**: click-through landed focus on the destination `H1` |
| 2.2 | Fully disabled under `prefers-reduced-motion` → plain navigation | ✅ | `@view-transition { navigation:auto }` lives **inside** `@media (prefers-reduced-motion: no-preference)`; under `reduce` the opt-in is absent → plain nav. Focus management still runs (motion-independent) | **Verified**: forced `reduce`; focus still reached H1. CSSOM confirmed the gate |
| 2.3 | Transition duration capped at ≤400ms (vestibular safety) | ✅ | `::view-transition-group/old/new(*)` set to `300ms` | **Verified**: CSSOM read `300ms` |
| 2.4 | Browser back button behaves normally, no trapped history state | ✅ | Cross-document (MPA) transition is ordinary navigation; the JS never calls `history.*` | **Verified**: one Back returned to grid, focus on `body`, no stale flag · By design |

**Caveat (2.1):** the focus target is the page's first `<h1>`. If a case-study template renders no `<h1>`, focus is left unchanged (no invented target). A configurable fallback target can be added if needed.

**Caveat (silent fallback):** on browsers without cross-document View Transitions, the `<a href>` simply navigates (no error, no morph), verified via the reduced-motion path, which takes the identical no-VT code branch.

**Beta tester checks:** In Chrome/Edge, click a grid tile → hero morphs, then screen-reader focus is on the case-study title; press Back → returns to grid normally; in Firefox/Safari without support → clicking just navigates, nothing breaks; enable "Reduce motion" → plain navigation, focus still moves to the H1.

---

## Module 3: Text Reveal

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 3.1 | Real content present in the DOM & screen-reader-readable immediately, never hidden behind a JS-only reveal (1.3.1, 4.1.2) | ✅ | Text is emitted server-side into the initial HTML; reveal is **pure CSS** (no JS); only `opacity`/`transform` animate, never `display`/`visibility`/`aria-*` | **Verified**: a11y tree exposed the full heading "Design that feels considered" (level 1) with words split into spans |
| 3.2 | `prefers-reduced-motion` → content appears static and immediately, no delay | ✅ | The entrance animation is the **only** place the hidden state exists (keyframe start-frame + `fill-mode`), and it is gated by `@media (prefers-reduced-motion: no-preference)`. Under `reduce`: no animation, default `opacity:1`, no delay | **Verified**: CSSOM: animation gated by `no-preference`; **zero** standalone `opacity:0` rules |
| 3.3 | No flashing faster than 3×/second (2.3.1) | ✅ | Single monotonic fade per element/word; `animation-iteration-count:1`, nothing loops | **Verified**: computed `animation-iteration-count: 1` |

**Beta tester checks:** Turn on a screen reader and load the page → the full headline/subhead is announced at once, even mid-animation; enable "Reduce motion" → text is fully visible instantly with no fade or delay; watch the animation → each word/line fades in once (no blinking or repetition).

---

## How a screen reader encounters Module 3 (before / during / after)

- **Before (initial load):** The server sends real HTML, e.g. `<h1><span>Design</span> <span>that</span> …</h1>`. The accessibility tree is built from this DOM. The CSS start-frame is `opacity:0`, but **opacity does not remove nodes from the accessibility tree**. A screen reader navigating by heading immediately hears *"Design that feels considered, heading level 1"*, verified: the a11y snapshot shows one heading with the complete name.
- **During (animation running):** Only `opacity` and `transform` change. No DOM mutation, no `display`/`visibility`/`aria` change. The accessibility tree is byte-for-byte identical, so assistive tech perceives **no change**. The full text was, and remains, available.
- **After (at rest):** Identical DOM, now visually at `opacity:1`. Nothing changes for assistive tech.

The animation is effectively invisible to assistive technology; the reveal is a sighted-user visual affordance only.

---

## Cross-module architecture notes relevant to QA

- **Assets load only where used.** `basalio-core.js` is enqueued from a block's `render.php`, which only runs when that block is on the page, never site-wide. Module 3 needs no JS, so it doesn't enqueue the core at all.
- **Modules 1 & 3 need no `history`/network APIs.** Only Module 2 uses View Transitions, and it uses the cross-document form (real navigation), so history/back behavior is native.
- **Reduced-motion is honored in all three**, each via `@media (prefers-reduced-motion: …)` in the module's own stylesheet. There is no global motion kill-switch to misconfigure.
