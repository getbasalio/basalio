# Basalio: WCAG 2.2 QA Matrix — DRAFT rev2, Modules 4–9 + Shared Lightbox

**Status: DRAFT. Replaces `WCAG-QA-draft-modules-4-9.md`. Do not merge into
`WCAG-QA.md` until the 🔬 rows are exercised in a browser.**

Every row is **Structural** or **By design**, written by reading shipped source,
not by running the plugin. Under the project's verification rule a Structural row
is a claim awaiting proof.

Legend: ✅ Pass · ⚠️ Pass with caveat · ❌ Defect · 🔬 Needs browser verification ·
⏳ Blocked on an unread source file.

---

## Source coverage

| File | Read |
|---|---|
| `assets/js/basalio-core.js` | ✅ full |
| `modules/before-after/render.php` | ✅ |
| `modules/scroll-sequence/render.php` | ✅ |
| `modules/filterable-grid/render.php` | ✅ |
| `modules/grid-item/render.php` | ✅ |
| `modules/pinned-scroll/render.php` + `style.css` | ✅ |
| `modules/custom-cursor/render.php` + `style.css` | ✅ |
| `modules/magnetic-button/render.php` | ✅ |
| `modules/case-study-transition/render.php` | ✅ |
| `assets/css/basalio-core.css` | ⏳ blocks L.10 |
| `modules/scroll-sequence/style.css` | ⏳ blocks 5.8 |
| `modules/before-after/style.css` | ⏳ blocks 6.8 |
| `modules/magnetic-button/style.css` | ⏳ blocks 4.6 |

---

## 🔴 Blocking defect: Pinned Scroll lightbox is dead on arrival

`modules/pinned-scroll/render.php` calls `basalio_media_wrap()` with its
`$basalio_lb` flag, which emits:

```html
<button type="button" class="basalio-media-expand"
        data-basalio-lightbox="…" aria-label="Expand image">
```

But **`pinned-scroll/render.php` never calls `basalio_enqueue_core()`.** Every
other module that emits lightbox markup does. The file header explains the
omission as "This block ships no JavaScript," which is true of its *own*
behavior but false the moment the author ticks the lightbox option.

Consequences when a Pinned Scroll block with lightbox enabled is the only Basalio
block on a page:

1. `basalio-core.js` never loads, so the `lightbox` behavior never binds. The
   button is focusable, announces "Expand image", and **does nothing when
   activated.** That is a control that advertises an action it cannot perform:
   a 4.1.2 / 2.1.1 failure, not a cosmetic bug.
2. `basalio-core.css` never loads either, so `.basalio-media-expand` renders with
   the theme's default `<button>` chrome — border, background, padding — wrapped
   around the image.

**Fix:** call `basalio_enqueue_core()` in `pinned-scroll/render.php`, either
unconditionally to match every sibling module, or gated on `$basalio_lb || $basalio_link`.
Unconditional is safer and costs one small deferred script on a page that already
loads an image.

**Regression guard worth adding:** any module whose render path can reach
`basalio_media_wrap()` must enqueue the core. Right now that invariant lives only
in each author's head.

---

## Module 7: Pinned Scroll

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 7.1 | No scroll hijacking; focus is never trapped (2.1.2) | ✅ | Pinning is `position: sticky` on `.basalio-pinned-scroll__media-inner`. No JS, no scroll listener, no `scrollTo`. Everything stays in normal flow and in the DOM | By design (`style.css`, and the module is absent from `basalio-core.js`) |
| 7.2 | Degrades to a stacked layout under `prefers-reduced-motion` (2.3.3) | ✅ | `@media (prefers-reduced-motion: reduce)` sets `.basalio-pinned-scroll__media-inner { position: static }` | Structural |
| 7.3 | Reflows to a single column on narrow viewports (1.4.10) | ✅ | `@media (max-width: 781px)` sets `grid-template-columns: 1fr`, drops the sticky to `static`, and resets `order` | Structural |
| 7.4 | Author `alt` is honored (1.1.1) | ✅ | `preg_replace` alt override applied to the `wp_get_attachment_image()` output, and `esc_attr( $basalio_alt )` in the URL fallback. Both paths correct | Structural |
| 7.5 | Media/story split cannot produce an unreadable text column | ✅ | `mediaWidth` clamped `max( 25, min( 75, … ) )`, so the story column is never narrower than 25% | Structural |
| 7.6 | `mediaPosition: right` does not break reading order (1.3.2, 2.4.3) | ⚠️ | DOM order is always media → story. `media-right` moves the media visually via `order: 2`, so visual order becomes story → media while DOM and tab order stay media → story. See caveat | Structural |
| 7.7 | Lightbox / link affordance actually works | ❌ | **See the blocking defect above.** The core script is never enqueued | Structural |

