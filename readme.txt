=== Basalio ===
Contributors: chrowmdesigns
Tags: portfolio, interaction, animation, gutenberg, blocks
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.6.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessible interaction blocks for portfolio and case-study sites, built for the WordPress block editor.

== Description ==

Basalio is a focused set of interaction blocks for people who build portfolio and case-study sites in the WordPress block editor. It is not a page builder or a general animation engine. It adds the motion that makes creative work feel considered, and nothing else.

Every block is a native Gutenberg block. There is no separate canvas, no shortcode, and no page-builder lock-in. Add a Basalio block the same way you add a paragraph, set its options in the block sidebar, and the effect renders on the front end.

**What is in the box**

* **Grid Reveal**, a portfolio tile that reveals its caption on hover, focus, or tap.
* **Case Study Transition**, a cross-page shared-element transition from a grid tile to its case-study page.
* **Text Reveal**, a choreographed entrance for a headline, subhead, and optional button.
* **Magnetic Button**, a call-to-action that gently pulls toward the visitor's cursor.
* **Before / After**, a draggable, keyboard-operable image comparison slider.
* **Filterable Grid**, a portfolio grid visitors can filter by discipline, with a Grid Item child block.
* **Custom Cursor**, a soft cursor accent for a bounded region of the page.
* **Scroll Sequence**, a frame-by-frame image sequence driven by scroll position.
* **Pinned Scroll**, an image that stays pinned while its story scrolls beside it.

**Built for restraint and access**

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

Yes. Every interactive element is keyboard operable and screen-reader labeled. Pointer effects like Custom Cursor have no keyboard equivalent by design — they are decorative and never carry information or function.

= Does it work with my theme? =

Basalio targets standard block themes and classic themes that support the block editor. The blocks inherit your theme's spacing and alignment controls where it makes sense.

== Screenshots ==

1. The Basalio block collection in the WordPress editor.
2. Basalio blocks ship with high-contrast keyboard focus indicators.
3. Text Reveal lets you choose word, line, or character reveals, plus duration, easing, and trigger threshold.
4. A draggable before/after image comparison slider, operable by mouse, touch, or keyboard arrows.
5. Filterable Grid sorts portfolio items by category with keyboard-operable filter buttons.
6. Portfolio tiles reveal captions on hover, keyboard focus, or tap. They never autoplay.
7. Case Study Transition sets transition speed and the post-navigation focus target.

== Changelog ==

= 0.6.0 =
* Accessibility audit debt release. Resolves the findings from the 0.5.1 audit.
* Scroll Sequence: Previous/Next buttons now advance frames authoritatively by scrolling to the matching position; they work under reduced motion even when controls are hidden.
* Lightbox: rebuilt as a native `<dialog>` element opened with `.showModal()`, giving the browser-native focus trap, Escape-to-close, and inert background.
* Pinned Scroll: fixed reading/focus order when the media is positioned on the right.
* Image alt handling is now robust across every image block via a shared helper; author-entered alt always wins, including an intentional empty value.
* Filterable Grid: uncategorised items now get their own "Uncategorized" filter button.
* Before / After: improved mobile touch handling and more informative screen-reader value text.
* Magnetic Button, Custom Cursor, and Scroll Sequence now respond live to a mid-session `prefers-reduced-motion` change.
* Custom Cursor default color unified to the Basalio brand chartreuse (#DFFF00).
* Editor asset versions and release tags aligned to 0.6.0.

= 0.5.1 =
* Accessibility fixes found in a full audit of every block. Details below.
* Pinned Scroll: the "expand image" lightbox button did nothing when a Pinned Scroll block was the only Basalio block on a page, because the shared script was never loaded. Fixed, and fixed at the root so no future block can repeat it.
* Scroll Sequence: alt text typed in the editor is no longer discarded in favour of the media library's own alt.
* Scroll Sequence: scrolling no longer interrupts screen readers on every frame. The frame count is now announced only when the Previous or Next buttons are used.
* Scroll Sequence: frames that are not on screen are properly hidden from screen readers, so a six-frame sequence is announced as one image, not six.
* Filterable Grid: a portfolio item that is linked but has no alt text now takes its accessible name from the visible title, instead of being an unlabelled link.
* Filterable Grid and Scroll Sequence: the result count, the "All" filter label, and the frame counter are now translatable. Previously they reverted to English on non-English sites.
* Lightbox: the dialog's own labels are now translatable.
* Case Study Transition: the morph now defaults to 300ms and is limited to 150-600ms, with a warning in the editor above 400ms. It previously defaulted to 500ms and allowed up to 3 seconds, which is uncomfortably long for a large moving image. **If you set a custom speed above 600ms it will now render at 600ms.** Blocks left at the default get a faster morph than before.
* Added an automated accessibility test suite (33 checks) that runs against real rendered output.

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

= 0.6.0 =
Accessibility audit debt release. Resolves all findings from the 0.5.1 audit, including a non-working Scroll Sequence button, a custom lightbox replaced with a native dialog, and a focus-order fix for right-positioned Pinned Scroll media. Recommended for all users.

= 0.5.1 =
Accessibility fix release. One block's lightbox button did not work at all; several blocks were not translatable. The Case Study Transition morph is now faster by default and capped at 600ms.

= 0.4.0 =
Beta-hardening release. Recommended for anyone testing Basalio ahead of launch.
