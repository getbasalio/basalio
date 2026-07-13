=== Basalio ===
Contributors: chrowmdesigns
Tags: portfolio, interaction, animation, gutenberg, blocks
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A narrow, accessibility-first interaction toolkit of native Gutenberg blocks for portfolio and case-study sites.

== Description ==

Basalio is a small, opinionated set of interaction blocks for people who build portfolio and case-study sites in the WordPress block editor. It is not a page builder and not a general animation engine. It does one category of thing, the tasteful motion that makes creative work feel considered, and it tries to do it well.

Every block is a native Gutenberg block. There is no separate canvas, no shortcode, and no page-builder lock-in. You add a Basalio block the same way you add a paragraph, fill in a couple of clearly labeled fields, and the effect renders on the front end.

**What is in the box**

* **Grid Reveal**, a portfolio tile that reveals its caption on hover, focus, or tap.
* **Case Study Transition**, a shared-element morph from a grid tile to its case-study page.
* **Text Reveal**, a choreographed entrance for a headline, subhead, and optional button.
* **Magnetic Button**, a call-to-action that gently pulls toward the visitor's cursor.
* **Before / After**, a draggable, keyboard-operable image comparison slider.
* **Filterable Grid**, a portfolio grid visitors can filter by discipline, with a Grid Item child block.
* **Custom Cursor**, a soft cursor accent for a bounded region of the page.
* **Scroll Sequence**, a frame-by-frame image sequence driven by scroll position.
* **Pinned Scroll**, an image that stays pinned while its story scrolls beside it.

**Built for restraint and access**

* Every interaction is keyboard operable and screen-reader labeled.
* Motion respects the visitor's "reduce motion" system setting.
* Pointer effects are disabled on touch, where they would only get in the way.
* The shared front-end script loads only on pages that actually contain a Basalio block, never site-wide.

== Installation ==

1. Upload the `basalio` folder to `/wp-content/plugins/`, or install the plugin zip through Plugins, Add New, Upload Plugin.
2. Activate Basalio through the Plugins screen in WordPress.
3. In the block editor, add any block whose name starts with "Basalio" and fill in its fields.

There is nothing to configure globally. Each block carries its own settings.

== Frequently Asked Questions ==

= Is this a page builder? =

No. Basalio is a focused set of interaction blocks. It works inside the standard WordPress block editor and leaves the rest of your layout to your theme.

= Will the effects slow down my site? =

The shared script is small and loads only on pages that contain a Basalio block. Pages without one load nothing extra.

= Are the blocks accessible? =

Yes. Every interaction is operable by keyboard, exposes the right ARIA state to assistive technology, and honors the "reduce motion" preference.

= Does it work with my theme? =

Basalio targets standard block themes and classic themes that support the block editor. The blocks inherit your theme's spacing and alignment controls where it makes sense.

== Screenshots ==

1. Grid Reveal, a caption sliding up over a portfolio tile.
2. Before / After, a wireframe resolving into the finished site.
3. The block editor view, with Basalio's labeled fields and inline guidance.

== Changelog ==

= 0.5.0 =
* Big configurability pass across every block:
* Image blocks (Grid Reveal, Case Study Transition, Filterable Grid items, Pinned Scroll) gain a corner-radius field, an optional Link URL, and an optional built-in lightbox (accessible, Escape to close).
* Grid Reveal: choose a tile aspect ratio.
* Before / After: choose an aspect ratio (16:9, 1:1, portrait, and more).
* Text Reveal and Case Study Transition: adjustable animation speed.
* Magnetic Button: now sits exactly where placed, with a left/center/right alignment option.
* Filterable Grid: arrow-key navigation across the filter buttons, plus a button-radius control.
* Custom Cursor: upload your own cursor image or SVG.
* Scroll Sequence: no more letterbox padding, an optional counter, and custom counter text.
* Pinned Scroll: adjustable image width.

= 0.4.4 =
* Extended theme hardening to text color: on some light themes the Filterable Grid filter labels and Magnetic Button text picked up the theme's button text color and became unreadable. Their colors are now locked to the block palette.

= 0.4.3 =
* Theme compatibility: hardened every block control (the Grid Reveal overlay, Filterable Grid pills, Before / After handle, Scroll Sequence steps) so a theme's generic button styling can no longer repaint them. Fixes Grid Reveal tiles showing as solid blocks in some classic themes, for example Twenty Twenty-One.

= 0.4.2 =
* Removed the last stray accent colors (a couple of focus outlines and the Custom Cursor default), so every block now uses the black, white, and chartreuse palette consistently.

= 0.4.1 =
* Fixed the Before / After slider not taking keyboard focus when grabbed by mouse or touch, so the arrow keys now move the divider after a click, as they already did after tabbing to it.

= 0.4.0 =
* Beta hardening pass ahead of launch.
* Wired plugin translation loading and per-block editor-script translations, and added a translation template.
* Verified every block renders cleanly in WordPress with both full and empty content, with no PHP notices or warnings.
* Confirmed output escaping and input sanitization across all blocks.

= 0.3.0 =
* Completed the full block set, nine insertable blocks plus one child block.
* Redesigned the block editor chrome to match native Gutenberg styling.

== Upgrade Notice ==

= 0.4.0 =
Beta-hardening release. Recommended for anyone testing Basalio ahead of launch.