**Caveat (7.6).** For a purely illustrative image this order mismatch is minor and
arguably not a 1.3.2 failure, since the image and its story are not a sequence
whose order carries meaning. It becomes a real 2.4.3 problem when the image is a
link or lightbox trigger: a keyboard user tabbing through a `media-right` block
reaches the interactive image *before* the story text, while seeing it to the
right of that text. The narrow-viewport rule already resets `order: 0`, which
shows the pattern is understood. Consider reordering in the DOM and using
`order` only for the left variant, so the interactive element always follows
visual order.

**Beta tester checks:** Set a Pinned Scroll block with lightbox on, alone on a
page, click the image (currently expected to do nothing); enable "Reduce motion"
and confirm the image stops pinning; resize to 780px and confirm single-column
stack; with `media-right`, Tab through and note where focus lands relative to
what you see.

---

## Module 8: Filterable Grid (+ Grid Item)

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 8.1 | Filters are real, keyboard-operable controls (2.1.1) | ✅ | Server renders `<button type="button">`; Enter/Space are native | Structural |
| 8.2 | Pressed state exposed (4.1.2) | ✅ | `aria-pressed` rendered server-side (`"true"` on All, `"false"` on the rest) and rewritten across all filters by `apply()` | Structural |
| 8.3 | Filter group uses the WAI-ARIA toolbar pattern (2.4.3) | ✅ | Container carries `role="toolbar"`, `aria-orientation="horizontal"`, and a translatable `aria-label` ("Filter by discipline"). JS adds roving tabindex, Arrow keys with wraparound, Home/End | Structural |
| 8.4 | Hidden items leave the tab order and a11y tree (1.3.1, 2.4.3) | ✅ | `item.hidden = !match` uses the `hidden` property, not an opacity or class trick | By design |
| 8.5 | Result count is announced (4.1.3) | ✅ | `<p class="basalio-filterable-grid__status" role="status">` rendered server-side with real initial text | Structural |
| 8.6 | Works with JS disabled | ✅ | All items render visible; filters are pure enhancement | By design |
| 8.7 | Count message is translatable (i18n) | ❌ | **Defect, see CC-1.** PHP is correct. JS overwrites it with English on the first filter click | Structural |
| 8.8 | Grid Item cannot be inserted standalone | ✅ | `"parent": [ "basalio/filterable-grid" ]`, `"reusable": false` | Structural |
| 8.9 | Grid Item lightbox trigger has an accessible name (4.1.2) | ✅ | `<button>` branch carries `esc_attr__( 'Expand image', 'basalio' )` | Structural |
| 8.10 | Grid Item link always has an accessible name (2.4.4, 4.1.2) | ⚠️ | The `<a class="basalio-grid-item__frame">` branch wraps only the `<img>`. The `<figcaption>` with title and category sits **outside** the link. If the author leaves alt blank, the link has no accessible name at all | Structural |
| 8.11 | Uncategorised items behave predictably when filtering | ⚠️ | An item with no category renders `data-basalio-item-category=""`, which matches no filter slug. Such items appear under "All" and vanish under every other filter, with no visible explanation | Structural |

**Caveat (8.10).** Keeping the caption outside the link is the right call for link
text quality, so the fix is not to move it. Either fall back to the item's `title`
as an `aria-label` on the `<a>` when `alt` is empty, or surface an editor warning.
This is a genuine empty-link failure in a plausible authoring path: an author who
treats the image as decorative and relies on the visible caption.

---

## Module 9: Custom Cursor

