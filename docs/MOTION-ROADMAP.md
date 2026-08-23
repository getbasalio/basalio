# Basalio Motion Roadmap

These params were removed from `motion.schema.json` because they described behavior
not implemented in the runtime. The schemas must be a source of truth, not a
wishlist. Each item below is a candidate post-launch feature release.

## R1 — Quick wins (≤ 1 h each)

| Block | Param | What would be built | Est. |
|---|---|---|---|
| Before / After | `duration` | Snap duration for keyboard/page-key jumps | 0.5 h |
| Before / After | `easing` | Easing for the same snap transition | 0.5 h |
| Case Study Transition | `transitionDuration` | Numeric view-transition duration (currently hardcoded 300 ms) | 1 h |
| Case Study Transition | `easing` | View-transition easing | 1 h |
| Custom Cursor | `lag` | Pointer-follow lag / trailing delay | 1 h |
| Filterable Grid | `emptyState` | Custom "no results" message | 0.5 h |
| Grid Reveal | `duration` | Reveal transition duration | 0.5 h |
| Grid Reveal | `easing` | Reveal transition easing | 0.5 h |
| Magnetic Button | `duration` | Return-to-center transition duration | 0.5 h |
| Magnetic Button | `easing` | Return-to-center easing | 0.5 h |
| Magnetic Button | `maxDistance` | Clamp magnetism to a max pixel radius | 1 h |
| Scroll Sequence | `startFrame` | Trim sequence start to a frame other than the first | 1 h |
| Scroll Sequence | `endFrame` | Trim sequence end to a frame other than the last | 1 h |

**R1 total: ~10 h**

## R2 — Real work (2–4 h)

| Block | Param | What would be built | Est. |
|---|---|---|---|
| Grid Reveal | `stagger` | Staggered reveal when multiple tiles enter together | 2–3 h |
| Filterable Grid | `layoutDuration` / `layoutEasing` | Animated layout reflow on filter change (FLIP or equivalent) | 3–4 h |
| Scroll Sequence | `scrubSmoothness` | Interpolated scroll-to-frame mapping instead of direct index | 2–3 h |

**R2 total: ~8 h**

## R3 — Architectural (8–12 h)

| Block | Param | What would be built | Est. |
|---|---|---|---|
| Pinned Scroll | `pinDuration` / `scrubSmoothness` / `easing` | Full scroll-scrub engine replacing pure CSS sticky | 8–12 h |

**R3 total: ~10 h**

## Not planned

- `Scroll Sequence.frameCount` — frame count is derived from the uploaded `frames`
  array. Generating or limiting frames via a count param is a different feature
  than what the runtime does today.

## How to reintroduce a param

1. Implement the runtime behavior.
2. Add the param back to the block's `motion.schema.json`.
3. Ensure `tests/verify-all-schemas.php` and the runtime-consumer test pass.
4. Update this roadmap.
