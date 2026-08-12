# Basalio: WCAG 2.2 QA Matrix

One row per accessibility requirement, every module, with the implementation and
how it was confirmed.

## Legend

| Mark | Meaning |
|---|---|
| ✅ **Verified** | Exercised in a real browser against the running plugin. |
| ✔︎ **Asserted** | Machine-checked against rendered output by `tests/verify-fixes.php`. Repeatable, re-run on every change. |
| ▫︎ **Structural** | Confirmed by reading the CSS, JS, or PHP the plugin ships. A claim, not a proof. |
| ◆ **By design** | Guaranteed by the architecture (e.g. no `history` API is ever called). |
| 🔬 | Needs browser verification. Not yet done. |
| ❌ | Known defect, unfixed. |
| ⚠️ | Passes with a caveat worth reading. |

**Asserted is not Verified.** The assertion suite renders blocks server-side and
inspects the HTML. It cannot exercise JavaScript at runtime, so nothing about
screen-reader announcement timing, focus behaviour, or the accessibility tree is
proven by a green run. Those rows stay 🔬 until someone opens a browser.

## Running the suite

```bash
php tests/make-verify-pages.php    # build fixtures (idempotent)
php tests/verify-fixes.php         # 27 assertions, exit 1 on any failure
```

Both accept an optional path to a WordPress install, or read `WP_ROOT`.
Default is `../../local-dev-site`.

## Module numbering

This document is authoritative. The comments in `basalio-core.js` use a
different, inconsistent numbering and need reconciling (see CC-3).

1 Grid Reveal · 2 Case Study Transition · 3 Text Reveal · 4 Magnetic Button ·
5 Scroll Sequence · 6 Before / After · 7 Pinned Scroll · 8 Filterable Grid
(+ Grid Item) · 9 Custom Cursor · plus the shared Lightbox.

---

## Module 1: Grid Reveal

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 1.1 | Respects `prefers-reduced-motion`: crossfade becomes an instant opacity swap, no slide | ✅ | `@media (prefers-reduced-motion: reduce)` sets `transition:none` + `transform:none` on scrim and caption | ▫︎ (`grid-reveal/style.css`) |
| 1.2 | Caption reachable and visible via Tab, not hover-only (2.4.7) | ✅ | Reveal driven by `:focus-within`; the control is a real `<button>` in tab order with `:focus-visible` outline | ▫︎ |
| 1.3 | Content on hover/focus is hoverable, dismissable, persistent (1.4.13) | ✅ | Caption is a child of the hovered `<figure>` (hoverable); Escape sets `data-basalio-dismissed` without moving focus (dismissable); no timeout (persistent) | ▫︎ (JS + CSS) |
| 1.4 | Overlay caption text has an enforced scrim for contrast (1.4.3) | ⚠️ | Gradient scrim baked into CSS, opacity tied to the caption, so text is never shown without it | ▫︎ |

**Caveat (1.4):** contrast is guaranteed for the default dark scrim with white
caption. A site overriding `--basalio-scrim` to a light gradient must re-check
contrast itself.

**Tester checks:** Tab to a tile, caption appears with a visible focus ring;
Escape hides it, focus stays; with OS "Reduce motion" on, hovering shows the
caption with no slide; on a phone, tap reveals.

---

## Module 2: Case Study Transition

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 2.1 | Focus moves to the destination `<h1>` after the transition (2.4.3) | ✅ | Per-tab `sessionStorage` handshake; destination focuses the first `<h1>` with `tabindex="-1"`, awaiting `pagereveal`'s `viewTransition.finished` | ✅ Verified: click-through landed focus on the destination H1 |
| 2.2 | Fully disabled under `prefers-reduced-motion`, plain navigation | ✅ | `@view-transition { navigation:auto }` lives inside `@media (prefers-reduced-motion: no-preference)`. Focus management still runs | ✅ Verified: forced `reduce`, focus still reached H1 |
| 2.3 | Transition duration capped at ≤400ms (vestibular safety) | ❌ | **Open defect. See below.** | ▫︎ (`case-study-transition/render.php`) |
| 2.4 | Back button behaves normally, no trapped history state | ✅ | Cross-document (MPA) transition is ordinary navigation; the JS never calls `history.*` | ✅ Verified · ◆ |

