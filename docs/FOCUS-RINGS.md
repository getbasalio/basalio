# Basalio focus-indicator notes

## Single-tone `--basalio-focus` ring fails on dark surfaces

The shared focus colour `--basalio-focus: #202020` is used as a single-tone
outline across the plugin. It works on the default light page surface, but it
cannot work against both light and dark backgrounds.

Measured on staging (0.6.3):

| Control | Measured adjacent surface | Ring vs surface |
|---|---|---|
| `.basalio-filterable-grid__filter` | `body` `#ffffff` | **16.29:1** |
| `.basalio-before-after__handle` | handle itself `#fcfcfc` | **15.88:1** |
| `.basalio-media-expand` | `body` `#ffffff` | **16.29:1** |
| `.basalio-media-link` | `body` `#ffffff` | **16.29:1** |

If an author places any of these controls on an ink surface (`--basalio-ink: #0a0a0a`):

| Control | Ring vs ink | Result |
|---|---|---|
| `.basalio-filterable-grid__filter` | **1.22:1** | Fails WCAG 1.4.11 (3:1) |
| `.basalio-before-after__handle` | **1.22:1** | Fails |
| `.basalio-media-expand` | **1.22:1** | Fails |
| `.basalio-media-link` | **1.22:1** | Fails |

This is the same defect class as the Pinned Scroll lightbox close button: a
single-tone ring that is only visible against one of its adjacent surfaces.

## Recommended fix

Apply the same dual-tone pattern already used for `.basalio-lightbox__close`:

```css
outline: 2px solid var(--basalio-ink);          /* dark inner ring */
outline-offset: 0;
box-shadow: 0 0 0 4px var(--basalio-paper);     /* light outer ring */
```

On light surfaces the dark inner ring is visible; on dark surfaces the light
outer ring is visible. Both rings clear 3:1 against their neighbours.

Because `--basalio-focus` is used in many modules, the fix should be applied
systemically — either by changing the token convention or by updating every
focus rule — rather than as one-off overrides.
