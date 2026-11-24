# WordPress.org Screenshot Capture Guide — Basalio Free 0.6.8

This guide defines the 10 replacement screenshots required for the WordPress.org listing (`https://wordpress.org/plugins/basalio/`). Do not reuse the 0.6.7 screenshots currently in `.wordpress-org/screenshot-1.jpg` through `screenshot-7.jpg`, and do not publish the updated `readme.txt` captions to WordPress.org SVN until all 10 fresh 0.6.8 captures replace the old assets.

## Environment requirements

- **WordPress:** 7.1.5 (local capture install, e.g. `local-dev-site` or isolated WordPress 7.1.5 + SQLite install).
- **Active plugin:** Basalio Free **0.6.8** ONLY (`basalio/basalio.php`).
- **Pro add-on:** **DEACTIVATED / NOT INSTALLED**. No Pro blocks, no Pro sidebar controls, and no Pro-only styling may appear in any screenshot.
- **Browser:** Real Chrome desktop at **1600 × 900** (or 1600 × 1000, consistent aspect ratio across all 10 files) at 1x or 2x DPR, exported as clean PNG or high-quality JPG.
- **Data rules:** Use only fictional demo projects (no real client endorsements). Import demo pages through WordPress APIs or the block editor, never via direct database writes.

## Required filenames, order, subjects, and captions

Save all 10 files into `.wordpress-org/` (replacing the 7 existing 0.6.7 `.jpg` files so SVN has no stale assets):

| Filename | Block / View | State to capture | Exact `readme.txt` caption |
|---|---|---|---|
| `.wordpress-org/screenshot-1.png` | **Scroll Sequence** | Front end, scrolled to mid-sequence frame (e.g., frame 2 of 4) with visible counter and Previous/Next controls | `Scroll Sequence — one scroll, a new perspective. A frame-by-frame image sequence driven by scroll position.` |
| `.wordpress-org/screenshot-2.png` | **Case Study Transition** | Front end showing the linked project tile / case-study handoff layout in a portfolio grid | `Case Study Transition — carry an image from the project tile into its case-study page.` |
| `.wordpress-org/screenshot-3.png` | **Text Reveal** | Front end showing an editorial hero headline and subhead rendered by Text Reveal | `Text Reveal — a headline that arrives word by word.` |
| `.wordpress-org/screenshot-4.png` | **Grid Reveal** | Front end with one portfolio tile hovered or focused so its title and category caption overlay is clearly visible | `Grid Reveal — move into an image and let its caption appear.` |
| `.wordpress-org/screenshot-5.png` | **Filterable Grid** | Front end with an active discipline filter selected (e.g., Identity) showing filtered items and result count | `Filterable Grid — let visitors follow their interests with categories that filter your projects in place.` |
| `.wordpress-org/screenshot-6.png` | **Pinned Scroll** | Front end mid-scroll showing the pinned visual column held alongside scrolling case-study copy | `Pinned Scroll — hold a visual in place while the text moves past it.` |
| `.wordpress-org/screenshot-7.png` | **Before / After** | Front end showing two image states separated by the interactive draggable divider handle with labels | `Before / After — two images, one divider. Let people explore the change for themselves.` |
| `.wordpress-org/screenshot-8.png` | **Magnetic Button** | Front end showing the portfolio CTA button in its layout context (hovered state if clearly visible) | `Magnetic Button — a call-to-action that gently pulls toward the pointer.` |
| `.wordpress-org/screenshot-9.png` | **Custom Cursor** | Front end showing the bounded custom cursor accent active inside its scoped portfolio region | `Custom Cursor — a small signature for a pointer-driven experience.` |
| `.wordpress-org/screenshot-10.png` | **Gutenberg Editor** | Same finished page open in the native WordPress block editor with a Basalio block selected and its native sidebar controls visible (no Pro control center) | `The same page in Gutenberg — what you see in the editor is what you get.` |

## Verification before SVN upload

1. Confirm all 10 files exist in `.wordpress-org/` as `screenshot-1.png` through `screenshot-10.png` (or `.jpg`, consistent extension) and delete the old 0.6.7 `screenshot-*.jpg` files.
2. Open every image and visually confirm:
   - WordPress admin bar hidden on front-end captures (signed-out view or admin bar disabled).
   - No Pro notices, no Pro blocks, and no placeholder/broken images.
   - Caption order 1–10 in `readme.txt` matches `screenshot-1` through `screenshot-10` one-to-one.
3. Record image dimensions and SHA-256 hashes in a manifest before committing to WordPress.org SVN `/assets/`.
