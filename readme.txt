=== Basalio: Animation and Interaction Blocks for Portfolios ===
Contributors: basalio
Tags: animation, gutenberg, portfolio, scroll, interaction, case-study, hover, reveal, blocks, accessibility
Requires at least: 6.3
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 0.6.8
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Animation and interaction blocks for portfolio sites: Gutenberg-native scroll, hover and reveal effects, accessible by default, free forever.

== Description ==

Basalio adds Framer-quality animation and interaction to WordPress portfolios, natively, in Gutenberg, with no page builder and no animation engine to learn.

If you build portfolio and case-study sites in the WordPress block editor, Basalio gives you the motion that makes creative work feel considered: a caption that surfaces on hover, a headline that arrives word by word, an image sequence that advances as you scroll, a project tile that carries its image into the case study.

It is not a page builder. It is not a general animation engine. It is nine focused interaction blocks for the details that make a portfolio worth exploring, and nothing else.

**Who it's for**

Photographers, designers, architects, studios, agencies, and anyone whose work needs to be seen, not just listed. If your site is a portfolio or a set of case studies, Basalio was built for you.

**What you get, free, forever**

Nine free blocks, all native Gutenberg blocks. Add one the same way you add a paragraph, set its options in the block sidebar, and the effect renders on the front end. No shortcodes, no separate canvas, no page-builder lock-in.

* **Grid Reveal**: a portfolio tile that reveals its caption on hover, focus, or tap.
* **Filterable Grid**: a portfolio grid visitors can filter by discipline, with a Grid Item child block.
* **Case Study Transition**: a shared-element transition that carries an image from a grid tile into its case-study page.
* **Text Reveal**: a choreographed entrance for a headline, subhead, and optional button, word by word.
* **Before / After**: a draggable, keyboard-operable image comparison slider.
* **Custom Cursor**: a soft cursor accent for a bounded region of the page.
* **Pinned Scroll**: an image that stays pinned while its story scrolls beside it.
* **Scroll Sequence**: a frame-by-frame image sequence driven by scroll position, like a flipbook, with Previous / Next buttons so it works without scrolling too.
* **Magnetic Button**: a call-to-action that gently pulls toward the visitor's cursor.

**See it in action**

Browse the live WordPress examples at https://basalio.com/ to explore seven fictional sites across architecture, ceramics, photography, floristry, hospitality, music and UX/UI design. The collection includes 30 pages running on WordPress with native Basalio blocks.

These examples combine Basalio Free with a preview build of the separate Basalio Pro add-on. Some features shown are not included in this free plugin. The sites are demonstrations, not client endorsements. Enquiry forms validate through WordPress without sending email or retaining submissions.

The screenshots below were captured in WordPress with Basalio Free 0.6.8 and no Pro add-on. Each finished example is followed by the same page in Gutenberg. The demo theme supplies the page layout, typography and colors; Basalio supplies the interactive blocks. The fictional brands, layouts and images are demonstration content, not bundled templates or client endorsements. Photography includes AI-generated imagery.

**Built for restraint and access**

* Motion respects the visitor's "reduce motion" system setting.
* Pointer effects are disabled on touch, where they would only get in the way.
* The shared front-end script loads only on pages that actually contain a Basalio block, never site-wide.
* Every interactive element is keyboard operable and screen-reader labeled. Pointer effects like Custom Cursor are decorative by design, they never carry information or function.
* Your content stays intact. Blocks degrade to static, semantic markup. Grids stay grids, text stays text. Nothing is stored in a proprietary format.

**Free is not a trial**

The blocks ship on WordPress.org under GPL-2.0-or-later: no locked blocks, no watermarks, no instance limits. Nothing in the free version expires or gets pulled into Pro later.

**Open development**

Basalio is developed in the open on GitHub, including the full WCAG 2.2 audit report and the automated accessibility checks that run on every change. Bug reports and pull requests are welcome there. For usage questions, use the support forum here on WordPress.org.

**What about Pro?**

Pro is a separate, optional add-on, coming in Q1 2027: a control center in the editor sidebar for live-tuning all nine blocks, plus every future block. Founder licenses are $149 one time for unlimited sites, then $249. Founder licenses stay valid, keep every future block, and are never re-billed. Pro is refundable at any time until the control center ships. Once it ships, the standard 30-day refund applies.

You do not need Pro to use Basalio. The free plugin is complete on its own.

== Installation ==

