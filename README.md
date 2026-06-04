# Monopage

![Riso-style illustration of a continuous Monopage campaign page with modular blocks and anchor navigation](docs/assets/monopage-riso-hero.jpg)

Monopage turns WordPress into a focused one-page site studio. It keeps WordPress core intact, activates a block theme, creates a routing Home page, and sends the authoring experience straight to the editable Site Editor `front-page` template.

The vibe is simple: one page, all signal. A launch room for products, studios, services, events, experiments, and anything else that should move a visitor down the same page instead of sending them wandering.

## What It Includes

- `plugins/monopage/`: Focus Mode, setup flow, admin controls, and WP-CLI commands.
- `themes/monopage-canvas/`: A block-first one-page Canvas theme with a polished default `front-page.html` and insertable section patterns.
- `skills/monopage-deploy/`: A Codex skill for packaging, deploying, validating, and customizing Monopage sites.
- `scripts/`: Validation, packaging, local setup, Plugin Check, Playground URL, and deploy helpers.
- `docs/wiki/`: Developer documentation intended to mirror a GitHub wiki.

## Try It

Open Monopage in WordPress Playground without the outer Playground toolbar:

https://playground.wordpress.net/?mode=seamless&blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2FRegionallyFamous%2Fmonopage%2Fmain%2Fplayground%2Fblueprint.json

The Blueprint installs Monopage Canvas, activates the Monopage plugin, logs in as `admin`, runs setup, and lands in the Site Editor canvas.

## Quick Start

```bash
npm install
npm run local:start
npm run local:setup
npm run local:validate
npm run local:status
```

Local WordPress runs at:

```text
http://localhost:8888
```

## Development Checks

Run the full validation suite before packaging or deploying:

```bash
npm test
```

Useful focused checks:

```bash
npm run doctor
npm run check:canvas
npm run check:links
npm run check:versions
```

`check:canvas` confirms the Canvas stylesheet is wired for both the Site Editor and the public front end, that CSS asset references are packaged and reasonably sized, and that bundled pattern metadata is valid.

`check:links` confirms the default front-page template and bundled patterns only use on-page links and that every `#anchor` target exists.

Run the full release gate before publishing or pushing a release-minded change:

```bash
npm run release:check
```

Use `npm run release:check:dry-run` to preview the sequence. In environments without Docker or wp-env, use `node scripts/release-check.mjs --skip-local --skip-plugin-check` and run those checks later on a WordPress runtime.

## Plugin Check

With Docker running:

```bash
npm run local:start
npm run plugin:check
npm run plugin:check:runtime
```

Against another WP-CLI target:

```bash
node scripts/plugin-check.mjs --path=/path/to/wordpress
```

The Plugin Check helper installs/activates Plugin Check and loads its WP-CLI file automatically. Use `--no-require` only for an environment that already registers `wp plugin check`.

## Runtime Validation

Validate a configured Monopage install through WP-CLI:

```bash
wp monopage validate --require-focus
wp monopage validate --require-focus --check-http
```

`validate` checks the static front-page assignment, routing Home page, active block theme, saved `front-page` template, template anchor links, registered Canvas patterns, bundled pattern anchor links, Focus Mode, and generated home/editor URLs.

Use `--check-http` only when `home_url()` is reachable from the WP-CLI runtime. For local `wp-env`, use `npm run local:validate` without the HTTP check.

## Packaging

Update release metadata with one command:

```bash
npm run version:set -- <next-version> --changelog="Short release note"
```

Build the plugin and theme ZIPs:

```bash
npm run package
```

Artifacts are written into `build/`.

## Deploy

Preview the deployment command sequence:

```bash
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

Deploy to a real local WP-CLI target:

```bash
npm run package
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

Use `--force-home` only when Monopage should replace an existing static front page assignment.

Use `--force-template` only when Monopage should replace saved Site Editor edits to the `front-page` template with the current Canvas default.

Add `--check-http` to the deploy script when the target environment can serve the homepage back to WP-CLI during validation.

## Install The Codex Skill

```bash
npm run skill:install
```

Preview the install without changing your Codex skill folder:

```bash
npm run skill:install:dry-run
```

The skill bakes in the Monopage rules: same-page navigation, block-first theme work, WP-CLI deploys, backups before changes, Plugin Check, and Canvas validation.

## Design Principles

<p>
  <img src="docs/assets/monopage-riso-editor-focus.jpg" alt="Riso-style focused editor canvas with modular blocks and a top toolbar" width="49%">
  <img src="docs/assets/monopage-riso-anchor-navigation.jpg" alt="Riso-style one-page layout with anchor navigation moving between sections" width="49%">
</p>

- One page means one page. Header navigation, footer navigation, and starter CTAs move to anchors on the same page.
- The Site Editor `front-page` template is the visible homepage. The WordPress `Home` page is only the routing page.
- Use core blocks first: Group, Columns, Navigation, Buttons, Details, Table, Quote, Separator, Spacer, Site Title, Heading, Paragraph, and List.
- Prefer `theme.json` and block settings for global typography, colors, spacing, button defaults, Navigation styling, and Site Title styling.
- Use bundled Monopage Canvas patterns when adding common sections so new content inherits the same one-page structure.
- Use custom CSS only where it earns its keep: hero imagery, sticky header behavior, mobile safety, scroll margins, pseudo-elements, and editorial treatments core blocks cannot express cleanly.
- Keep the default visual language cool and campaign-grade: Riso texture, sharp hierarchy, ink, white, electric blue, teal, coral, lime, and a little "Mad Men in the year 3000" energy.

## Canvas Patterns

Monopage Canvas ships a focused pattern set under the `Monopage Canvas` pattern category:

- `Offer Lab`: three-card offer packaging.
- `Proof Strip`: dark results/metrics band.
- `Pricing Deck`: three-plan pricing section.
- `Question Stack`: compact FAQ section.
- `Final Push`: closing call-to-action band.

Each pattern is built from core blocks, uses project-local Canvas classes, and keeps calls to action on the same page.

## Developer Wiki

Start with [docs/wiki/Developer-Guide.md](docs/wiki/Developer-Guide.md).
