# Basalio

The bedrock your work stands on. Basalio is a small, opinionated set of native
Gutenberg blocks that add tasteful, accessible interactions to WordPress
portfolio and case-study sites. It is not a page builder, and not a general
animation engine.

Every interaction respects `prefers-reduced-motion`, works with a keyboard, and
loads its script only on pages that actually use a Basalio block. Never
site-wide.

## Blocks

All nine roadmap blocks ship today, plus one child block used inside the Filterable Grid:

- **Grid Reveal.** A portfolio tile that reveals its caption on hover, focus, or tap.
- **Case Study Transition.** A shared-element view transition from a grid tile to its case-study page, with a plain-navigation fallback.
- **Text Reveal.** A staged entrance for a headline, subhead, and optional button. CSS only, and the real text stays readable to screen readers.
- **Magnetic Button.** A call-to-action that pulls toward the pointer. Pointer only, off on touch and under reduced motion.
- **Before / After.** An interactive comparison slider built as a real ARIA slider, operable by drag, touch, and arrow keys.
- **Filterable Grid** (with its **Grid Item** child). A portfolio grid visitors filter by discipline, using real buttons with `aria-pressed` and a live-region count.
- **Custom Cursor.** A branded cursor accent that trails the pointer inside an area. Pointer only, off on touch and reduced motion, and it never covers a click target.
- **Scroll Sequence.** A frame sequence tied to scroll, with Previous and Next buttons so it also works without scrolling (WCAG 2.5.1).
- **Pinned Scroll.** A pinned image beside a story that scrolls past it. Pure CSS sticky, so it never traps focus and degrades to a normal stack under reduced motion.

`WCAG-QA.md` documents the accessibility behavior of each block, with how it was verified.

## Folder structure

```
basalio/
├── basalio.php                  Plugin header, version constant, activation, basalio_enqueue_core()
├── includes/
│   └── class-basalio.php        Loader: registers shared assets, auto-discovers every module
├── assets/
│   ├── js/
│   │   ├── basalio-core.js       One shared front-end script. A behavior registry that runs
│   │   │                         each module's behavior. Enqueued only when a block is present.
│   │   └── basalio-editor.js     Shared editor helpers (the branded block panel, field, and tip)
│   └── css/
│       └── basalio-editor.css   Styling for the editor chrome
├── modules/
│   ├── grid-reveal/             Each module folder holds: block.json, index.js, index.asset.php,
│   ├── case-study-transition/   render.php, and style.css.
│   ├── text-reveal/
│   ├── magnetic-button/
│   ├── before-after/
│   ├── filterable-grid/
│   ├── grid-item/               Child of filterable-grid (no standalone insert)
│   ├── custom-cursor/
│   ├── scroll-sequence/
│   └── pinned-scroll/           Pure CSS, ships no JS
├── uninstall.php                Removes the plugin's version option on delete
├── LICENSE                      GPL-2.0 full text
├── README.md
└── WCAG-QA.md
```

## How it fits together

**Dynamic render.** Every block renders through its own `render.php` (server side)
rather than a static `save()`. Markup and escaping live in one place, images get
real responsive `srcset` and `alt`, and later markup fixes never invalidate
already-saved blocks.

**No build step.** Editor scripts are written directly against WordPress's bundled
`wp.*` globals, with a hand-authored `index.asset.php` for each. There is no npm,
webpack, or JSX. The plugin depends on nothing outside WordPress core.

**One shared front-end script.** `basalio-core.js` is registered once and enqueued
from a block's `render.php`, so it loads only on pages that use a block. Each
module registers an isolated behavior, so adding a module never touches an
existing one.

**One shared editor UI.** `basalio-editor.js` and `basalio-editor.css` give every
block the same clear chrome: a header with the block name, a plain description of
what it does, and labeled fields with inline hints. New blocks inherit it for free.

## Adding a module

The loader globs `modules/*/block.json`, so a new block is additive. You never
edit an existing module.

1. Create `modules/<your-block>/` with its own `block.json`, `index.js`,
   `index.asset.php`, `render.php`, and `style.css`. Copy the shape of an existing
   module.
2. Give it a unique name in `block.json`, for example `basalio/your-block`.
3. Call `basalio_enqueue_core()` from its `render.php` if it needs the shared script.
4. If it needs front-end JavaScript, append a new `Basalio.register(...)` behavior
   to `basalio-core.js`. Do not edit the behaviors above it.
5. Keep styles under your block's own class prefix so stylesheets never collide.

## Requirements

WordPress 6.3 or newer, PHP 7.4 or newer.

## License

GPL-2.0-or-later. WordPress core is GPL, so a plugin that builds on its PHP is a
derivative work and inherits GPL. This also keeps the plugin eligible for the
WordPress.org directory and compatible with commercial licensing tools like
Freemius or EDD.