1. In WordPress, go to Plugins > Add New, search for "Basalio", and click Install, or upload the `basalio` folder to `/wp-content/plugins/`, or install the plugin zip through Plugins, Add New, Upload Plugin.
2. Activate Basalio through the Plugins screen in WordPress.
3. In the block editor, add any block whose name starts with "Basalio" and fill in its fields.

There is nothing to configure globally. Each block carries its own settings.

== Frequently Asked Questions ==

= Is this a page builder? =
No. Basalio is a focused set of interaction blocks. It works inside the standard WordPress block editor and leaves the rest of your layout to your theme.

= Is this an animation plugin? =
Yes, animation and interaction, built specifically for portfolios. Unlike general animation engines that add effects to any block, Basalio's blocks are designed around portfolio moments: revealing a caption, filtering a body of work, moving from a tile into a case study, and scrolling through a story.

= Does it work with Elementor, Divi, or other page builders? =
No. Basalio is Gutenberg-native. It works with any block theme or classic theme running the block editor. No page builder or premium theme is required.

= Will the effects slow down my site? =
The shared script is small and loads only on pages that contain a Basalio block. Pages without one load nothing extra. There is no runtime framework, no jQuery, and no global stylesheet overriding your theme.

= Are the blocks accessible? =
Yes. Every interactive element is keyboard operable and screen-reader labeled. Motion respects the visitor's reduce-motion setting, and Scroll Sequence and Before / After work fully from the keyboard. Pointer effects like Custom Cursor have no keyboard equivalent by design, they are decorative and never carry information or function.

= Does it work with my theme? =
Basalio targets standard block themes and classic themes that support the block editor. The blocks inherit your theme's spacing and alignment controls where it makes sense.

= Can I use it on client sites? =
Yes. Basalio is GPL-2.0-or-later, with no instance limits and no watermarks. Use it on as many sites as you like, including client work.

= Is the free version really free? =
Yes. Free is not a trial. No locked blocks, no watermarks, no instance limits, and nothing in the free version expires or gets pulled into Pro later.

= What do I get with Pro? =
Pro, coming Q1 2027, adds a control center in the editor sidebar for live-tuning all nine blocks, plus every future block. It is a separate add-on, you do not need it to use the free plugin.

= What happens if I stop using Basalio? =
Your content stays intact. Blocks degrade to static, semantic markup. Grids stay grids, text stays text. Nothing is stored in a proprietary format, and if Basalio disappears tomorrow, what you have keeps working.

== Screenshots ==

1. Scroll Sequence: one scroll, a new perspective. A frame-by-frame image sequence driven by scroll position.
2. Case Study Transition: carry an image from the project tile into its case-study page.
3. Text Reveal: a headline that arrives word by word.
4. Grid Reveal: move into an image and let its caption appear.
5. Filterable Grid: let visitors follow their interests with categories that filter your projects in place.
6. Pinned Scroll: hold a visual in place while the text moves past it.
7. Before / After: two images, one divider. Let people explore the change for themselves.
8. Magnetic Button: a call-to-action that gently pulls toward the pointer.
9. Custom Cursor: a small signature for a pointer-driven experience.
10. The same page in Gutenberg: what you see in the editor is what you get.

== Changelog ==

= 0.6.8 =
* Verify compatibility with WordPress 7.1.5, including block editor saves and front-end interactions.
* Improve default muted-text contrast on light surfaces in the front end and editor.
* Keep comparison and scroll-sequence keyboard focus visible over dark imagery.
* Synchronize the visible frame and accessible status in reduced-motion Scroll Sequence, without jumping the page on manual selection.

= 0.6.7 =
* Add a live WordPress examples link to the Basalio dashboard, with clear Free and Pro preview labels.
* Use plain punctuation in the image comparison slider's accessible label.

= 0.6.6 =
* Validate filtered CSS token names, values, and the final stylesheet before inline output.
* Apply the same CSS validation to motion parameters and token fallbacks.
* Escape composed block HTML at output while preserving supported interactive controls.

= 0.6.5 =
* Image blocks that render outside a named link or button now fall back to the attachment's media-library alt text when the author leaves the alt field empty.
* Pinned Scroll link path no longer produces an unnamed anchor: it adds an aria-label only when the linked image has no alt text.

= 0.6.4 =
* Move per-instance transition CSS to the stylesheet instead of an inline <style> element.
* Harden output escaping and validate the view-transition name as a CSS custom-ident.
* Remove an unimplemented Character split option from Text Reveal.

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
