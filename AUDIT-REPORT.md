# Basalio Plugin — Comprehensive Audit Report

**Plugin version audited:** 0.5.1  
**Date:** 2026-08-18  
**Scope:** All shipped documentation, PHP/JS/CSS source, every block, the shared runtime, and live functional testing against the local WordPress fixture site.

## Executive Summary

The plugin is well-architected, accessibility-conscious, and mostly follows WordPress and web standards. The existing static assertion suite passes 38/38 checks. However, live browser testing revealed one **critical functional bug** in Scroll Sequence, and code review surfaced several **high- and medium-severity** issues that affect real users across themes, devices, and assistive technologies.

| Severity | Count |
|---|---|
| Critical | 1 |
| High | 4 |
| Medium | 10 |
| Low | 12 |

**Critical finding:** Scroll Sequence manual Previous/Next buttons are overridden by the scroll driver, so they do not advance the visible frames. This fails the block's core claim of being "usable without scrolling" and breaks WCAG 2.5.1/2.1.1 for anyone who cannot scroll.

**Highest-priority fixes:**
1. Make Scroll Sequence buttons actually advance frames (scroll to the matching position or suppress scroll override after button use).
2. Replace the custom lightbox overlay with a native `<dialog>` element so background content is truly inert and focus trapping is browser-native.
3. Fix `index.asset.php` cache-busting versions (all still at 0.5.0).
4. Fix the Pinned Scroll `mediaPosition: right` tab-order / reading-order mismatch.
5. Make linked Grid Items with empty alt fallback to the visible title/category reliably.

---

## How this audit was performed

1. **Read all source files:** `basalio.php`, `includes/class-basalio.php`, all shared assets, every module's `block.json`, `index.js`, `index.asset.php`, `render.php`, and `style.css`.
2. **Ran the existing test harness:** `php tests/make-verify-pages.php` + `php tests/verify-fixes.php` against `../local-dev-site`.
3. **Researched current standards:** invoked `modern-web-guidance` (accessibility, HTML, CSS guides) and web search on WordPress block/theme compatibility.
4. **Live browser testing:** used Kimi WebBridge against `http://127.0.0.1:8091` to exercise the lightbox, Filterable Grid, Scroll Sequence, and Pinned Scroll DOM-order behavior.

---

## Static test harness results

```
38/38 assertions passed.
```

**Gaps identified in the static suite:**
- It does **not** verify that `index.asset.php` versions match the plugin version.
- It does **not** verify that Scroll Sequence buttons actually advance the visible frame.
- It does **not** verify interactive behavior of Before / After, Magnetic Button, Custom Cursor, Grid Reveal, or Case Study Transition.
- It does **not** verify focus return or Tab-cycle behavior in the lightbox.
- It does **not** verify responsive / theme-compatibility rendering.

---

## Critical Issues

### C1. Scroll Sequence Previous/Next buttons do not work

**Severity:** Critical  
**Block:** Scroll Sequence  
**Files:** `assets/js/basalio-core.js` (Scroll Sequence behavior), `modules/scroll-sequence/render.php`

**Problem:** The scroll driver calls `show(frameFromScrollPosition)` on every scroll tick. When a visitor clicks **Next**, `show(current + 1, true)` briefly advances the frame, but the pending (or next) scroll tick immediately recalculates progress from the unchanged scroll position and resets to the frame matching the scroll position. The visible frame therefore never changes. The announcer updates to "Frame 2 of 6" but the image stays on frame 1.

**Live test evidence:**
- Clicked **Next** three times on `/?page_id=90`.
- Result: `activeIndex: 0`, `status: "Frame 1 of 6"`, `announcer: "Frame 2 of 6"`.
- Scrolling down by 500px correctly reached `activeIndex: 5` (`Frame 6 of 6`).

**Impact:** WCAG 2.5.1 (Pointer Gestures) and 2.1.1 (Keyboard) failure for users who cannot scroll (motor impairment, keyboard-only, some screen-reader users). Also contradicts the block description and `readme.txt`.

**Correct fix:** Make the buttons authoritative. Two valid approaches:
1. **Scroll-to-frame:** On button click, `window.scrollTo({ top: targetScrollY, behavior: 'smooth' })` so the scroll driver naturally lands on the requested frame. Under reduced motion use `behavior: 'auto'`.
2. **Temporary override flag:** Set a flag `ignoreScroll = true` on button use; clear it on the next native `scroll` event. This is simpler but less aligned with the "scroll sequence" metaphor.