Every open row from rev1 now resolves to Pass. This module is clean.

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 9.1 | Disabled under `prefers-reduced-motion` (2.3.3) | ✅ | Belt and braces: JS returns early before binding, **and** CSS sets `display: none` on the accent under `reduce`. Either alone would suffice | Structural (JS + `style.css`) |
| 9.2 | Disabled on touch / coarse pointers | ✅ | JS early return unless `(hover: hover) and (pointer: fine)` | Structural |
| 9.3 | Accent never blocks a click target (2.5.1) | ✅ | `pointer-events: none` on `.basalio-custom-cursor__accent` | Structural |
| 9.4 | Accent is hidden from assistive technology (1.3.1) | ✅ | `<span class="basalio-custom-cursor__accent" aria-hidden="true">` | Structural |
| 9.5 | Native cursor is never hidden or replaced (2.4.7) | ✅ | No `cursor:` declaration anywhere in the module stylesheet. The accent is purely additive | Structural |
| 9.6 | Accent starts invisible and only appears on real pointer movement | ✅ | `opacity: 0` by default; `.is-cursor-active` (added on `pointermove`, removed on `pointerleave`) raises it to 1 | Structural |
| 9.7 | Colour input is sanitised | ✅ | `sanitize_hex_color()` with a hardcoded fallback | Structural |

**Note, not an accessibility issue:** the fallback colour is `#C6F135` in both
`render.php` and the CSS `var()` defaults. The documented Basalio brand chartreuse
is `#DFFF00`. One of the two is wrong. Flagged for brand review, not QA.

---

## Module 4: Magnetic Button

| # | Spec checkbox | Status | How it's met | Evidence |
|---|---|---|---|---|
| 4.1 | Disabled under `prefers-reduced-motion` (2.3.3) | ✅ | JS returns early before binding any listener | Structural |
| 4.2 | Disabled on touch / coarse pointers | ✅ | JS returns early unless `(hover: hover) and (pointer: fine)` | Structural |
| 4.3 | Remains a real, keyboard-operable control (2.1.1) | ✅ | Renders `<a href>` when a URL is set. The behavior only writes two CSS custom properties and never touches semantics or the tab order | Structural |
| 4.4 | No fake control when no URL is set | ✅ | Falls back to `<span>`, not a `<div role="button">` or an `<a>` with no `href`. Nothing unusable enters the tab order | Structural |
| 4.5 | Displacement resets on pointer leave and blur | ✅ | `reset()` bound to `pointerleave` and `blur` | Structural |
| 4.6 | Displacement cannot move the control out from under the pointer (2.5.8) | 🔬 | `strength` is clamped `max( 4, min( 40, … ) )` with an inline comment citing vestibular comfort. 40px on a short button is still meaningful displacement | Test at `strength = 40` on the smallest button the block allows |
| 4.7 | New-tab links are announced as such (3.2.5) | ⚠️ | `target="_blank"` is paired with `rel="noopener noreferrer"`, which is the security half. There is no visible or screen-reader-only indication that the link opens a new window | Structural |
| 4.8 | Accessible name comes from visible text (2.5.3) | ✅ | Name is the `<span class="basalio-magnetic-button__label">` text. No competing `aria-label` to desync from it | Structural |

---

## Module 5: Scroll Sequence

Unchanged from rev1. Both defects stand.

| # | Spec checkbox | Status | Summary |
|---|---|---|---|
| 5.1 | Usable without scrolling or a pointer (2.5.1, 2.1.1) | ✅ | Real Prev/Next buttons; sole driver under reduced motion |
| 5.2 | Controls have accessible names (4.1.2) | ✅ | Translatable `aria-label`s, not reliant on arrow glyphs |
| 5.3 | Frame position exposed (4.1.3) | ⚠️ | `role="status"` present, but see 5.6 |
| 5.4 | Correct initial state with JS off | ✅ | Frame 0 `is-active` server-side; status renders at `{current}=1` |
| 5.5 | First frame not lazy-loaded | ✅ | `loading="eager"` on index 0 only |
| 5.6 | Live region does not spam AT (4.1.3) | ❌ | `show()` writes `status.textContent` from the rAF scroll handler |
| 5.7 | Per-frame `alt` honored (1.1.1) | ❌ | Attachment branch discards the author's alt; URL branch honors it |
| 5.8 | Inactive frames not announced (1.3.1) | ⏳ | Blocked on `scroll-sequence/style.css` |
| 5.9 | Pagination format translatable | ⚠️ | PHP default `'Frame {current} of {total}'` is a bare literal, not `__()`-wrapped |

---

## Module 6: Before / After

Unchanged from rev1. Strongest module in the plugin. Only 6.8 remains open,
blocked on `before-after/style.css`.

---

## Shared: Lightbox