### ❌ Open defect 2.3 — this document contradicts the code

The 0.5.0 "adjustable animation speed" pass raised `transitionDuration` to a
default of **500ms** with a permitted range of **100–3000ms**:

```php
$basalio_dur = ... max( 100, min( 3000, absint( ... ) ) ) : 500;
```

The row above previously asserted a verified 300ms cap. That guarantee no longer
holds. **A decision is required before this row can be closed:**

1. Clamp to `min( 400, … )`, default back to 300. Keeps the guarantee, loses the
   configurability just shipped.
2. Keep the range, and restate the row as "author-configurable, values above
   400ms are the site owner's responsibility." Honest, weaker claim.
3. Keep the range, warn in the editor panel above 400ms, and document that.
   Preserves the promise and the flexibility.

Related: the demo video production spec assumes 300ms. If the default stays at
500ms the videos will not match the shipped product.

**Caveat (2.1):** the focus target is the page's first `<h1>`. A case-study
template with no `<h1>` leaves focus unchanged; no target is invented.

**Caveat (fallback):** on browsers without cross-document View Transitions the
`<a href>` simply navigates. No error, no morph.

---

## Module 3: Text Reveal

Ships no JavaScript. The reveal is pure CSS.

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 3.1 | Real content in the DOM and screen-reader-readable immediately (1.3.1, 4.1.2) | ✅ | Text emitted server-side; only `opacity`/`transform` animate, never `display`/`visibility`/`aria-*` | ✅ Verified: a11y tree exposed the full heading |
| 3.2 | `prefers-reduced-motion`: content appears static and immediately | ✅ | The hidden state exists only inside the entrance keyframe, gated by `no-preference`. Under `reduce` there is no animation and no delay | ✅ Verified: zero standalone `opacity:0` rules |
| 3.3 | No flashing faster than 3×/second (2.3.1) | ✅ | Single monotonic fade, `animation-iteration-count:1` | ✅ Verified |

### How a screen reader encounters Module 3

- **Initial load:** the server sends real HTML. The CSS start-frame is
  `opacity:0`, but opacity does not remove nodes from the accessibility tree, so
  the heading is announced in full immediately.
- **During:** only `opacity` and `transform` change. No DOM mutation. The
  accessibility tree is byte-for-byte identical, so assistive tech perceives no
  change.
- **At rest:** identical DOM at `opacity:1`.

The animation is effectively invisible to assistive technology: a sighted-user
affordance only.

---

## Module 4: Magnetic Button

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 4.1 | Disabled under `prefers-reduced-motion` (2.3.3) | ✔︎ | Behavior returns early before binding any listener | ▫︎ |
| 4.2 | Disabled on touch / coarse pointers | ✔︎ | Returns early unless `(hover: hover) and (pointer: fine)` | ▫︎ |
| 4.3 | Remains a real, keyboard-operable control (2.1.1) | ✔︎ | Renders `<a href>` when a URL is set; the behavior only writes two CSS custom properties and never touches semantics | ▫︎ |
| 4.4 | No fake control when no URL is set | ✔︎ | Falls back to `<span>`, not a `<div role="button">` or an href-less `<a>`. Nothing unusable enters the tab order | ▫︎ |
| 4.5 | Displacement resets on pointer leave and blur | ✔︎ | `reset()` bound to `pointerleave` and `blur` | ▫︎ |
| 4.6 | Displacement cannot move the control out from under the pointer (2.5.8) | 🔬 | `strength` clamped `max( 4, min( 40, … ) )` with an inline vestibular-comfort note | Test at `strength = 40` on the smallest allowed button |
| 4.7 | New-tab links are announced as such (3.2.5) | ⚠️ | `target="_blank"` is paired with `rel="noopener noreferrer"`, the security half. No visible or screen-reader-only new-window indication | ▫︎ |
| 4.8 | Accessible name comes from visible text (2.5.3) | ✔︎ | Name is the label span's text; no competing `aria-label` to desync | ▫︎ |