Recommended: approach 1, because it keeps scroll position and active frame in sync.

---

## High Issues

### H1. Lightbox is a custom overlay instead of native `<dialog>`

**Severity:** High  
**Files:** `assets/js/basalio-core.js` (lightbox behavior), `assets/css/basalio-core.css`

**Problem:** The lightbox builds a `<div role="dialog" aria-modal="true">` and manually intercepts `Tab` and `Escape`. It does **not** make the rest of the page inert. Background links/buttons remain in the tab order and clickable. The custom Tab trap only loops to the close button and does not handle `Shift+Tab` differently; with only one focusable control the practical effect is acceptable, but it is fragile and deviates from modern standards.

**Standards basis:** `modern-web-guidance` accessibility guide section 11: "Use the Native `<dialog>` Element … to automatically trap focus, dim backgrounds, and support dismissing via `Esc`." Also WCAG 2.4.3 (Focus Order), 2.4.7 (Focus Visible), 2.1.2 (No Keyboard Trap).

**Impact:** On browsers/AT that do not fully honor `aria-modal`, screen-reader users can navigate out of the dialog into the background page. The lightbox is the highest-risk surface in the plugin per `WCAG-QA.md`.

**Correct fix:** Refactor the lightbox to use a single reusable `<dialog>` element opened with `.showModal()`. Benefits:
- Browser handles focus trap and `Escape` close.
- Background content is automatically inert.
- `::backdrop` pseudo-element replaces the manual backdrop div.
- Reduced JS complexity.

Keep the same visual styling and reduced-motion behavior.

---

### H2. Scroll Sequence is unusable when controls are hidden under reduced motion

**Severity:** High  
**Files:** `assets/js/basalio-core.js` (Scroll Sequence behavior)

**Problem:** If the author sets `showControls: false`, the only drivers are scroll (default) and reduced-motion fallback (none). Under `prefers-reduced-motion: reduce` the scroll driver is skipped entirely, leaving the sequence stuck on frame 1.

**Impact:** A user with reduced-motion preferences on a page whose author hid the controls cannot access frames 2+. WCAG 2.3.3 (Animation from Interactions) requires motion be disableable; here disabling motion disables the content.

**Correct fix:** When `showControls` is false and reduced motion is active, force the controls to render (or add a non-motion way to step). The simplest robust fix: ignore `showControls` under reduced motion and always show the Previous/Next buttons. Alternatively, render a static filmstrip/all frames when reduced motion is on.

---

### H3. Pinned Scroll `mediaPosition: right` breaks reading order

**Severity:** High  
**Block:** Pinned Scroll  
**Files:** `modules/pinned-scroll/style.css`, `modules/pinned-scroll/render.php`

**Problem:** DOM order is always `media` → `story`. With `mediaPosition: right`, CSS `order: 2` visually places the media on the right. Keyboard Tab order and screen-reader reading order still follow the DOM, so focus reaches the media (which can be a lightbox trigger or link) **before** the story text it visually follows.

**Live test evidence:**
- On `/?page_id=89` (`mediaPosition: right`), `childOrder` is media → story, but `mediaComputedOrder: 2`, `storyComputedOrder: 0`.

**Impact:** WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order) failure when the media is interactive. Already flagged as open issue 7.6 in `WCAG-QA.md`.

**Correct fix:** Render DOM as story → media when `mediaPosition: right`, and use `order` only for the left variant. The narrow-viewport rule already resets `order: 0`, so the pattern is understood.

---

### H4. `index.asset.php` editor-script versions are stale (0.5.0)

**Severity:** High  
**Files:** `modules/*/index.asset.php` (all 10 modules)

**Problem:** Plugin version is 0.5.1, but every `index.asset.php` declares `'version' => '0.5.0'`. WordPress uses this value to cache-bust each block's editor script and style. After the 0.5.1 update, browsers will continue serving the old editor JS/CSS for every block.

**Static test evidence:**
```
modules/grid-reveal/index.asset.php:    'version'      => '0.5.0',
modules/text-reveal/index.asset.php:    'version'      => '0.5.0',
modules/grid-item/index.asset.php:      'version'      => '0.5.0',
...
```

The existing `verify-fixes.php` only checks `block.json` versions, not `index.asset.php`.

