# Monopage Canvas Riso Visual Assets

Generated on 2026-06-04 with the built-in ImageGen tool for the default Monopage Canvas theme.

Source ImageGen originals remain in:

- `/Users/nick/.codex/generated_images/019e9273-c25d-7b33-b167-f8b589c0704f/`
- `/Users/nick/.codex/generated_images/019e9385-3853-7c11-99b2-54dea576eb87/`
- `/Users/nick/.codex/generated_images/019e935b-4b13-7ad1-8dba-e3d482220d90/ig_0b06a95bbe91292a016a21a0c5d3988190a1a6a60761e5a5a9.png`
- `/Users/nick/.codex/generated_images/019e935b-4b13-7ad1-8dba-e3d482220d90/ig_0b06a95bbe91292a016a21a10565188190bddda8b60146b175.png`
- `/Users/nick/.codex/generated_images/019e935b-4b13-7ad1-8dba-e3d482220d90/ig_0b06a95bbe91292a016a21a162ecb881908caaf2e45fba3396.png`

## Final Assets

- `themes/monopage-canvas/assets/monopage-riso-hero.jpg`
  - Current placement: wired into the `.monopage-hero` background.
  - Composition note: the left side has enough quiet space for overlaid hero copy; the right side carries the retrofuture ad-studio detail.
- `themes/monopage-canvas/assets/monopage-riso-flow.jpg`
  - Current placement: wired into the Showcase visual section for same-page navigation and content-flow energy.
  - Composition note: vertical page runway with dots and arrows; especially good beside a feature list or as a README visual.
- `themes/monopage-canvas/assets/monopage-riso-launch.jpg`
  - Current placement: wired into the `.monopage-final-cta` background.
  - Composition note: wide creative proofing table with launch-console energy and strong premium polish.
- `themes/monopage-canvas/assets/images/monopage-riso-future-agency-hero.jpg`
  - Current placement: wired into the campaign-wall "future pitch room" tile.
  - Composition note: Riso agency room with anonymous silhouettes, big boards, and strong editorial energy.
- `themes/monopage-canvas/assets/images/monopage-riso-print-lab.jpg`
  - Current placement: wired into the campaign-wall "print lab" tile.
  - Composition note: vertical print factory wall; useful when the page needs tactile production energy.
- `themes/monopage-canvas/assets/images/monopage-riso-skyline-banner.jpg`
  - Current placement: wired into the campaign-wall "skyline takeover" tile.
  - Composition note: wide city/ad takeover image; use for panoramic campaign moments.
- `themes/monopage-canvas/assets/images/monopage-riso-orbital-lounge.jpg`
  - Current placement: bundled as an alternate inspiration image.
  - Composition note: square retrofuture strategy lounge with anonymous silhouettes and bright Riso color.

## CSS Placement

Suggested minimal theme wiring:

```css
.monopage-hero {
	background-image:
		linear-gradient(90deg, rgba(5, 9, 16, 0.82) 0%, rgba(5, 9, 16, 0.5) 38%, rgba(5, 9, 16, 0.08) 100%),
		url("assets/monopage-riso-hero.jpg");
}
```

The current theme uses CSS only for image-backed section backgrounds, overlays, responsive cropping, and sticky/header behavior. Keep typography, spacing, colors, and block defaults in `theme.json` or block settings wherever WordPress can express them cleanly.

Campaign-wall tiles use the same CSS approach because WordPress block templates cannot reliably express theme-relative background images without a small class hook.

## Prompt Set

Hero prompt:

