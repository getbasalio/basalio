# Basalio — Marketing Website

The pre-launch marketing site for **Basalio**, a narrow, opinionated interaction
toolkit (native Gutenberg blocks) for WordPress portfolio and case-study sites.

Static HTML/CSS/JS — no build step, no framework. Pre-launch, so every call to
action is **"join the beta,"** not "buy."

> **Note:** this is the *marketing website*, not the Basalio plugin. The plugin
> is a separate project and is licensed **GPLv2-or-later** (a WordPress
> requirement). This website is open source under the **MIT License** — see
> [LICENSE](LICENSE).

## Pages

| File | Purpose |
|---|---|
| `index.html` | Home. Hero with the live demo grid, positioning, the three beta "Exhibits" (with working demos), performance bar, pricing summary, beta waitlist. |
| `pricing.html` | Pricing: three tiers, full comparison table, licensing/updates FAQ. |
| `roadmap.html` | Roadmap: the six post-beta modules as Exhibits 04–09, clearly marked *not yet built*. |
| `og-image.html` | Source for the 1200×630 social-share image. |
| `og-image.png` | Rendered OG image (referenced by `og:image` on the sub-pages). |
| `context/basalio-product-spec.md` | Product spec — positioning, modules, pricing rationale, roadmap. Reference only. |

All pages share one sticky header: **Modules · Performance · Roadmap · Pricing ·
Join the beta**. Home uses in-page anchors; sub-pages link back to `index.html#…`.

## Run locally

No build. Either open `index.html` directly, or serve the folder so relative
links and the OG image resolve cleanly:

```bash
# Python 3
python3 -m http.server 8080
# then visit http://localhost:8080/
```

## Deploy

It's a static site — drop the folder on any static host (Netlify, Vercel,
Cloudflare Pages, GitHub Pages, etc.). No server runtime required.

- Entry point is `index.html`.
- The only external dependency is **Google Fonts** (`fonts.googleapis.com` /
  `fonts.gstatic.com`) — the site needs internet for correct type. Everything
  else is self-contained (all artwork is inline SVG; no local image files).
- For clean URLs (`/pricing`, `/roadmap`) configure your host's rewrites, or
  rename the files and update the four nav/logo/footer links.

## Design system

Defined once as CSS custom properties in each file's `:root`:

```
--paper      #F6F4EF   page background
--paper-dim  #EEEBE3   alt panels / muted tiles
--ink        #17171A   text, dark sections, primary buttons
--stone      #83796C   secondary text, labels
--line       #DBD6CB   1px borders / dividers
--cobalt     #3049FF   the single accent (italic <em>, focus rings)
--cobalt-dim #EBEEFF   waitlist / CTA background
--radius     2px       global corner radius
```

**Type:** Fraunces (display/headings; italic = the cobalt accent phrase),
IBM Plex Sans (body), IBM Plex Mono (eyebrows, labels, buttons).

**Brand:** *Basalio* evokes **basalt — bedrock, the solid base your work stands
on.** Hero line: "Give your work *solid ground* to stand on." Modules are framed
as numbered **"Exhibits."** Restraint: one signature interactive moment (the
hero), quiet elsewhere; all demo motion is user-triggered and respects
`prefers-reduced-motion`.

## Notes

- **Swap in real work:** the demo tiles use inline-SVG placeholders that read as
  design work. Replacing them with real portfolio images is the highest-impact
  upgrade; keep them embedded if the page must stay self-contained.
- **Copy stays pre-launch** until the paid product ships — keep CTAs as
  "join the beta" / "request access."

## License

Released under the **MIT License** — see [LICENSE](LICENSE). You're free to use,
modify, and distribute the code, provided the copyright notice is retained.

Two things the MIT license does **not** cover:

- The **"Basalio" and "ChrowmDesigns" names and logos** — trademarks, all rights
  reserved. MIT lets people reuse the code, not the brand identity.
- The **Google Fonts** typefaces (Fraunces, IBM Plex Sans, IBM Plex Mono), which
  are provided under the SIL Open Font License 1.1 and loaded from the CDN.
