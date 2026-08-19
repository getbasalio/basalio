# Post-0.5.1 open items: accessibility verification, i18n payload, release hygiene

Carried over from the audit that produced 0.5.1 (`0fe77b6` → `23d48c6`). Nothing
here blocks internal beta testing. Items are grouped by what they cost and what
they buy.

Context: `WCAG-QA.md` is the reference for every row number below. The assertion
suite is `tests/verify-fixes.php` (38 checks). Fixtures: `tests/make-verify-pages.php`.

---

## 1. Browser verification (blocks promoting Asserted → Verified)

Every one of the 38 assertions inspects server-rendered HTML or source files.
**None of them run the JavaScript.** These four checks are the difference between
a QA doc that can be defended and one that can only be cited.

Run `php tests/make-verify-pages.php` first; it prints the fixture URLs.

- [ ] **Lightbox actually opens** (fixture: `verify-pinned-lightbox`).
      Pinned Scroll is deliberately the only Basalio block on that page. The core
      script being enqueued is asserted; that the click opens the overlay is not.
- [ ] **Lightbox focus behaviour** — rows L.2–L.5. Focus moves to the close
      button on open, Tab stays trapped, Escape dismisses, focus returns to the
      trigger on close. **Highest-risk untested surface in the plugin:** a modal
      dialog enabled on five blocks with zero runtime coverage.
- [ ] **Scroll Sequence live region** — row 5.6 (fixture: `verify-scroll-sequence`).
      With a screen reader running: scroll through the sequence and expect
      *silence*; press Next once and expect *exactly one* announcement.
      Empty live regions have real cross-screen-reader quirks on first
      population, so the markup being correct does not settle this.
- [ ] **Accessibility tree** — row 5.8. DevTools → Elements → Accessibility,
      enable full-page tree. Six `<img>` in the DOM, expect **one** image in the
      tree.

## 2. Unread stylesheets (three rows still Structural)

- [ ] `modules/before-after/style.css` → closes **6.8**. Confirm no `transition`
      on `--basalio-ba-pos` or the handle that would animate on keypress.
- [ ] `modules/magnetic-button/style.css` → closes **4.6**. Check displacement at
      `strength = 40` on the smallest allowed button: can it move out from under
      the pointer (2.5.8)?
- [ ] `assets/css/basalio-core.css` → closes **L.10**. Confirm lightbox
      open/close animation is gated on `prefers-reduced-motion`; JS uses a
      hardcoded 220ms teardown.

## 3. i18n: plumbing is done, payload is empty

CC-1 wired `wp-i18n` and `wp_set_script_translations()` for `basalio-core`, and
removed every hardcoded English string. But `languages/basalio.pot` predates all
of it, so a translator gets an incomplete string list. Missing at minimum:

- the editor duration-warning Notice and reworded help text
  (`modules/case-study-transition/index.js`)
- `__( 'Frame {current} of {total}' )`, only reachable since the `block.json`
  default was emptied
- the three `wp.i18n` strings in `assets/js/basalio-core.js`, which were never in
  the .pot because they used to be hardcoded

- [ ] Regenerate: `wp i18n make-pot . languages/basalio.pot --exclude=tests`
- [ ] Generate JS translations: `wp i18n make-json languages/ --no-purge`
- [ ] Prove CC-1 end to end with one test locale (currently unproven; strings
      correctly fall back to English, i.e. the old behaviour)

Needs WP-CLI, which the Blocs PHP build does not provide.

## 4. Release hygiene

- [ ] **`Tested up to: 7.0`** in `readme.txt`. WordPress.org validates this field
      and rejects values above the current release. Set it to a version actually
      tested against.
- [ ] Add a `tests/` assertion for `Tested up to`, alongside the existing
      release-consistency checks.

## 5. Known issues, non-blocking

- [ ] **7.6 — Pinned Scroll `media-right` reading order** (fixture:
      `verify-pinned-right`). DOM order is always media → story; `order: 2` moves
      the media visually, so tab order reaches an interactive image *before* the
      story text it visually follows (1.3.2 / 2.4.3). Only matters when the image
      is a link or lightbox trigger. The narrow-viewport rule already resets
      `order: 0`, so the pattern is understood. Fix: reorder in the DOM, use
      `order` only for the left variant.
- [ ] **4.7 — new-window indication.** Magnetic Button pairs `target="_blank"`
      with `rel="noopener noreferrer"` (the security half) but gives no visible
      or screen-reader-only signal that the link opens a new window (3.2.5).
- [ ] **8.11 — uncategorised grid items.** An item with no category matches no
      filter slug: it shows under "All" and silently vanishes under every other
      filter. Currently asserted as *behaviour*, not endorsed as *correct*.
      Product call: hide from filters, auto-assign an "Other" bucket, or warn in
      the editor.
- [ ] **L.8 — lightbox alt fallback.** Falls back to `alt=""`, correct for a
      decorative image, but leaves an unlabelled image in a dialog whose only
      other content is a close button.
- [ ] **L.9 — background not inert.** `aria-modal="true"` plus a Tab intercept,
      but neither `inert` nor `aria-hidden` on the rest of the page, so support
      depends on the AT honouring `aria-modal`.
- [ ] **6.2 — `aria-valuetext` is thin.** Before/After announces a bare
      percentage ("63%"). Something like "63% — Before" would tell a
      screen-reader user what the number means.

## 6. Cross-cutting

- [ ] **CC-2 — reduced motion read once at boot.** Magnetic Button, Custom
      Cursor, and Scroll Sequence evaluate `matchMedia().matches` a single time
      and then bind or skip permanently. Enabling "Reduce motion" mid-session has
      no effect until reload. Custom Cursor is insulated by its CSS; the other
      two are not. Not a WCAG failure. Fix:
      `matchMedia().addEventListener( 'change', … )`.
- [ ] **CC-3 — module numbering.** `basalio-core.js` comments number the
      behaviours 1, 2, 4, 8, 9, 5, matching neither `README.md` nor `WCAG-QA.md`.
      Reconcile to the numbering at the top of `WCAG-QA.md`.
- [ ] **CC-4 — missing spec file.** Earlier revisions of `WCAG-QA.md` cited
      `basalio-product-spec.md`, which does not exist anywhere in the project.
      The reference is removed and the current doc derives its checklist from
      shipped behaviour. Either recover the original from
      `_archive/snapshots/` or formally accept `WCAG-QA.md` as the checklist.

## 7. Brand, not accessibility

- [ ] **Chartreuse mismatch.** Custom Cursor defaults to `#C6F135` in both
      `render.php` and the CSS `var()` fallbacks. Scroll Sequence uses the same
      value for its focus outline. The documented Basalio brand chartreuse is
      `#DFFF00`. One of the two is wrong; decide which and make it consistent.
- [ ] **`Author: ChrowmDesigns`** was set in 0.5.1 to replace an `example.com`
      placeholder, on the assumption Basalio is a ChrowmDesigns product. Change
      if Basalio should stand as its own brand.

---

## Strategic note

The plugin is currently in better shape than the business around it. Marketing
site mid-build, demo videos unfinished, no pricing/licensing/distribution
decision made. Everything above is real but is diminishing returns against
getting 0.5.1 in front of ten beta testers, who will surface more than the next
twenty assertions would.

Suggested order: §1 whenever a screen reader is already open, §3 and §4 before
any public release, everything else as it comes up.
