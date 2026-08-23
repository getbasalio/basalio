# Basalio — Handoff

## WordPress.org submission blockers
1. Recapture .wordpress-org screenshots — current three show old block UI
2. "Tested up to" — verify all nine blocks against target WP version, then set
   readme.txt. Currently reverted to 7.0 pending real testing
3. /blocks copy audit — Astro `src/data/blocks.ts` lists Grid Reveal controls
   (`data-delay`, `data-duration`, `data-easing`) that the block does not have.
   No `threshold` claim exists in the repo. Rewrite every block's `controls`
   array as user-facing capability language, then update the live copy.
4. Extension points — DONE, must not change after submission

## Motion pipeline
- Vertical slice COMPLETE: schema-only param change renders on the front end,
  proven via __pipeline_test_param
- 1 of 9 blocks migrated (Text Reveal). Eight hardcoded
- Schema audit COMPLETE: unimplemented params removed, name mismatches fixed to
  match the runtime, and accessibility params (`reducedMotionFallback`,
  `dragStep`, `focusTarget`, `announceChange`, `resultCount`) wired and tested
- Migration (mechanical) and feature-building (Pinned Scroll scroll-scrub,
  Scroll Sequence frame subsetting, Filterable Grid layout animation) are
  separate decisions, tracked in `docs/MOTION-ROADMAP.md`

## Open decisions
- Pricing: lifetime vs. bounded term. Blocked on Freemius support answer re:
  decoupling expiry from one-time payment
- Licensing: unlimited-site confirmed as intent, not yet configured
- Reduced motion is a single fixed behavior, no user choice. Confirmed intended
- Pinned Scroll / Scroll Sequence / Filterable Grid: unimplemented specced
  behavior cut from schemas and logged in `docs/MOTION-ROADMAP.md`

## Patterns (Pro track — does NOT gate .org)
- 72 patterns, all ship at launch, 12 categories x 6
- Motion: 25 static by design / 29 optional / 18 load-bearing
- Contract work: ~95h. Tier 3 (gallery/catalog/steps) is the critical path
- Pattern packs architecture designed, not built. Needed before library grows

## Standing rules
- Raw terminal output, never summary counts
- One commit per item
- A control that does not do what it says is a defect, not a follow-up
- Front-end output never depends on license state
- If you discover something while answering a question, report it — do not convert
  it into a menu option