---

## Module 5: Scroll Sequence

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 5.1 | Usable without scrolling or a pointing device (2.5.1, 2.1.1) | ✔︎ | Real Previous/Next buttons. Under `reduce` the scroll driver is skipped and the buttons are the only driver | ▫︎ |
| 5.2 | Controls have accessible names (4.1.2) | ✔︎ | Translatable `aria-label`s, not reliant on the arrow glyphs | ▫︎ |
| 5.3 | Frame position exposed to AT (4.1.3) | ✔︎ | A dedicated `role="status"` announcer, separate from the visible counter | Asserted ×4 |
| 5.4 | Correct initial state with JS off | ✔︎ | Frame 0 gets `is-active` server-side; the counter renders at `{current}=1` | ▫︎ |
| 5.5 | First frame not lazy-loaded | ✔︎ | `loading="eager"` on index 0 only | ▫︎ |
| 5.6 | Live region does not spam assistive technology (4.1.3) | 🔬 | **Fixed.** `show( i, announce )` writes the live region only when `announce` is true, i.e. from the buttons. The scroll driver calls `show( i )` with no flag | Asserted: announcer empty on load, visible counter carries no live semantics, scroll driver call signature guarded |
| 5.7 | Per-frame `alt` is honored (1.1.1) | ✔︎ | **Fixed.** The attachment path now applies the author's alt override, matching every other image module | Asserted: frame 3 renders the override, not the attachment alt |
| 5.8 | Inactive frames are not announced (1.3.1) | 🔬 | **Fixed.** Inactive frames set `visibility: hidden`, which removes them from the accessibility tree. Layout is preserved, so the first frame still sets stage height | Asserted on the CSS |
| 5.9 | Pagination format is translatable | ✔︎ | **Fixed.** The `block.json` default was emptied so the PHP `__()` fallback is reachable | Asserted on both the JSON and the rendered counter |

**Why 5.6 and 5.8 remain 🔬:** the markup and CSS are asserted correct, but
whether VoiceOver actually stays silent during scroll, and whether the
accessibility tree really shows one image rather than six, can only be confirmed
in a browser. Empty live regions have real cross-screen-reader quirks on first
population.

**Tester checks:** with a screen reader running, scroll slowly through the
sequence and count announcements (expect zero); press Next once (expect exactly
one); open the DevTools accessibility pane and count images in the sequence
(expect one).

---

## Module 6: Before / After

The strongest module in the plugin. The handle is a complete ARIA slider, not a
draggable `div` with ARIA sprinkled on.

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 6.1 | Divider operable by keyboard, not pointer-only (2.1.1) | ✔︎ | `role="slider"` + `tabindex="0"`; keydown handles Arrows (±`data-basalio-step`, default 2), PageUp/PageDown (±10), Home (0), End (100) | ▫︎ |
| 6.2 | Correct value semantics exposed (4.1.2) | ⚠️ | Server emits `aria-valuemin/max/now/valuetext` and `aria-orientation`; JS keeps `aria-valuenow` and `aria-valuetext` in sync | ▫︎ |
| 6.3 | Slider has an accessible name (4.1.2) | ✔︎ | `aria-label` composed via `sprintf( __( 'Reveal %1$s over %2$s', 'basalio' ) )` from the author's own labels | ▫︎ |
| 6.4 | Decorative chrome hidden from AT (1.3.1) | ✔︎ | Divider and handle icon both carry `aria-hidden="true"` | ▫︎ |
| 6.5 | Grabbing by pointer leaves it keyboard-operable (2.1.1) | ✔︎ | `pointerdown` calls `preventDefault()` to stop text selection, which suppresses native focus, so the handler calls `handle.focus()` explicitly | ▫︎ (the 0.4.1 fix) |
| 6.6 | Content survives with JS disabled | ✔︎ | Both images render server-side; `--basalio-ba-pos` set inline from `startPosition` | ▫︎ |
| 6.7 | Both images carry meaningful `alt` (1.1.1) | ✔︎ | Alt override applied on the attachment path, `esc_attr()` on the URL fallback | ▫︎ |
| 6.8 | No motion violation under `prefers-reduced-motion` (2.3.3) | 🔬 | Divider position is user-driven, not auto-animating, so there is likely nothing to gate. Confirm no `transition` on the position or handle | Pending `before-after/style.css` read |

