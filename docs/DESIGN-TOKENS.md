# Basalio design-token notes

## `--basalio-accent-text` serves two incompatible roles

`--basalio-accent-text` is currently a single token used for:

1. Text/icons on the acid accent surface (`--basalio-accent-solid: #dfff00`).
2. The auto-generated WordPress background utility `.has-basalio-accent-text-background-color`.

The current value `#5a7000`:

- Passes AA text on acid solid: **4.91:1**.
- Fails AA text on accent hover (`#d4f000`): **4.33:1**.
- Fails AA when used as a background behind default body text (`#171717`): **3.20:1** (was 7.36:1 with the old `#8fb300`).

No single colour can satisfy both roles because the required luminance ranges
conflict: text on `#dfff00` must be dark (L ≤ 0.148 for 4.5:1), while a
background behind `#171717` text must be light (L ≥ 0.214 for 4.5:1).

### Options

- **Split the token.** Keep `--basalio-accent-text` for text on accent
  (dark), and introduce a separate `--basalio-accent-bg-soft` or similar for
  the background utility (light enough for dark text).
- **Drop the background utility.** If `--basalio-accent-text` is not intended
  to be used as a background, remove the generated background class from the
  palette registration.
- **Accept the trade-off.** Document that `.has-basalio-accent-text-background-color`
  must always be paired with a light text colour class.

This is logged as a design-token architecture issue, not an immediate runtime
blocker.