```text
Use case: ads-marketing
Asset type: wide website hero/background image for the default Monopage Canvas WordPress one-page marketing theme
Primary request: Create a cool, fun, premium, weirdly polished hero image with cutting-edge ad agency, retrofuture Madison Avenue, "Mad Men in the year 3000" energy and Riso-inspired print texture.
Scene/backdrop: a futuristic creative studio campaign room with a chrome strategy table, abstract browser-canvas architecture, floating modular page sections, same-page anchor rails, campaign boards, art-direction grids, luminous proof sheets, and sleek launch artifacts. It should imply one-page block editing, same-page marketing flow, premium creative direction, and launch-day momentum.
Composition: wide cinematic 16:9 hero background. Left third should have quieter negative space for overlaid website headline text; right two-thirds can be dense, dramatic, dimensional, and richly detailed. Strong depth, crisp focal objects, premium editorial finish, no dark blurry generic tech look.
Style: Riso-inspired ink layers, halftone texture, offset print imperfections, electric teal, acid lime, coral red, black ink, warm cream paper grain, chrome/silver future accents, glossy ad-agency polish. Cool and fun, not corporate stock, not childish.
Avoid: no readable embedded text, no letters, no numbers, no words, no logos, no official WordPress marks, no brand marks, no watermarks, no UI labels, no fake gibberish typography, no people as the main subject.
```

Flow prompt:

```text
Use case: stylized-concept
Asset type: supporting editorial image for a one-page marketing theme section about same-page navigation and content flow
Primary request: Create a Riso-inspired retrofuture advertising-system illustration that shows a single long web page as a beautiful vertical campaign runway, with stacked modular content blocks connected by anchor dots and motion arrows.
Scene/backdrop: abstract premium design studio wall, page sections floating like printed panels, chrome rails, orbital navigation beads, registration marks, color swatches, tiny 3D block components, editorial layout energy, smooth same-page movement.
Composition: portrait-to-square friendly image with strong central vertical flow. Detailed but clean enough for a website section image or README visual. The central page path should read as navigation/content flow without any readable text.
Style: "Mad Men in the year 3000", cool cutting-edge ad agency, Riso print texture, halftone dots, slightly imperfect ink registration, electric teal, coral, acid lime, black ink, cream paper, silver/chrome details, premium and playful.
Avoid: no readable embedded text, no letters, no numbers, no words, no logos, no official WordPress marks, no brand marks, no watermarks, no UI labels, no fake typography, no humans as main subject.
```

Launch prompt:

```text
Use case: ads-marketing
Asset type: supporting wide editorial image for a one-page marketing theme section about launch, proof, and conversion
Primary request: Create a refreshed cool retrofuture ad-agency campaign image showing an abstract launch console and creative proofing table, built from modular website blocks, call-to-action objects, analytics-like shapes, and same-page section markers.
Scene/backdrop: premium 3000s Madison Avenue studio, chrome desk, Riso proof sheets, luminous block components, orbital navigation dots, campaign signal beams, futuristic print rollers, ad artifacts, and conversion/proof energy. It should feel inspiring for a WordPress one-page marketing site, but not like a literal app screenshot.
Composition: landscape 16:10 or 4:3, balanced for use as an inline section visual or README image. High impact, crisp, playful, polished, with clear foreground, midground, and a small amount of calm space.
Style: Riso print texture plus glossy editorial product photography, electric teal, coral, acid lime, black ink, cream paper, chrome/silver, halftone, overprint, subtle grain, cutting-edge agency premium finish.
Avoid: no readable embedded text, no letters, no numbers, no words, no logos, no official WordPress marks, no brand marks, no watermarks, no UI labels, no fake typography, no human faces, no generic stock image.
```

## Copy And Vibe Notes

- Lead with confident campaign language: "Build one page that behaves like a launch room", "Every section has a job", "Move visitors down the same page, not away from it".
- Keep the voice stylish and direct: shorter lines, vivid nouns, fewer generic SaaS phrases.
- Treat navigation copy as anchors only: `#top`, `#promise`, `#services`, `#showcase`, `#results`, `#pricing`, `#questions`, `#start`; avoid page-like labels that imply leaving the homepage.
- Pair the Riso assets with strong typography, black ink, cream paper, teal/coral/lime accents, and a few chrome-like highlights.
