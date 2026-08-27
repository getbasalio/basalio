# PHPCS suppression audit

This file tracks inline `phpcs:ignore` comments whose justification is an
assertion about escaping happening somewhere else. `render.php:129` in the
0.6.3 submission proved that class of claim can be wrong: the line was
silenced with `OutputNotEscaped` while a user-controlled value was concatenated
into a CSS selector. Post-submission, each site below should have its escaping
traced end-to-end rather than taken on trust.

## Composed-HTML `OutputNotEscaped` suppressions

These suppress `WordPress.Security.EscapeOutput.OutputNotEscaped` on an `echo`
of a variable that is claimed to be built from escaped parts or core-rendered
content.

| File:line | Variable / expression | Current justification |
|-----------|----------------------|-----------------------|
| `modules/before-after/render.php:121` | `echo $basalio_after;` | Built via `esc_*` / `wp_get_attachment_image` above. |
| `modules/before-after/render.php:122` | `echo $basalio_before;` | Built via `esc_*` / `wp_get_attachment_image` above. |
| `modules/magnetic-button/render.php:95` | `echo $basalio_link_attrs;` | Built via `esc_url` above. |
| `modules/magnetic-button/render.php:96` | `echo $basalio_new_tab_text;` | Built via `esc_html__()` above. |
| `modules/case-study-transition/render.php:162` | `echo $basalio_motion_data;` | Built via `esc_attr` above. |
| `modules/case-study-transition/render.php:163` | `echo $basalio_img_html;` | Built via `wp_get_attachment_image` / `esc_*` above. |
| `modules/case-study-transition/render.php:187` | `echo basalio_media_wrap( ... );` | Built from escaped parts in `basalio_media_wrap()`. |
| `modules/case-study-transition/render.php:210` | `echo $basalio_motion_data;` | Built via `esc_attr` above. |
| `modules/case-study-transition/render.php:211` | `echo $basalio_img_html;` | Built via `wp_get_attachment_image` / `esc_*` above. |
| `modules/case-study-transition/render.php:220` | `echo $basalio_img_html;` | Built via `wp_get_attachment_image` / `esc_*` above. |
| `modules/pinned-scroll/render.php:99` | `echo $basalio_story_html;` | Built above from core-rendered inner blocks / escaped image parts. |
| `modules/pinned-scroll/render.php:100` | `echo $basalio_media_html;` | Built above from escaped image parts. |
| `modules/pinned-scroll/render.php:102` | `echo $basalio_media_html;` | Built above from escaped image parts. |
| `modules/pinned-scroll/render.php:103` | `echo $basalio_story_html;` | Built above from core-rendered inner blocks. |
| `modules/scroll-sequence/render.php:93` | `echo $basalio_frames_html;` | Built via `esc_*` / `wp_get_attachment_image` above. |
| `modules/filterable-grid/render.php:127` | `echo $basalio_grid;` | Each item is escaped in `grid-item/render.php`. |
| `modules/grid-item/render.php:77` | `echo $basalio_frame_open . $basalio_img_html . $basalio_frame_close;` | Built via `esc_url` / `wp_get_attachment_image` above. |
| `modules/text-reveal/render.php:198` | `echo $basalio_parts;` | Built from `esc_html` / `esc_url` / `esc_attr` above. |
| `modules/grid-reveal/render.php:145` | `echo $basalio_img_html;` | Built from `esc_*` / `wp_get_attachment_image` above. |
| `modules/grid-reveal/render.php:159` | `echo $basalio_toggle;` | Built from `esc_url` / `esc_attr` / `esc_html` above. |

## Accepted suppressions

The following are **not** on the trace list because they rely on core APIs that
perform escaping internally and are the standard block-render pattern:

- `echo $basalio_wrapper` where `$basalio_wrapper = get_block_wrapper_attributes( ... )`
  (appears in every block render file).
- `echo $content` for inner blocks rendered by core.
- `WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents` for
  reading local plugin files (`includes/class-basalio.php:236,595`).

## Follow-up work

For each composed-HTML entry above, verify:

1. Every user-supplied value that reaches the echoed variable is escaped at the
   point it enters the string.
2. No value is transformed after escaping in a way that re-introduces raw HTML
   or CSS (e.g., concatenation into a selector, URL, or style property).
3. If a value is used in a non-HTML context (CSS selector, `view-transition-name`,
   inline style), the escaping function matches that context, not just
   `esc_attr` or `esc_html`.
4. The suppression comment is narrowed to the specific sniff or replaced with
   an inline escape if the trace shows one is missing.