**Caveat (6.2):** `aria-valuetext` is a bare percentage. Valid but thin.
`"63% — Before"` would tell a screen-reader user what the number means.

---

## Module 7: Pinned Scroll

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 7.1 | No scroll hijacking, focus never trapped (2.1.2) | ✔︎ | Pure `position: sticky`. No scroll listener, no `scrollTo`, everything stays in flow | ◆ |
| 7.2 | Degrades to a stacked layout under `prefers-reduced-motion` (2.3.3) | ✔︎ | `@media (prefers-reduced-motion: reduce)` sets `position: static` | ▫︎ |
| 7.3 | Reflows to one column on narrow viewports (1.4.10) | ✔︎ | `@media (max-width: 781px)`: single column, sticky dropped, `order` reset | ▫︎ |
| 7.4 | Author `alt` honored (1.1.1) | ✔︎ | Override applied on both the attachment and URL paths | ▫︎ |
| 7.5 | Media/story split cannot produce an unreadable column | ✔︎ | `mediaWidth` clamped 25–75 | ▫︎ |
| 7.6 | `mediaPosition: right` does not break reading order (1.3.2, 2.4.3) | ⚠️ | **Open issue.** DOM order is always media → story; `order: 2` moves the media visually, so tab order reaches an interactive image before the story text it visually follows | ▫︎ |
| 7.7 | Lightbox affordance actually works | ✔︎ | **Fixed.** See CC-5 | Asserted: core script and style both enqueued with Pinned Scroll alone on the page |

**Open issue 7.6.** For a purely illustrative image the order mismatch is minor.
It becomes a real 2.4.3 problem when the image is a link or lightbox trigger.
The narrow-viewport rule already resets `order: 0`, so the pattern is understood.
Consider reordering in the DOM and using `order` only for the left variant.
Fixture page: `verify-pinned-right`.

---

## Module 8: Filterable Grid (+ Grid Item)

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 8.1 | Filters are real, keyboard-operable controls (2.1.1) | ✔︎ | Server renders `<button type="button">`; Enter/Space are native | ▫︎ |
| 8.2 | Pressed state exposed (4.1.2) | ✔︎ | `aria-pressed` rendered server-side and rewritten across all filters on change | ▫︎ |
| 8.3 | Filter group uses the WAI-ARIA toolbar pattern (2.4.3) | ✔︎ | `role="toolbar"`, `aria-orientation`, translatable `aria-label`; roving tabindex, Arrow keys with wraparound, Home/End | ▫︎ |
| 8.4 | Hidden items leave the tab order and a11y tree (1.3.1, 2.4.3) | ✔︎ | `item.hidden = !match` uses the `hidden` property, not an opacity trick | ◆ |
| 8.5 | Result count is announced (4.1.3) | ✔︎ | `role="status"` on the count paragraph, rendered server-side with real initial text | ▫︎ |
| 8.6 | Works with JS disabled | ✔︎ | All items render visible; filtering is pure enhancement | ◆ |
| 8.7 | Count message is translatable | ✔︎ | **Fixed.** See CC-1 | Asserted: `_n()` in PHP, no hardcoded English left in JS |
| 8.8 | Grid Item cannot be inserted standalone | ✔︎ | `"parent"` + `"reusable": false` | ▫︎ |
| 8.9 | Lightbox trigger has an accessible name (4.1.2) | ✔︎ | Translatable `esc_attr__( 'Expand image' )` | ▫︎ |
| 8.10 | Linked item always has an accessible name (2.4.4, 4.1.2) | ✔︎ | **Fixed.** Falls back to title, then category, and only when the rendered markup has no alt | Asserted, including that items with good alt are left alone |
| 8.11 | Uncategorised items behave predictably when filtering | ⚠️ | An item with no category matches no filter slug: it appears under All and vanishes under every other filter, with no explanation to the visitor | Asserted (behaviour confirmed, acceptability is a product call) |

