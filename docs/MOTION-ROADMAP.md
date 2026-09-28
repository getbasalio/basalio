# Basalio Motion Roadmap

These params were removed from `motion.schema.json` because they described behavior
not implemented in the runtime. The schemas must be a source of truth, not a
wishlist. Each item below is a candidate post-launch feature release.

## Current configurator state

Eight of the nine blocks expose only `reducedMotionFallback` plus one real param
(or zero). Grid Reveal has `revealStyle` plus `reducedMotionFallback`. A
configurator with one dropdown is not a configurator, so this roadmap is
re-sorted by what gives the Pro control center something worth paying for.

## Tier 1 — Block-defining motion (highest Pro configurator value)

These turn a thin block into a flagship motion control.

| Block | Params | What it adds to the Pro configurator | Est. |
|---|---|---|---|
| Pinned Scroll | `pinDuration`, `scrubSmoothness`, `easing` | Replaces pure CSS sticky with a cinematic scroll-scrub engine. Users get pin length, smoothness, and easing controls — the difference between a static sidebar and a story-driven sequence. | 8–12 h |
| Filterable Grid | `layoutDuration`, `layoutEasing` | Animated layout reflow on filter change. This is the visible reason to choose this block over a plain WordPress query loop; it makes filtering feel intentional. | 3–4 h |
| Grid Reveal | `duration`, `easing`, `stagger` | Gives portfolio tiles a signature reveal. Duration + easing tune the feel; stagger creates a cascading "wave" when multiple tiles enter together. | 3–4 h |

**Tier 1 total: ~15–20 h**

## Tier 2 — Meaningful second control (medium value)

These add a visible or tactile control that users will notice, but they do not redefine the block.

| Block | Params | What it adds to the Pro configurator | Est. |
|---|---|---|---|
| Magnetic Button | `maxDistance` | Clamps how far the button follows the pointer. Changes the effect from subtle to bold without touching strength. | 1 h |
| Custom Cursor | `lag` | Adds trailing delay to the cursor accent. A small but distinctive brand-feel control. | 1 h |
| Scroll Sequence | `scrubSmoothness` | Interpolates scroll position to frame index. The difference between a flipbook and a smooth cinematic scrub. | 2–3 h |
| Before / After | `duration`, `easing` | Polishes the keyboard/page-key snap so the divider does not jump. | 1 h |
| Magnetic Button | `duration`, `easing` | Refines the return-to-center motion after the pointer leaves. | 1 h |
| Case Study Transition | `transitionDuration`, `easing` | Note: `transitionDuration` already exists as a block attribute. Re-introducing it as a motion param adds little unless the View Transitions API gains easing support. | 2 h |

**Tier 2 total: ~8–9 h**

## Tier 3 — Utility / niche (low configurator value)

Useful for completeness, but not reasons to buy Pro.

| Block | Params | What it adds to the Pro configurator | Est. |
|---|---|---|---|
| Filterable Grid | `emptyState` | A text field for the "no results" message. Accessibility copy, not motion. | 0.5 h |
| Scroll Sequence | `startFrame`, `endFrame` | Trims the usable frame range. Handy for long sequences, minor differentiator. | 2 h |

**Tier 3 total: ~2.5 h**

## Not planned

- `Scroll Sequence.frameCount` — frame count is derived from the uploaded `frames`
  array. Generating or limiting frames via a count param is a different feature
  than what the runtime does today.

## How to reintroduce a param

1. Implement the runtime behavior.
2. Add the param back to the block's `motion.schema.json`.
3. Ensure `tests/verify-all-schemas.php` and the runtime-consumer test pass.
4. Update this roadmap.

## Logged follow-up: unimplemented Text Reveal "Character" splitUnit

`modules/text-reveal/motion.schema.json` previously advertised a `character`
value for `splitUnit`, but `modules/text-reveal/render.php` only implements
`line` (default) and `word`. Selecting `character` silently fell through to the
line-by-line reveal. The enum value was removed from the schema for the 0.6.3
release.

To reintroduce it:
1. Implement per-character splitting in `render.php` (respecting Unicode
   grapheme clusters, e.g., with `mb_str_split()` or `IntlBreakIterator` with a
   polyfill).
2. Add per-character CSS so each character animates independently.
3. Update the schema and verify the editor panel shows the option.

## Resolved in 0.6.6: CSS-value-context hardening

Token declarations and motion parameter values now pass through a shared,
restricted CSS-value validator. Token fallback values are validated before
interpolation, and the final `basalio_token_css` filter is validated afterward.
Only a single `:root` custom-property block is accepted. This addresses the
WordPress.org review of 0.6.4 as well as the related motion pipeline paths.
Regression coverage lives in `tests/verify-output-safety.php`.
