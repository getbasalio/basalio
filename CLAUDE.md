# Basalio Pro — Engineering Rules

Repo: `basalio-pro` (separate plugin from the free `basalio` plugin on WordPress.org).

## Current Phase

Phase 7 = licensing + distribution via Freemius. Real Freemius product keys are not yet available — the Freemius product has not been created because the WordPress.org slug for the free plugin is still unassigned.

## Hard Invariants — do not violate, do not "improve on"

1. **Front-end rendering NEVER depends on license state.** An expired, deactivated, or absent license must not change a single pixel or attribute of what a site visitor sees. Motion overrides already saved into block attributes keep rendering forever. Degradation applies ONLY to the Pro authoring UI in `wp-admin`.
2. `basalio-pro` must never modify, wrap, or gate any code in the free `basalio` plugin. It consumes the free plugin's published extension points and nothing else.
3. The Freemius secret key must never be committed. Not in a file, not in history.
4. No hardcoded motion parameter names anywhere in Pro. The Pro panel renders from `motion.schema.json`. If adding a parameter to the schema requires editing any other file, the implementation is wrong.

## Task 7A — do now (no Freemius keys required)

Build the licensing seam so real keys drop in later without refactoring.

- Create `basalio-pro-config.php` exporting these constants with clearly-marked placeholder values:
  - `BASALIO_PRO_FS_ID`
  - `BASALIO_PRO_FS_PUBLIC_KEY`
  - `BASALIO_FS_ID` (parent)
  - `BASALIO_FS_PUBLIC_KEY` (parent)
  - `BASALIO_PRO_FS_CONFIGURED` — `false` while placeholders are in use.
- Vendor the Freemius WordPress SDK into `/freemius`. Do not modify SDK files.
- Write the `fs_dynamic_init()` add-on integration in the main plugin file, immediately after the plugin header, NOT inside `plugins_loaded` or any other hook. Reference the constants from above. Set `is_premium_only` true, `has_paid_plans` true, `is_org_compliant` false, and the parent array pointing at the free plugin.
  - When `BASALIO_PRO_FS_CONFIGURED` is false, skip SDK init entirely and show a single admin notice reading "Basalio Pro: licensing not configured (developer build)." Do not fatal, do not white-screen.
- Parent dependency guard: if the free `basalio` plugin is not active, or is below the minimum supported version, Pro must deactivate itself with a clear admin notice naming the required plugin and version. Test both branches.
- Introduce ONE license-state accessor used by all of Pro — e.g. `basalio_pro_can_author()`. Every admin-UI gate calls that function and nothing else. No scattered `is_paying()` calls across the codebase. Internally it wraps the Freemius check; while unconfigured it reads a filterable dev override so the UI is testable without keys.
- Define the three degraded-UI states explicitly and implement them:
  - **NO LICENSE**: control center visible but read-only, with an upgrade CTA.
  - **EXPIRED LICENSE**: control center read-only, existing saved values displayed accurately, renewal CTA. Saved values are NOT cleared and NOT reset to defaults.
  - **ACTIVE**: full authoring.
  - In all three states, front-end output is byte-identical for the same post content.
- Add a pre-commit guard (and a `.gitignore` entry) that fails the commit if a string matching the Freemius secret key pattern appears in staged files. Prove it works by staging a dummy key, showing the commit being rejected, then removing it.

## Task 7B — blocked, do not attempt

Do not stub, mock, or simulate any of the following. Stop and tell the user if you reach them:

- Real product IDs / public keys / secret key
- Sandbox-mode purchase testing
- Uploading a build to the Freemius Deployment section
- Anything that requires a live Freemius product to exist

## Verification — required

Every verification rule below assumes git. If a repo is not under version control, stop and report it before writing code.

- Paste raw terminal output. Summary lines like "all tests pass" are not evidence.
- For parent dependency guard and degraded states: prove each branch by forcing the state and pasting the resulting console/PHP output. A screenshot of the admin notice for each state.
- For invariant 1: render a post containing a Text Reveal block with non-default overrides under all three license states. Paste the rendered front-end markup for each. Diff them. The diff must be empty. If the diff is not empty, stop and report — do not adjust the test to pass.
- For the pre-commit guard: paste the failed-commit output.
- Local verification is not deployment verification. Say which is which.

## Commits

One commit per lettered item. After each, paste `git log --oneline -1` from the main checkout confirming the merge before reporting that item complete. If the harness forces a worktree, say so explicitly in the same message.

## Start of every session

Before writing code, report what already exists on disk for:

- the free plugin's extension points, and
- `motion.schema.json`.

If either is missing or draft-only, stop and tell the user — Pro's control center renders from the schema and consumes the extension points, so building against an imagined API is rework. Do not proceed past that check without the user's say-so.