Unchanged from rev1, except L.10 remains ⏳ blocked on `assets/css/basalio-core.css`.
Semantics are sound (`role="dialog"`, `aria-modal`, Escape, focus capture and
restore, real `<button>` trigger, scroll lock released). The failure is L.7, i18n.

---

## Cross-cutting defects

### CC-1 — JavaScript overwrites correctly translated PHP with English ❌

**Sharper than rev1 recorded.** `filterable-grid/render.php` does this correctly:

```php
esc_html( _n( 'Showing %d project.', 'Showing %d projects.', $basalio_total, 'basalio' ) )
```

Proper `_n()`, proper text domain, translator comment present. Then on the first
filter click, `basalio-core.js` replaces it:

```js
status.textContent = 'Showing ' + shown + ' of ' + total + noun;
```

On a French site the count renders in French until the visitor touches a filter,
then permanently becomes English. The string format also differs between the two
("Showing 12 projects." versus "Showing 5 of 12 projects."), so the announcement
changes shape as well as language.

Same class of defect in the lightbox: `'Image viewer'` and `'Close image viewer'`
are hardcoded in JS, while the trigger that opens it is correctly translated via
`esc_attr__( 'Expand image', 'basalio' )` in PHP.

Root cause: `wp_set_script_translations()` is called only on editor script handles
inside `register_modules()`. The `basalio-core` handle registered in
`register_assets()` never receives it, so `wp.i18n.__()` would not resolve even if
the strings were wrapped today.

**Recommended fix, no new JS dependency:** follow the pattern Scroll Sequence
already uses. Pass the translated format from PHP as a data attribute
(`data-basalio-count-format="Showing %1$s of %2$s projects."`) and have the JS
substitute the numbers. That keeps every translatable string in PHP where the
`.pot` extractor can see it, and avoids adding `wp-i18n` plus a JSON translation
build to the core script. It also fixes the singular/plural problem, since PHP
`_n()` already handles locale plural rules.

### CC-2 — Reduced-motion is read once at boot ⚠️

Magnetic Button, Custom Cursor, and Scroll Sequence each evaluate
`matchMedia( … ).matches` once and then bind or skip permanently. A visitor who
enables "Reduce motion" mid-session keeps the motion until reload.

Custom Cursor is partly insulated because its CSS also hides the accent under
`reduce`, and that media query re-evaluates live. Magnetic Button and Scroll
Sequence have no such backstop.

Not a WCAG failure. Worth a `matchMedia().addEventListener( 'change', … )` when
convenient.

### CC-3 — Module numbering is inconsistent ⚠️

`basalio-core.js` comments say Module 4 Magnetic Button, Module 5 Scroll Sequence,
Module 8 Filterable Grid, Module 9 Custom Cursor. `README.md` lists a different
order. `WCAG-QA.md` numbers 1–3 following the README.

This draft uses README order: 1 Grid Reveal, 2 Case Study Transition, 3 Text
Reveal, 4 Magnetic Button, 5 Scroll Sequence, 6 Before/After, 7 Pinned Scroll,
8 Filterable Grid, 9 Custom Cursor. **Confirm or override before merging.**

### CC-4 — The referenced spec file does not exist ❌

`WCAG-QA.md` cites `basalio-product-spec.md` as its source of truth. No such file
exists under `~/Projects/products/basalio/`. Recover it from the archived
snapshots in `_archive/snapshots/`, or rewrite the checklist from shipped behavior
and re-point the reference.

### CC-5 — Enqueue invariant is undocumented and already violated ⚠️

Any module that can emit `data-basalio-lightbox` must enqueue the core. Pinned
Scroll violates this today. The rule appears nowhere in `README.md`'s "Adding a
module" section, which mentions `basalio_enqueue_core()` only as "call it if it
needs the shared script" — easy to read as "if *my* behavior needs JS."

Add to the README checklist, and consider making `basalio_media_wrap()` call
`basalio_enqueue_core()` itself when `$lightbox` is true, which removes the
invariant from human memory entirely.

---

## Priority order

1. **Pinned Scroll enqueue** (dead lightbox control). Blocking.
2. **Scroll Sequence 5.7** (discarded alt) and **5.6** (live-region spam).
3. **CC-1** front-end i18n, via the data-attribute pattern.
4. **8.10** empty-link fallback in Grid Item.
5. Read the four remaining stylesheets; close 5.8, 6.8, 4.6, L.10.
6. Settle CC-3 numbering, then merge into `WCAG-QA.md` and delete both drafts.