**Impact:** Silent cache corruption for all users updating from 0.5.0 to 0.5.1. New editor fixes/changes may not appear until a hard refresh.

**Correct fix:** Update every `index.asset.php` to `'version' => '0.5.1'`, and add an assertion in `verify-fixes.php` that checks asset versions against `BASALIO_VERSION`.

---

## Medium Issues

### M1. Linked Grid Item with empty alt can still be unnamed

**Severity:** Medium  
**Block:** Grid Item  
**File:** `modules/grid-item/render.php`

**Problem:** The fallback logic checks for the presence of `alt="[^"]+"` in the image markup. If the image has `alt=""` (empty but present), the regex does not match, so the fallback to title/category runs. However, the fallback only runs if the regex does **not** match. The issue is the opposite: if `wp_get_attachment_image()` returns an image **without** any `alt` attribute (e.g., attachment has no alt text and the markup omits it), the regex also fails, so fallback runs. But the fallback uses title then category. If both are empty, the link has no accessible name.

More importantly, the regex approach is fragile: if WordPress ever emits single-quoted attributes or omits `alt`, behavior changes.

**Impact:** WCAG 2.4.4 (Link Purpose) / 4.1.2 (Name, Role, Value) failure for linked grid items that have no alt, no title, and no category.

**Correct fix:** Build the accessible name explicitly in PHP rather than regex-parsing rendered HTML. Use a helper that returns the first non-empty value from: author alt override → attachment alt → title → category → empty string. Then add `aria-label` only when the resulting image alt would otherwise be empty.

---

### M2. Image alt override regex is fragile across blocks

**Severity:** Medium  
**Blocks:** Grid Reveal, Case Study Transition, Before / After, Scroll Sequence, Pinned Scroll, Grid Item  
**Files:** all `render.php` files that call `wp_get_attachment_image()`

**Problem:** Every block overrides the attachment alt with user-provided alt via:
```php
preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $alt ) . '"', $img_html, 1 );
```
This assumes WordPress always emits `alt="..."` with double quotes and that the attribute exists. If `wp_get_attachment_image()` ever omits the attribute (attachment has no alt), the explicit user alt is silently ignored.

**Impact:** Author-entered alt text may not be honored, producing inaccessible images.

**Correct fix:** Use `wp_get_attachment_image()` with a filtered alt, or build the image tag manually when an explicit alt is provided. A robust helper:
```php
function basalio_get_image_html( $id, $url, $alt, $attr ) {
    if ( $id ) {
        $attr['alt'] = '' !== $alt ? $alt : '';
        $html = wp_get_attachment_image( $id, 'large', false, $attr );
        if ( $html ) {
            return $html;
        }
    }
    if ( $url ) {
        return sprintf( '<img src="%s" alt="%s" />', esc_url( $url ), esc_attr( $alt ) );
    }
    return '';
}
```
Passing `alt` into `wp_get_attachment_image()`'s `$attr` array makes WordPress use it directly, no regex needed.

---

### M3. Pinned Scroll image uses `loading="lazy"` for likely LCP element

**Severity:** Medium  
**Block:** Pinned Scroll  
**File:** `modules/pinned-scroll/render.php`

**Problem:** The pinned image is typically above the fold and may be the Largest Contentful Paint element. It is rendered with `loading="lazy"`, which tells the browser to defer loading until near the viewport.

**Standards basis:** `modern-web-guidance` HTML guide section 3: "DON'T apply `loading="lazy"` to above-the-fold or hero images."

**Impact:** Slower LCP, worse Core Web Vitals.

**Correct fix:** Use `loading="eager"` for the Pinned Scroll image (the block is by definition in view while the story scrolls). Consider adding `fetchpriority="high"` only when the block is the first/hero block; otherwise `eager` is sufficient.

---

### M4. Before / After disables all touch actions on mobile

**Severity:** Medium  
**Block:** Before / After  
**File:** `modules/before-after/style.css`

**Problem:** `.basalio-before-after__frame { touch-action: none; }` prevents the browser from handling any touch gesture on the frame, including vertical scrolling. On a phone the slider may occupy most of the viewport, making the page hard to scroll past.

**Impact:** Mobile usability and WCAG 2.5.1/2.1.1 concern for touch users.

**Correct fix:** Change to `touch-action: pan-y;` so vertical scrolling still works while horizontal drags drive the slider. Verify pointer events still update the divider.

---

### M5. Magnetic Button new-tab links lack new-window indication

