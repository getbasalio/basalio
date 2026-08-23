# Basalio — Handoff

## WordPress.org submission blockers
1. Recapture .wordpress-org screenshots — current three show old block UI
2. "Tested up to" — verify all nine blocks against target WP version, then set
   readme.txt. Currently reverted to 7.0 pending real testing
3. /blocks copy audit — Grid Reveal listed with threshold/stagger, which it has no
   controls for. Marketing site repo. Open across multiple sessions
4. Extension points — DONE, must not change after submission

## Vertical slice — in progress
- motion.schema.json pipeline: editor generates from schema ✓
- Front-end render still enumerates known keys ✗ — slice not complete until generic
- 1 of 9 blocks migrated (Text Reveal). Eight hardcoded

## Open decisions
- Pricing: lifetime vs. bounded term. Blocked on Freemius support answer re:
  decoupling expiry from one-time payment
- Licensing: unlimited-site confirmed as intent, not yet configured
- Reduced motion is now a single fixed behavior, no user choice. Confirm intended

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
