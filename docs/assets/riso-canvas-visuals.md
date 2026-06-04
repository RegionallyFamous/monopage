# Monopage Canvas Riso Visual Assets

Generated on 2026-06-04 with the built-in ImageGen tool for the default Monopage Canvas theme.

## Final Assets

- `themes/monopage-canvas/assets/monopage-riso-hero.jpg`
  - Current placement: wired into the `.monopage-hero` background.
  - Composition note: the left side has enough quiet space for overlaid hero copy; the right side carries the retrofuture ad-studio detail.
- `themes/monopage-canvas/assets/monopage-riso-flow.jpg`
  - Current placement: wired into the Showcase visual section for same-page navigation and content-flow energy.
  - Composition note: vertical page runway with dots and arrows; especially good beside a feature list or as a README visual.
- `themes/monopage-canvas/assets/monopage-riso-launch.jpg`
  - Placement note: available for future conversion, launch, proof, or README sections.
  - Composition note: wide creative proofing table with launch-console energy and strong premium polish.

## CSS Placement

Suggested minimal theme wiring:

```css
.monopage-hero {
	background-image:
		linear-gradient(90deg, rgba(5, 9, 16, 0.82) 0%, rgba(5, 9, 16, 0.5) 38%, rgba(5, 9, 16, 0.08) 100%),
		url("assets/monopage-riso-hero.jpg");
}
```

If the design keeps moving toward theme.json and block settings, prefer using these as ordinary block image/background assets in patterns or templates, then keep CSS limited to the hero overlay and responsive cropping.

## Prompt Set

Hero prompt:

```text
Use case: ads-marketing
Asset type: wide website hero/background image for the default Monopage Canvas WordPress one-page marketing theme
Primary request: Create a cool, fun, premium, weirdly polished hero image with cutting-edge ad agency, retrofuture Madison Avenue, "Mad Men in the year 3000" energy and Riso-inspired print texture.
Scene/backdrop: a futuristic creative studio campaign table and abstract browser-canvas environment, with floating modular page blocks, anchor-navigation rails, campaign boards, art-direction grids, and luminous print proofs. It should imply one-page block editing, same-page section movement, launch campaigns, creative systems, and premium editorial polish.
Composition: wide cinematic 16:9 hero background. Left third should have quieter negative space for overlaid headline text; right two-thirds can be dense, dramatic, and richly detailed. Strong depth, crisp focal objects, elegant shadows, premium design-magazine finish.
Style: Riso-inspired ink layers, halftone texture, offset print imperfections, electric teal, acid lime, coral red, black ink, warm paper grain, chrome/silver future accents. Cool and fun, not corporate stock, not childish.
Avoid: no readable embedded text, no letters, no words, no official WordPress logo, no brand marks, no watermarks, no UI labels, no fake gibberish typography, no people as the main subject, no dark blurry generic tech background.
```

Flow prompt:

```text
Use case: stylized-concept
Asset type: supporting editorial image for a one-page marketing theme section about same-page navigation and content flow
Primary request: Create a Riso-inspired retrofuture advertising-system illustration that shows a single long web page as a beautiful vertical campaign runway, with stacked modular content blocks connected by anchor dots and motion arrows.
Scene/backdrop: abstract premium design studio wall, page sections floating like printed panels, chrome rails, neon registration marks, color swatches, tiny 3D block components, editorial layout energy.
Composition: portrait-to-square friendly image, strong central vertical flow, plenty of detail but clean enough for a website section image or README visual. No headline space required.
Style: "Mad Men in the year 3000", cool ad agency, Riso print texture, halftone dots, slightly imperfect ink registration, teal, coral, acid lime, black ink, cream paper, silver/chrome details.
Avoid: no readable embedded text, no letters, no words, no official WordPress logo, no brand marks, no watermarks, no UI labels, no fake typography, no humans as main subject.
```

Launch prompt:

```text
Use case: ads-marketing
Asset type: supporting wide editorial image for a one-page marketing theme section about launch, proof, and conversion
Primary request: Create a cool retrofuture ad-agency campaign image showing an abstract launch console and creative proofing table, built from modular website blocks, call-to-action objects, analytics-like forms, and same-page section markers.
Scene/backdrop: premium 3000s Madison Avenue studio, chrome desk, Riso proof sheets, luminous block components, orbital navigation dots, campaign signal beams, futuristic print rollers and ad artifacts. It should feel inspiring for a WordPress one-page marketing site, but not like a literal app screenshot.
Composition: landscape 4:3 or 16:10, balanced for use as an inline section visual or README image. High impact, crisp, playful, polished, with clear foreground/midground.
Style: Riso print texture plus glossy editorial product photography, teal, coral, acid lime, black ink, cream paper, chrome/silver, halftone, overprint, subtle grain.
Avoid: no readable embedded text, no letters, no words, no official WordPress logo, no brand marks, no watermarks, no UI labels, no fake typography, no human faces, no generic stock image.
```

## Copy And Vibe Notes

- Lead with confident campaign language: "Build one page that behaves like a launch room", "Every section has a job", "Move visitors down the same page, not away from it".
- Keep the voice stylish and direct: shorter lines, vivid nouns, fewer generic SaaS phrases.
- Treat navigation copy as anchors only: `#top`, `#story`, `#proof`, `#offer`, `#contact`; avoid page-like labels that imply leaving the homepage.
- Pair the Riso assets with strong typography, black ink, cream paper, teal/coral/lime accents, and a few chrome-like highlights.