---

## Module 9: Custom Cursor

Clean. Every requirement passes.

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| 9.1 | Disabled under `prefers-reduced-motion` (2.3.3) | ✔︎ | Belt and braces: JS returns early before binding, and CSS sets `display: none` on the accent. Either alone would suffice | ▫︎ |
| 9.2 | Disabled on touch / coarse pointers | ✔︎ | Early return unless `(hover: hover) and (pointer: fine)` | ▫︎ |
| 9.3 | Accent never blocks a click target (2.5.1) | ✔︎ | `pointer-events: none` | ▫︎ |
| 9.4 | Accent hidden from assistive technology (1.3.1) | ✔︎ | `aria-hidden="true"` on the accent span | ▫︎ |
| 9.5 | Native cursor never hidden or replaced (2.4.7) | ✔︎ | No `cursor:` declaration anywhere in the module stylesheet; the accent is purely additive | ▫︎ |
| 9.6 | Accent appears only on real pointer movement | ✔︎ | `opacity: 0` by default; `.is-cursor-active` raises it | ▫︎ |
| 9.7 | Colour input sanitised | ✔︎ | `sanitize_hex_color()` with a hardcoded fallback | ▫︎ |

**Not accessibility, flagged for brand review:** the fallback colour is
`#C6F135` in both `render.php` and the CSS defaults. The documented Basalio brand
chartreuse is `#DFFF00`. One of the two is wrong.

---

## Shared: Lightbox

Enabled on five blocks (Grid Reveal, Case Study Transition, Grid Item, Pinned
Scroll, and any image block with `lightbox: true`). A modal dialog is the
highest-risk surface in the plugin.

| # | Requirement | Status | How it's met | Evidence |
|---|---|---|---|---|
| L.1 | Modal semantics exposed (4.1.2) | ✔︎ | `role="dialog"`, `aria-modal="true"` | ▫︎ |
| L.2 | Escape dismisses (2.1.2) | 🔬 | `keydown` on the overlay closes on Escape | ▫︎ |
| L.3 | Focus not trapped irrecoverably (2.1.2) | 🔬 | Tab is intercepted and focus forced back to the close button, the only focusable control. Escape and the button both exit | ▫︎ |
| L.4 | Focus returns to the trigger on close (2.4.3) | 🔬 | `lastFocused` captured on open, restored on close | ▫︎ |
| L.5 | Focus moves into the dialog on open (2.4.3) | 🔬 | `closeBtn.focus()` on open | ▫︎ |
| L.6 | Trigger is a real control (2.1.1) | ✔︎ | `basalio_media_wrap()` emits `<button type="button">` with an `aria-label` | ▫︎ |
| L.7 | Dialog controls are translatable | ✔︎ | **Fixed.** See CC-1 | Asserted: no hardcoded dialog labels left in JS |
| L.8 | Lightbox image has meaningful `alt` (1.1.1) | ⚠️ | Taken from `data-basalio-lightbox-alt`. Falls back to `alt=""`, correct for a decorative image but leaves an unlabeled image in a dialog whose only other content is a close button | ▫︎ |
| L.9 | Background content inert while open (2.4.3) | ⚠️ | `aria-modal="true"` plus the Tab intercept. Neither `inert` nor `aria-hidden` is applied to the rest of the page, so support depends on the AT honoring `aria-modal` | ▫︎ |
| L.10 | Reduced motion honored on open/close (2.3.3) | 🔬 | Open/close animate via `is-open` and a 220ms teardown timeout | Pending `assets/css/basalio-core.css` read |
| L.11 | Scroll lock released on close | ✔︎ | `documentElement.style.overflow` set and cleared | ▫︎ |