**Severity:** Medium  
**Block:** Magnetic Button  
**Files:** `modules/magnetic-button/render.php`, `modules/magnetic-button/index.js`

**Problem:** When `opensInNewTab` is true, the link gets `target="_blank" rel="noopener noreferrer"` but no visible or screen-reader text indicating a new window. `WCAG-QA.md` 4.7 already flags this.

**Impact:** WCAG 3.2.5 (Change on Request) advisory failure; users are surprised when a new tab opens.

**Correct fix:** Append a visually hidden span or icon to the button label when `opensInNewTab` is true, e.g., `<span class="screen-reader-text">(opens in a new tab)</span>`.

---

### M6. Before / After slider `aria-valuetext` is uninformative

**Severity:** Medium  
**Block:** Before / After  
**Files:** `modules/before-after/render.php`, `assets/js/basalio-core.js`

**Problem:** `aria-valuetext` is just `"63%"`. A screen-reader user does not know what the percentage means. `WCAG-QA.md` 6.2 already notes this.

**Impact:** Poor screen-reader usability despite correct slider semantics.

**Correct fix:** Use `aria-valuetext` that combines the value with the labels, e.g., `"63% — Before: Design sketch, After: Final website"` or at minimum `"63% before, 37% after"`.

---

### M7. Reduced-motion preference is read only at boot

**Severity:** Medium  
**Blocks:** Magnetic Button, Custom Cursor, Scroll Sequence  
**File:** `assets/js/basalio-core.js`

**Problem:** Each of these behaviors checks `matchMedia('(prefers-reduced-motion: reduce)').matches` once and returns early if true. If a user enables reduced motion mid-session (e.g., via system settings while the page is open), the behavior does not update. `WCAG-QA.md` CC-2 already flags this.

**Impact:** A user who turns on reduced motion after loading the page continues to see motion.

**Correct fix:** Add `matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', ...)` listeners (with feature detection). For Magnetic Button and Custom Cursor, the change listener can reset custom properties and remove classes. For Scroll Sequence, it can enable/disable the scroll driver and show/hide controls as needed.

---

### M8. Filterable Grid uncategorised items disappear silently

**Severity:** Medium  
**Block:** Filterable Grid  
**File:** `modules/filterable-grid/render.php`

**Problem:** An item with no category gets `data-basalio-item-category=""` and matches only the "All" filter. Selecting any category filter hides it with no explanation. `WCAG-QA.md` 8.11 already flags this as a product decision.

**Impact:** Confusing UX; visitors may think items are broken.

**Correct fix:** Either (a) auto-assign an "Uncategorized" filter button so items are always reachable, or (b) render a visible note such as "X items not in any category" when filters hide uncategorised items. Option (a) is preferable.

---

### M9. Custom Cursor default color mismatches brand

**Severity:** Medium  
**Block:** Custom Cursor  
**Files:** `modules/custom-cursor/render.php`, `modules/custom-cursor/style.css`

**Problem:** The fallback/default color is `#C6F135`, while `readme.txt` and `WCAG-QA.md` describe the Basalio brand chartreuse as `#DFFF00`. `WCAG-QA.md` already flags this for brand review.

**Impact:** Brand inconsistency.

**Correct fix:** Decide the canonical brand color and update both `render.php` and `style.css` fallbacks to match.

---

### M10. Shared helper functions are defined inside render.php files

**Severity:** Medium  
**Files:** `modules/text-reveal/render.php`, `modules/before-after/render.php`

**Problem:** `basalio_tr_item()` and `basalio_ba_image()` are conditionally defined inside render files to avoid redeclaration on multiple block renders. This is a code smell: it clutters render files, makes the functions hard to unit-test, and risks accidental redeclaration if the guard is ever removed.

**Impact:** Maintainability and testability.

**Correct fix:** Move shared image helpers to `includes/class-basalio.php` or a dedicated `includes/helpers.php` file. Wrap in `if ( ! function_exists( ... ) )` once. Do the same for the alt-override helper recommended in M2.

---

## Low Issues

### L1. `editor-styles-wrapper` fallback may be stale

**Block:** Text Reveal  
**File:** `modules/text-reveal/style.css`

**Problem:** The editor override uses `.editor-styles-wrapper .basalio-text-reveal__item`. The block-canvas iframe class in newer WordPress may differ. Reliance on this class is fragile.

