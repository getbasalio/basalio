# Basalio: WCAG 2.2 QA Matrix — DRAFT, Modules 4–9 + Shared Lightbox

**Status: DRAFT. Do not merge into `WCAG-QA.md` until the rows marked
🔬 *Needs browser verification* have been exercised in a real browser.**

Every row below is **Structural** or **By design**. Nothing here is **Verified**,
because these rows were written by reading the shipped source
(`basalio-core.js`, each module's `render.php`), not by exercising the plugin in
a browser. Under the project's standing verification rule, a Structural row is a
claim awaiting proof, not proof.

Legend, matching `WCAG-QA.md`: ✅ Pass · ⚠️ Pass with caveat · ❌ Defect ·
🔬 Needs browser verification · ⏳ Blocked on a source file not yet read.

---

## Source coverage behind this draft

| File | Read | Notes |
|---|---|---|
| `assets/js/basalio-core.js` | ✅ full | All seven behaviors + shared lightbox |
| `modules/before-after/render.php` | ✅ full | |
| `modules/scroll-sequence/render.php` | ✅ full | |
| `modules/case-study-transition/render.php` | ✅ full | Covered in existing doc |
| `modules/*/block.json` | ✅ (6 of 10) | grid-reveal, cst, custom-cursor, pinned-scroll, grid-item, filterable-grid |
| `modules/magnetic-button/render.php` + `style.css` | ⏳ not read | Markup rows below are blocked |
| `modules/custom-cursor/render.php` + `style.css` | ⏳ not read | `pointer-events` + `aria-hidden` claims blocked |
| `modules/pinned-scroll/render.php` + `style.css` | ⏳ not read | Entire module blocked |
| `modules/filterable-grid/render.php` + `grid-item/render.php` | ⏳ not read | Live-region + markup rows blocked |
| `assets/css/basalio-core.css` | ⏳ not read | Lightbox reduced-motion row blocked |

---

## Module 6: Before / After

The strongest module in the plugin from an accessibility standpoint. The handle
is a complete ARIA slider, not a draggable `div` with ARIA sprinkled on.

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 6.1 | Divider is operable by keyboard, not pointer-only (2.1.1) | ✅ | `role="slider"` + `tabindex="0"` in `render.php`; `keydown` handles Arrow (±`data-basalio-step`, default 2), PageUp/PageDown (±10), Home (0), End (100), each with `preventDefault()` | Structural (`before-after/render.php`, `basalio-core.js`) |
| 6.2 | Slider exposes correct value semantics to AT (4.1.2) | ✅ | Server emits `aria-valuemin="0"`, `aria-valuemax="100"`, `aria-valuenow`, `aria-valuetext`, `aria-orientation="horizontal"`. JS `setPos()` keeps `aria-valuenow` and `aria-valuetext` in sync on every change | Structural |
| 6.3 | Slider has an accessible name (4.1.2) | ✅ | `aria-label` composed server-side via `sprintf( __( 'Reveal %1$s over %2$s', 'basalio' ) )` from the user's Before/After labels. Translatable, and meaningful rather than generic | Structural |
| 6.4 | Decorative chrome is hidden from AT (1.3.1) | ✅ | `.basalio-before-after__divider` and `.basalio-before-after__handle-icon` both carry `aria-hidden="true"` | Structural |
| 6.5 | Grabbing the handle by pointer leaves it keyboard-operable (2.1.1) | ✅ | `pointerdown` calls `e.preventDefault()` to stop text selection, which also suppresses native focus, so the handler explicitly calls `handle.focus()`. Frame-body presses also focus the handle. This is the 0.4.1 fix, still in place | Structural |
| 6.6 | Content survives with JS disabled (progressive enhancement) | ✅ | Both `<img>` render server-side; `--basalio-ba-pos` is set inline from `startPosition`, so the comparison renders split at the author's chosen position with no JS | Structural |
| 6.7 | Both images carry meaningful `alt` (1.1.1) | ✅ | `basalio_ba_image()` prefers `wp_get_attachment_image()` and `preg_replace`s the author's `alt` override in when set | Structural |
| 6.8 | No motion violation under `prefers-reduced-motion` (2.3.3) | 🔬 | The divider position is user-driven, not auto-animating, so there is likely nothing to gate. Must confirm `before-after/style.css` puts no `transition` on `--basalio-ba-pos` or the handle that would animate on keypress | Pending `style.css` read |

**Caveat (6.2):** `aria-valuetext` is set to a bare percentage (`"63%"`). That is
valid but thin. `"63% — Before"` style text would tell a screen-reader user what
the number means. Worth considering, not a failure.

**Beta tester checks:** Tab to the handle → visible focus ring, arrows move the
divider in 2% steps; press Home then End → divider snaps to each edge; click mid-frame
with a mouse, then immediately press an arrow key → divider moves (this is the
0.4.1 regression guard); disable JS → both images visible, split at the start
position; screen reader on the handle → hears "Reveal Before over After, slider,
50%".

---

## Module 5: Scroll Sequence

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 5.1 | Usable without scrolling / without a pointing device (2.5.1, 2.1.1) | ✅ | Real `<button type="button">` Previous and Next controls step frames. Under `prefers-reduced-motion: reduce` the scroll driver is skipped entirely and the buttons become the only driver | Structural (`render.php`, `basalio-core.js`) |
| 5.2 | Controls have accessible names (4.1.2) | ✅ | `esc_attr_e( 'Previous frame', 'basalio' )` / `'Next frame'`. Translatable, and not reliant on the `←` `→` glyph text | Structural |
| 5.3 | Frame position is exposed to AT (4.1.3) | ⚠️ | `<p role="status">` renders server-side with real initial text ("Frame 1 of N"), and JS updates it on change | See defect 5.6 |
| 5.4 | Initial state is correct with JS off | ✅ | Frame index 0 gets `is-active` server-side, and the status paragraph is server-rendered at `{current}=1`. JS initialises `current = 0`, so there is no flash or desync on boot | Structural |
| 5.5 | First frame is not lazy-loaded (perf / CLS) | ✅ | Frame 0 renders `loading="eager"`, all others `loading="lazy"` | Structural |
| 5.6 | Status region does not spam assistive technology (4.1.3) | ❌ | **Defect.** `show()` writes `status.textContent` on every frame change, and `show()` is called from the rAF-throttled `scroll` handler. A visitor scrolling through an 8-frame sequence triggers up to 8 live-region announcements in a couple of seconds, each interrupting the last | Structural (`basalio-core.js`, `scroll-sequence` behavior) |
| 5.7 | Per-frame `alt` text is honored (1.1.1) | ❌ | **Defect.** For attachment-backed frames the code calls `wp_get_attachment_image()` and never applies `$basalio_alt`. The author's alt text entered in the editor is silently discarded and the attachment's own alt is used instead. The URL-fallback branch *does* honor it, so behavior differs by input path | Structural (`scroll-sequence/render.php`) |
| 5.8 | Inactive frames are not announced (1.3.1) | 🔬 | All N frames are `<img>` siblings in the DOM. If inactive frames are hidden with `opacity` rather than `display:none` / `visibility:hidden`, a screen reader will announce every frame in the sequence | Pending `scroll-sequence/style.css` read |
| 5.9 | Pagination format string is translatable | ⚠️ | The `data-basalio-seq-format` attribute is author-editable and passed through, which is right. But the PHP default `'Frame {current} of {total}'` is a bare literal, not wrapped in `__()`, so an untouched block ships English on every locale | Structural |

**Defect 5.6, suggested fix.** Split the visual counter from the announced one.
Keep `role="status"` for button-driven changes only, and update the scroll-driven
counter through a plain element with no live semantics. Simplest version: remove
`role="status"` from the `<p>`, add a visually-hidden sibling that carries it, and
only write to that sibling from the `prev`/`next` click handlers.

**Defect 5.7, suggested fix.** Mirror the `preg_replace` alt-override that
`grid-reveal`, `case-study-transition`, and `before-after` already use. Three
modules do this correctly and one does not, which is the kind of inconsistency
that is cheap to fix now and expensive to find later.

**Beta tester checks:** With a screen reader running, scroll slowly through the
sequence and count the announcements; enable "Reduce motion" and confirm scrolling
changes nothing while the buttons still work; set a custom alt on frame 3 in the
editor and confirm it appears in the rendered HTML (currently expected to fail);
Tab to Previous/Next and step the whole sequence with the keyboard only.

---

## Module 8: Filterable Grid (+ Grid Item child)

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 8.1 | Filters are real, keyboard-operable controls (2.1.1) | ✅ | Server renders real `<button>` elements; Enter/Space are native | Structural (`basalio-core.js`) |
| 8.2 | Filter pressed state is exposed (4.1.2) | ✅ | `apply()` sets `aria-pressed` `true`/`false` across all filters on every change, alongside the `is-active` class | Structural |
| 8.3 | Filter toolbar follows the WAI-ARIA roving-tabindex pattern (2.4.3) | ✅ | Exactly one filter carries `tabindex="0"` at a time via `setRoving()`; Arrow Left/Right/Up/Down wrap with modulo, Home/End jump to the ends, all with `preventDefault()` | Structural |
| 8.4 | Hidden items leave the tab order and the a11y tree (1.3.1, 2.4.3) | ✅ | Filtering uses the `hidden` **property** (`item.hidden = !match`) rather than an opacity or class trick, so non-matching items are genuinely removed from both | By design |
| 8.5 | Result count is announced (4.1.3) | ⚠️ | JS writes to `.basalio-filterable-grid__status`. Whether that element carries `role="status"` or `aria-live` is in `filterable-grid/render.php`, not yet read | ⏳ Blocked |
| 8.6 | Works with JS disabled | ✅ | Every item renders visible server-side; filtering is pure enhancement layered on top | By design |
| 8.7 | Count message is translatable (i18n) | ❌ | **Defect.** `status.textContent = 'Showing ' + shown + ' of ' + total + noun;` with `' project.'` / `' projects.'` are hardcoded English literals inside `basalio-core.js`. See the cross-cutting i18n defect below | Structural |
| 8.8 | Grid Item cannot be inserted standalone | ✅ | `block.json` declares `"parent": [ "basalio/filterable-grid" ]` and `"reusable": false` | Structural (`grid-item/block.json`) |

---

## Module 4: Magnetic Button

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 4.1 | Effect is disabled under `prefers-reduced-motion` (2.3.3) | ✅ | Behavior returns early on `matchMedia( '(prefers-reduced-motion: reduce)' ).matches` before binding any listener | Structural (`basalio-core.js`) |
| 4.2 | Effect is disabled on touch / coarse pointers | ✅ | Behavior returns early unless `(hover: hover) and (pointer: fine)` matches | Structural |
| 4.3 | The control remains a real, keyboard-operable element regardless of the effect | ⏳ | The behavior only sets two CSS custom properties and never alters semantics, which is the right shape. Confirming the element is a real `<a>` or `<button>` requires `magnetic-button/render.php` | Blocked |
| 4.4 | Displacement cannot move the control out from under the pointer (2.5.8 target size) | 🔬 | `strength` defaults to 18px and the translate is applied via `--basalio-mag-x/y`. At the default this is small relative to a button, but the attribute is author-set and an aggressive value could displace a small target away from a click | Needs browser check at max configurable strength |
| 4.5 | Displacement resets when focus or pointer leaves | ✅ | `reset()` bound to both `pointerleave` and `blur` | Structural |

---

## Module 9: Custom Cursor

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 9.1 | Disabled under `prefers-reduced-motion` (2.3.3) | ✅ | Early return before any listener binds | Structural (`basalio-core.js`) |
| 9.2 | Disabled on touch / coarse pointers | ✅ | Early return unless `(hover: hover) and (pointer: fine)` | Structural |
| 9.3 | Accent never blocks a click target (2.5.1) | ⏳ | The JS only writes `--basalio-cursor-x/y` and toggles `is-cursor-active`; the `pointer-events: none` guarantee lives in CSS | Blocked on `custom-cursor/style.css` |
| 9.4 | Accent is hidden from assistive technology (1.3.1) | ⏳ | Purely decorative, so the accent element needs `aria-hidden="true"` or to be a CSS pseudo-element | Blocked on `custom-cursor/render.php` |
| 9.5 | Native cursor is not replaced or hidden (2.4.7, 2.5.1) | ⏳ | The plugin describes the accent as additive, but a `cursor: none` rule anywhere in the module stylesheet would break pointer visibility | Blocked on `custom-cursor/style.css` |

---

## Module 7: Pinned Scroll

⏳ **Blocked in full.** No source read yet. `README.md` claims "Pure CSS sticky,
so it never traps focus and degrades to a normal stack under reduced motion," and
the module ships no JS (it is absent from `basalio-core.js`), which is consistent.
Rows to write once `pinned-scroll/render.php` and `style.css` are read:

- 7.1 Sticky positioning does not trap or steal focus (2.1.2)
- 7.2 Degrades to a stacked layout under `prefers-reduced-motion` (2.3.3)
- 7.3 Pinned image does not obscure content at 320px width / 400% zoom (1.4.10)
- 7.4 Image `alt` is honored, and the `mediaWidth` control cannot produce an unreadable text column
- 7.5 `mediaPosition` left/right does not invert DOM order away from reading order (1.3.2)

---

## Shared: Lightbox

**This component is undocumented in `WCAG-QA.md` and ships enabled on five
blocks** (Grid Reveal, Case Study Transition, Grid Item, Pinned Scroll, and any
image block with `lightbox: true`). A modal dialog is the highest-risk
accessibility surface in the plugin and currently has zero QA coverage.

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| L.1 | Dialog exposes modal semantics (4.1.2) | ✅ | `role="dialog"`, `aria-modal="true"` set on the overlay at build time | Structural (`basalio-core.js`, `lightbox` behavior) |
| L.2 | Escape dismisses (2.1.2) | ✅ | `keydown` on the overlay closes on `Escape` | Structural |
| L.3 | Focus is not trapped irrecoverably (2.1.2) | ✅ | `Tab` is intercepted and focus forced back to the close button, which is the only focusable control. Escape and the close button both exit | Structural |
| L.4 | Focus returns to the trigger on close (2.4.3) | ✅ | `lastFocused = document.activeElement` captured on open; `close()` restores it | Structural |
| L.5 | Focus moves into the dialog on open (2.4.3) | ✅ | `closeBtn.focus()` on open | Structural |
| L.6 | Trigger is a real control, not a click-handled `div` (2.1.1) | ✅ | `basalio_media_wrap()` emits `<button type="button" class="basalio-media-expand">` with an `aria-label` | Structural (`basalio.php`) |
| L.7 | Dialog controls are translatable (i18n) | ❌ | **Defect.** `aria-label="Image viewer"` and `aria-label="Close image viewer"` are hardcoded English string literals in `basalio-core.js`. The trigger's own label *is* translated via `esc_attr__( 'Expand image', 'basalio' )` in PHP, so a non-English site gets a translated button that opens an English-labeled dialog | Structural |
| L.8 | Lightbox image has meaningful `alt` (1.1.1) | ⚠️ | `alt` is taken from `data-basalio-lightbox-alt`, which `basalio_media_wrap()` populates from the block's alt attribute. Falls back to `alt=""` when unset, which is correct for an unlabeled decorative image but means an author who left alt blank gets an unlabeled image in a dialog whose only other content is a close button | Structural |
| L.9 | Background content is inert while open (2.4.3) | ⚠️ | `aria-modal="true"` instructs AT to constrain to the dialog, and the Tab intercept holds keyboard focus. Neither `inert` nor `aria-hidden` is applied to the rest of the page, so support depends on the AT honoring `aria-modal` | Structural |
| L.10 | Reduced motion honored on open/close (2.3.3) | ⏳ | Open/close animate via the `is-open` class and a hardcoded `220ms` teardown timeout in JS | Blocked on `assets/css/basalio-core.css` |
| L.11 | Scroll lock is released on close | ✅ | `documentElement.style.overflow` set to `'hidden'` on open and `''` on close | Structural |

---

## Cross-cutting defects

### CC-1 — Front-end strings are not translatable ❌

`basalio-core.js` contains hardcoded English UI text:

- `'Showing ' + shown + ' of ' + total + ' projects.'` (Filterable Grid status)
- `'Image viewer'` (lightbox `aria-label`)
- `'Close image viewer'` (close button `aria-label`)

`class-basalio.php` calls `wp_set_script_translations()` only on each block's
**editor** script handles, inside `register_modules()`. The `basalio-core` handle
registered in `register_assets()` never gets that call, so even if these strings
were wrapped in `wp.i18n.__()` today they would not resolve.

This contradicts the 0.4.0 changelog entry, "Wired plugin translation loading and
per-block editor-script translations," which is accurate as written but reads as
broader coverage than actually shipped.

**Fix:** add `wp-i18n` to the `basalio-core` dependency array, call
`wp_set_script_translations( 'basalio-core', 'basalio', BASALIO_PATH . 'languages' )`
in `register_assets()`, wrap the three strings in `wp.i18n.__()` / `_n()`, and
regenerate `basalio.pot`. The `' project.'` / `' projects.'` pair specifically
needs `_n()`, not a ternary, since plural rules vary by locale.

Note the contrast: Scroll Sequence solves this correctly by passing its format
string from PHP through `data-basalio-seq-format`. That pattern could be reused
for the Filterable Grid count instead of adding a JS i18n dependency.

### CC-2 — Reduced-motion preference is read once, at boot ⚠️

Magnetic Button, Custom Cursor, and Scroll Sequence each evaluate
`matchMedia( '(prefers-reduced-motion: reduce)' ).matches` a single time when the
behavior runs, then bind or skip listeners permanently. A visitor who enables
"Reduce motion" in their OS while the page is open keeps the motion until reload.

The CSS-driven modules do not have this problem, since a media query re-evaluates
live. This is a JS-only gap.

Severity is low, and it is not a WCAG failure (2.3.3 does not require live
response). Worth a `matchMedia().addEventListener( 'change', … )` when convenient,
not worth blocking a release.

### CC-3 — Module numbering is inconsistent across artifacts ⚠️

`basalio-core.js` labels behaviors Module 1, 2, 4, 8, 9, 5. `README.md` lists nine
blocks in a different order. `WCAG-QA.md` numbers its three modules 1–3 matching
the README. There is no single canonical numbering, which makes the QA matrix hard
to cross-reference against the code.

This draft follows the README order and assigns: 1 Grid Reveal, 2 Case Study
Transition, 3 Text Reveal, 4 Magnetic Button, 5 Scroll Sequence, 6 Before/After,
7 Pinned Scroll, 8 Filterable Grid, 9 Custom Cursor. **Confirm or override this
before merging**, then reconcile the comments in `basalio-core.js` to match.

### CC-4 — The referenced spec file does not exist ❌

`WCAG-QA.md` opens with "One row per checklist item in
`basalio-product-spec.md`." No file by that name exists anywhere under
`~/Projects/products/basalio/`. The QA matrix therefore cites a source of truth
that cannot be checked, and there is no authoritative list of which checkboxes
each module was supposed to satisfy.

Either recover that file from the archived snapshots, or rewrite the checklist
from the shipped behavior and re-point the reference.

---

## What must happen before this draft is merged

1. Read the six remaining source files listed in the coverage table and fill the ⏳ rows.
2. Resolve the two Scroll Sequence defects (5.6 live-region spam, 5.7 discarded alt).
3. Resolve CC-1 (front-end i18n).
4. Settle the module numbering (CC-3) and reconcile `basalio-core.js` comments.
5. Exercise every 🔬 row in a real browser and promote it from Structural to Verified.
6. Merge into `WCAG-QA.md` and delete this draft.