---

## Cross-cutting

### CC-1 — Front-end i18n ✔︎ Fixed

`filterable-grid/render.php` correctly used `_n()` with a translator comment, and
then `basalio-core.js` overwrote it with hardcoded English on the first filter
click. On a French site the count rendered in French until a visitor touched a
filter, then permanently became English, in a different format. The lightbox had
the same shape: a translated "Expand image" trigger opening an English-labeled
dialog.

Root cause: `wp_set_script_translations()` was called only on editor script
handles, never on `basalio-core`.

**Fix:** `basalio-core` now declares `wp-i18n` and has translations wired; the
three runtime strings use `wp.i18n`. Asserted five ways.

**Release obligation:** JS translations resolve from `.json` files, so shipping a
locale needs `wp i18n make-json languages/` after `make-pot`. Absent those files
the strings fall back to English, i.e. the previous behaviour. This has not been
proven end to end and needs a test locale.

### CC-2 — Reduced motion read once at boot ⚠️ Open

Magnetic Button, Custom Cursor, and Scroll Sequence each evaluate
`matchMedia( … ).matches` once and then bind or skip permanently. A visitor who
enables "Reduce motion" mid-session keeps the motion until reload. Custom Cursor
is insulated because its CSS also hides the accent, and media queries
re-evaluate live; the other two have no backstop.

Not a WCAG failure. Worth a `matchMedia().addEventListener( 'change', … )`.

### CC-3 — Module numbering inconsistent ⚠️ Open

`basalio-core.js` comments number the behaviors 1, 2, 4, 8, 9, 5 in an order that
matches neither the README nor this document. Reconcile the comments to the
numbering at the top of this file.

### CC-4 — Referenced spec file does not exist ❌ Open

Earlier revisions of this document cited `basalio-product-spec.md` as its source
of truth. No such file exists in the project. This revision derives its
checklist from shipped behavior instead. Either recover the original from
`_archive/snapshots/` or accept this document as the checklist.

### CC-5 — Enqueue invariant ✔︎ Fixed

Any module emitting `data-basalio-lightbox` must enqueue the core, or the trigger
is inert. Pinned Scroll violated this: it announced "Expand image" and did
nothing, and rendered with the theme's raw `<button>` chrome.

**Fix:** `basalio_media_wrap()` now enqueues the core itself, so the invariant no
longer depends on each module author remembering. Pinned Scroll also enqueues
explicitly, matching its siblings.

### CC-6 — `block.json` string defaults are untranslatable by construction ✔︎ Fixed

`block.json` is static JSON with no gettext. Any user-facing string given a
default there always wins over a PHP `__()` fallback, making the fallback dead
code and the string permanently English. This bit twice:
`scroll-sequence.paginationFormat` and `filterable-grid.allLabel`. Both defaults
are now empty and both are guarded by assertions.

Applies only to user-facing text. Enum-ish defaults (`revealStyle`,
`mediaPosition`, `cursorStyle`) are fine.

---

## Open items

| Item | Kind | Blocking |
|---|---|---|
| 2.3 Case Study Transition duration | ❌ Defect + product decision | Yes: this document asserts a cap the code does not honor |
| 5.6, 5.8 screen-reader confirmation | 🔬 | Promoting two fixes from Asserted to Verified |
| L.2–L.5 lightbox focus behaviour | 🔬 | Highest-risk untested surface |
| 6.8, 4.6, L.10 | 🔬 | Three stylesheets still unread |
| 7.6 Pinned Scroll DOM order | ⚠️ | No |
| 4.7 new-tab indication | ⚠️ | No |
| CC-2, CC-3, CC-4 | ⚠️ / ❌ | No |
| `example.com` in the plugin header | Hygiene | Release |
| `.netlify/`, `index.html`, `tests/` in the zip | Hygiene | Release |