**Correct fix:** Use `.basalio-editor .basalio-text-reveal__item` consistently (already present) and remove the potentially redundant `.editor-styles-wrapper` rule, or verify the class still exists in the supported WordPress range (6.3+).

---

### L2. Case Study Transition uses `url_to_postid()` without fallback UX

**Block:** Case Study Transition  
**File:** `modules/case-study-transition/render.php`

**Problem:** Auto-pairing relies on `url_to_postid( $link_url )`. This function can be slow, fails on custom post types with non-standard permalinks, and fails on external URLs. When it fails, `$basalio_vt_name` is empty and no view transition occurs.

**Impact:** Silent loss of the transition in some URL configurations.

**Correct fix:** Surface a Pair ID warning in the editor when `url_to_postid()` returns 0 and the link is non-empty. Also consider caching or document that custom post types require a manual Pair ID.

---

### L3. Case Study Transition focus safety net races with transition

**Block:** Case Study Transition  
**File:** `assets/js/basalio-core.js`

**Problem:** When `pagereveal` is supported, focus is restored via `e.viewTransition.finished.then(focusHeading, focusHeading)`. A 400ms `setTimeout(focusHeading)` also runs in case `pagereveal` already fired. If the transition finishes after 400ms, `focusHeading()` may run twice; the guard prevents duplicate focus, but the timeout is arbitrary and could race.

**Correct fix:** Use a single `requestAnimationFrame` check or clear the timeout inside the `pagereveal` handler once it fires.

---

### L4. Lightbox close animation delays `hidden` state under reduced motion

**File:** `assets/js/basalio-core.js`

**Problem:** `close()` always waits 220ms before setting `overlay.hidden = true`, even when reduced motion removes the CSS transition.

**Correct fix:** Check `matchMedia('(prefers-reduced-motion: reduce)').matches` and hide immediately when true.

---

### L5. Scroll Sequence `100vh` height ignores dynamic viewports

**Block:** Scroll Sequence  
**File:** `modules/scroll-sequence/style.css`

**Problem:** Wrapper height uses `calc(... * 100vh)`. On mobile browsers with dynamic toolbars, `100vh` may include or exclude toolbar area inconsistently, leading to layout jumps or incorrect scroll distance.

**Correct fix:** Use `100dvh` with a `100vh` fallback for older browsers:
```css
height: calc(var(--basalio-seq-screens, 3) * 100vh);
height: calc(var(--basalio-seq-screens, 3) * 100dvh);
```

---

### L6. Lightbox close button can be clipped on small viewports

**File:** `assets/css/basalio-core.css`

**Problem:** `.basalio-lightbox__close { position: absolute; top: -8px; right: -8px; }` places the button outside the padded overlay area. On small screens or when the image fills the viewport, the button may be partially off-screen or hard to tap.

**Correct fix:** Move the close button inside the safe padding area, e.g., `top: 16px; right: 16px;`, and ensure it meets the minimum 44×44 CSS px touch target.

---

### L7. Before / After frame click does not prevent text selection

**Block:** Before / After  
**File:** `assets/js/basalio-core.js`

**Problem:** Clicking the frame (outside the handle) jumps the divider but does not call `preventDefault()`, so the user may start a text selection drag.

**Correct fix:** Add `e.preventDefault()` in the frame `pointerdown` handler.

---

### L8. Hard-coded font families may not load

**Blocks:** Before / After, Scroll Sequence  
**Files:** `modules/before-after/style.css`, `modules/scroll-sequence/style.css`

**Problem:** Both use `'IBM Plex Mono', ui-monospace, monospace` but the plugin does not enqueue IBM Plex Mono. If the theme does not load it, the label/counter falls back to system monospace.

**Impact:** Visual inconsistency, not functional.

**Correct fix:** Either enqueue IBM Plex Mono from the plugin or remove it and rely on `ui-monospace, monospace`.

---

### L9. `example.com` hygiene item is already fixed

`WCAG-QA.md` lists "`example.com` in the plugin header" as a hygiene/release item, but `basalio.php` already uses `https://basalio.com`. No action needed; the item can be removed from the open-items table.

---

### L10. `WCAG-QA.md` CC-4 references missing spec file

The document references a missing `basalio-product-spec.md`. This is already noted as open. It does not affect shipped code, but the team should decide whether to recover the spec or accept `WCAG-QA.md` as the source of truth.

---

