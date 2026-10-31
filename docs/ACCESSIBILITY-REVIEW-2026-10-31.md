# Basalio accessibility review, 31 October 2026

The selected Mac and physical iPhone workflows passed the owner's screen-reader review. Four problems found during the wider evaluation were corrected in local release candidates **Free 0.6.8** and **Pro 0.3.0-rc7**. Windows with NVDA and a native Windows contrast theme remains untested because no Windows computer is available.

The target is WCAG 2.2 AA. This report supports the specific results below; it is not a full conformance declaration or a guarantee for every authored website. Neither candidate has been published by this review.

## Corrections

1. Free's default muted text changed from `#838383` to `#646464` in both the editor and frontend. The old default achieved 3.79:1 on white; the replacement achieves 5.62-5.92:1 on the shipped light surfaces. This fixes the Filterable Grid status/category text finding.
2. Escape from Pro's sandboxed live preview now closes its enclosing inspector and returns focus to the opening button. A nested media dialog consumes the first Escape; a second closes the inspector. The parent accepts messages only from its own preview window.
3. Before After received a light halo around its dark focus outline. Scroll Sequence uses a light outline against its dark control overlay, making focus visible over imagery.
4. With reduced motion, Scroll Sequence's visible frame, counter and accessible image now agree. Previous/Next selects the corresponding image without jumping the page. The static CSS fallback no longer overrides the enhanced controls.

## Actual screen-reader and device observations

Mac: macOS 27.2, Safari 27.2 and Chrome 156.0.8078.76. VoiceOver was enabled and the owner confirmed speech was audible. The tools could not capture its spoken output, so these are owner-reported outcomes, not transcripts.

Phone: the owner identified an **iPhone 17 Pro Max using Safari**. The iOS version was not supplied. The physical phone was used over local Wi-Fi; it was not an iPhone simulator test.

| Workflow | Platform | Owner's result |
| --- | --- | --- |
| Frontispiece: pause rotation, select Features, hear selected state, read the panel and reach its links | Mac, Safari, VoiceOver | All steps work |
| Scroll-synced showcase: navigate Forma through North, activate names, read associated image descriptions/captions | Mac, Safari, VoiceOver | Works |
| Impact Rail: expand panels, read details/links, continue without a trap | Mac, Safari, VoiceOver | Works |
| Control Center: search Frontispiece, open/close details, return focus; Content Switcher inspector controls, enter/leave its preview and continue | Mac, Chrome, VoiceOver | Both workflows work |
| Content Switcher, Story Steps, Expanding Panels and Before After: read content, change panels, adjust slider by swiping up/down | iPhone, Safari, VoiceOver; portrait and landscape | All checks pass |
| Control Center: navigate, search, open/close details and inspector, reach controls without clipping or sideways page scrolling | iPhone, Safari; portrait and landscape | All checks pass |
| Filterable Grid: choose Digital and hear results; Scroll Sequence: Next frame; Showcase Rail: Next | iPhone, Safari, VoiceOver | All workflows pass |
| Media Dialog: open, transcript, close; Pinned Lightbox: open/close; Standard Tabs: change tab with sensible reading/focus position | iPhone, Safari, VoiceOver | All workflows pass |

These owner observations were collected before the final targeted corrections. The corrected code was retested automatically. A separate owner screen-reader retest of the final ZIPs has not been recorded.

## Automated and visual evidence

- **131 accessibility acceptance checks:** 20 rendered block fixtures; reflow at 1440, 768, 390 and 320 CSS pixels; automated WCAG checks; slider keyboard and click operation; manual tab activation; inspector Escape, focus return and nested dialogs.
- **91 Control Center checks:** responsive layout, text spacing, keyboard/focus behavior, dialogs, live inspectors and reduced motion. Its 13 axe states have zero violations and zero incomplete results.
- **11 focused checks:** image focus treatments, reduced-motion frame/status/announcement consistency, no page jump, native dialog relationship and Chromium forced-colors emulation.
- **96 Free regression assertions** and **367 Pro regression assertions** passed. All nine Free motion schemas passed the schema/runtime checks. Eight palette checks passed, including a known-bad color rejection.
- **83 pattern previews** passed the packaging integrity check.

The focus-contrast regression gate was also run against the former dark Scroll Sequence outline and failed as expected. Restoring the corrected outline passed. No accessibility rule was disabled or suppressed.

Actual Chrome browser zoom was exercised through the native application UI. At 200%, all seven Control Center routes reflowed within a 735 CSS-pixel viewport. At 400%, those routes plus ten representative block fixtures reflowed within 367 CSS pixels. The separate automated 320 CSS-pixel checks cover the narrower reflow width. Chrome was returned to 100%. VoiceOver was observed off in System Settings at the end of the native review.

### Manual review of axe incomplete results

All 20 block fixtures had zero automated violations. Their incomplete results required inspection rather than being counted as automatic passes:

| Finding | Review and result |
| --- | --- |
| Before After labels over images | The black 62% label backing produces at least 6.05:1 for the shipped text, even over a white image. Focus halo screenshot reviewed. |
| Scroll Sequence controls over images | The dark 72% control backing produces at least 7.75:1 for the shipped text, even over a white image. Light focus outline and frame counter reviewed. |
| Impact Rail text overlapping a transparent panel button | Actual text/background pairs exceed 10:1 for headings and 9.99:1 for the green panel's body text. Revealed content remains readable. |
| Media Dialog's reference to a closed dialog | The referenced ID exists and belongs to its native dialog. Opening/closing was covered in the owner and automated workflows. |
| Media heading flagged as partially obscured | Inspected after fonts loaded; readable in the captured fixture. Its text contrast is 18.41:1. |
| Metric visual counter flagged as partially obscured | Counter and label are readable in the captured fixture. Its text contrast is 15.88:1. |

The accompanying JSON files retain the original axe incomplete results and computed styles. Screenshots document the reviewed states. The earlier `block-device-review` files record the initial failing contrast check; `all-blocks-axe.json` and `acceptance.log` record the corrected run.

## Remaining scope

- Windows/NVDA and a native Windows contrast theme: **not tested; no Windows computer available**. Chromium forced-colors emulation is supplemental evidence, not a substitute for that platform.
- A configured enquiry-form provider and complete submission/error flows: the local fixture exercises the available fallback, not a configured provider integration.
- Full-page/process coverage for a deployed website, including author-supplied content, color choices, alternative text, captions/transcripts and third-party integrations.

WCAG conformance applies to complete pages and processes using accessibility-supported technology. Automated tests and selected successful screen-reader tasks do not establish every success criterion. See [WCAG 2.2 conformance requirements](https://www.w3.org/TR/WCAG22/#conformance-reqs). The zoom/reflow methods are described in W3C's [Resize Text guidance](https://www.w3.org/WAI/WCAG22/Understanding/resize-text.html) and [Reflow guidance](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html).

## Reproduction

The Pro repository contains `tests/browser/accessibility-acceptance.cjs`, `tests/browser/focus-contrast.cjs` and the local-only fixture helper `tests/browser/helpers/accessibility-review.php`. Install the helper as an MU plugin only in a disposable WordPress installation with `WP_ENVIRONMENT_TYPE=local`. Set `BASALIO_QA_URL`, `BASALIO_QA_OUTPUT` and the test account's `BASALIO_QA_USER` / `BASALIO_QA_PASSWORD` through environment variables. Browser dependencies are in `tests/browser/package.json`.

Free contains `tests/verify-palette-contrast.py` and the corrected image-overlay focus check in `tests/verify-fixes.php`. Test fixtures, credentials and review documents are excluded from the plugin ZIPs.