### L11. `basalio-core.js` module comment numbering is inconsistent

Already tracked as CC-3 in `WCAG-QA.md`. Comments number behaviors 1, 2, 4, 8, 9, 5, 6. Non-blocking but should be reconciled for maintainability.

---

### L12. Scroll Sequence status element formatting includes whitespace

The rendered status contains tabs/newlines around the text. This is cosmetic and does not affect functionality, but the static assertion trims the text. Consider rendering the status inline to avoid extra whitespace in the accessibility tree.

---

## Theme Compatibility Findings

The plugin intentionally uses `!important` and locked color values to prevent theme style leakage. This is a valid trade-off for an opinionated block set, but it creates limitations:

1. **Color customization:** Blocks do not inherit theme palette colors. Global Styles in block themes cannot override most block colors. Consider exposing more CSS custom properties (e.g., `--basalio-btn-bg`, `--basalio-fg-ink`) as theming hooks and documenting them.
2. **Spacing:** Most blocks support `spacing.margin`. Padding support is inconsistent (Text Reveal yes, most others no). Consider whether inner padding should be configurable.
3. **Typography:** Text Reveal supports `fontSize` and `lineHeight`; Magnetic Button supports `fontSize`; other blocks do not. This is acceptable for image-focused blocks.
4. **Classic themes:** The heavy use of `!important` on `<button>` and `<a>` styles is specifically designed to survive classic theme stylesheet overrides. This works but may conflict with theme customizer colors.
5. **Dark mode:** The plugin uses a fixed light palette. On dark themes, blocks like Filterable Grid and Magnetic Button will render with their own paper/ink colors rather than adapting. Consider adding `prefers-color-scheme` media queries or CSS variables for dark mode.

---

## Proposed Fix Priority and Verification Plan

| # | Issue | Files to change | Verification |
|---|---|---|---|
| 1 | C1 Scroll Sequence buttons | `assets/js/basalio-core.js` | Browser: click Next, assert visible frame advances and stays advanced. |
| 2 | H1 Lightbox native dialog | `assets/js/basalio-core.js`, `assets/css/basalio-core.css` | Browser: open/close, Tab cycles only inside dialog, background inert. |
| 3 | H2 Scroll Sequence reduced motion + hidden controls | `assets/js/basalio-core.js`, `modules/scroll-sequence/render.php` | Browser with forced reduced motion: controls visible, buttons work. |
| 4 | H3 Pinned Scroll right-order | `modules/pinned-scroll/render.php`, `style.css` | Browser: Tab order follows visual order on media-right. |
| 5 | H4 Asset versions | `modules/*/index.asset.php`, `tests/verify-fixes.php` | `verify-fixes.php` passes new assertion. |
| 6 | M1/M2 Image alt robustness | all image `render.php`, new helper file | `verify-fixes.php` + manual checks for attachment with no alt. |
| 7 | M3 Pinned Scroll lazy loading | `modules/pinned-scroll/render.php` | Inspect rendered `loading` attribute. |
| 8 | M4 Before / After touch-action | `modules/before-after/style.css` | Mobile/touch: vertical scroll works, horizontal drag moves divider. |
| 9 | M5 Magnetic Button new-tab text | `modules/magnetic-button/render.php`, `index.js`, `style.css` | Rendered markup includes visually hidden new-window text. |
| 10 | M6 Before / After aria-valuetext | `modules/before-after/render.php`, `assets/js/basalio-core.js` | Accessibility tree shows meaningful value text. |
| 11 | M7 Reduced-motion live listeners | `assets/js/basalio-core.js` | Toggle reduced motion mid-session; behavior updates. |
| 12 | M8 Uncategorised filter | `modules/filterable-grid/render.php`, `style.css` | Uncategorised items always reachable. |
| 13 | M9 Custom Cursor color | `modules/custom-cursor/render.php`, `style.css` | Decide and unify color. |
| 14 | M10 Shared helpers | new `includes/helpers.php`, update render files | `verify-fixes.php` still passes. |
| 15 | L1–L12 | various | Minor cleanups, low risk. |

---

## Conclusion

Basalio is a thoughtfully built plugin, but it is not yet "100% functional against every possible scenario." The most urgent fix is the Scroll Sequence button behavior, which is currently broken in the live build. After that, the lightbox refactor and the stale asset versions are the highest-impact changes for real users. The remaining items are medium/low severity and can be addressed in follow-up passes.
