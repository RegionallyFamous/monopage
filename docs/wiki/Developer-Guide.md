# Monopage Developer Guide

Monopage is a WordPress plugin, block theme, and Codex deployment skill for building one-page marketing sites with the Site Editor.

It does not fork WordPress. It narrows the experience around a single editable homepage template, keeps the real WordPress admin available through an escape hatch, and uses WP-CLI for deployment.

## Architecture

- `plugins/monopage/monopage.php` owns setup, Focus Mode, admin redirects, the Monopage settings page, and WP-CLI commands.
- `themes/monopage-canvas/` owns the default block theme, `theme.json`, `templates/front-page.html`, and bundled section patterns.
- `skills/monopage-deploy/` teaches Codex agents how to package, deploy, customize, and validate Monopage sites.
- `playground/blueprint.json` installs the plugin and theme into WordPress Playground.
- `scripts/` contains validation, packaging, Plugin Check, local setup, and deployment helpers.

## Homepage Model

Monopage uses two separate WordPress concepts:

- Routing page: a normal `Home` page assigned in Reading settings.
- Visible homepage: the Site Editor `front-page` template.

Do not edit the routing Home page as if it were the visible page. The visible homepage lives in the saved `wp_template` record for `front-page`, seeded from `themes/monopage-canvas/templates/front-page.html`.

Setup preserves an existing static front page unless `--force-home` is explicitly passed.

Template refresh preserves saved Site Editor edits unless `--force-template` is explicitly passed.

## One-Page Navigation

Default Monopage links must stay on the same page. This applies to the starter template and bundled Canvas patterns.

Use anchors such as:

```text
#top
#promise
#services
#showcase
#results
#pricing
#questions
#start
```

Rules:

- Header navigation should use the core Navigation block with custom `#anchor` links.
- Footer navigation and primary CTAs should also use on-page anchors.
- Do not add page URLs, archive URLs, post URLs, category URLs, `/`, or external URLs to the starter template unless the user explicitly asks for that destination.
- Every linked anchor must exist as a block anchor or HTML `id`.

Validation:

```bash
npm run check:links
```

## Canvas Patterns

Monopage Canvas ships insertable section patterns in `themes/monopage-canvas/patterns/`.

Current patterns:

- `monopage-canvas/offer-lab`
- `monopage-canvas/proof-strip`
- `monopage-canvas/pricing-deck`
- `monopage-canvas/question-stack`
- `monopage-canvas/final-push`

Pattern rules:

- Register patterns under the `monopage-canvas` category.
- Use core blocks and existing Canvas classes before adding CSS.
- Keep CTAs on-page with `#anchor` links.
- Give new full-section patterns a stable, unique anchor.
- Run `npm run check:links` and `npm run check:canvas` after pattern changes.

## Block-First Theme Work

Use WordPress-native styling first.

Prefer:

- `theme.json` for palette, font presets, button defaults, Navigation defaults, Site Title defaults, spacing scale, and layout sizes.
- Block attributes for alignment, text alignment, block gaps, widths, colors, and core block behavior.
- Core blocks for structure and content.
- Bundled patterns for repeated one-page sections.

Use custom CSS only when WordPress block settings cannot express the behavior cleanly:

- hero background images and overlays
- sticky header behavior
- mobile overflow safety
- scroll margins
- pseudo-elements
- bespoke editorial visuals
- image-backed section treatments

When changing Canvas CSS or assets:

```bash
npm run check:canvas
```

## Visual Direction

Monopage should feel like a cutting-edge ad agency building a one-page campaign.

Good defaults:

- Riso print texture
- editorial layouts
- campaign boards
- modular page blocks
- anchor rails and movement down the page
- ink, white, electric blue, teal, coral, lime
- retrofuture "Mad Men in the year 3000" polish

Avoid:

- generic SaaS gradients
- stock-looking tech backgrounds
- excessive purple
- beige/tan dominance
- embedded readable text in generated images
- fake UI labels
- official WordPress logos unless sourced and used accurately

## Local Development

```bash
npm install
npm run local:start
npm run local:setup
npm run local:validate
npm run local:status
```

Refresh the saved local Site Editor template from the current bundled Canvas template:

```bash
npm run local:refresh-template
```

Local WordPress:

```text
http://localhost:8888
```

If Docker is not running, `wp-env` and Plugin Check cannot run. `npm run doctor` reports this.

## Validation Gates

Run before packaging or deploying:

```bash
npm test
```

The test suite runs:

- PHP syntax checks
- JavaScript syntax checks
- JSON validation
- Canvas style and asset validation
- one-page link validation for templates and patterns
- version metadata validation
- package dry-run

Additional checks:

```bash
npm run doctor
npm run local:validate
npm run plugin:check
npm run plugin:check:runtime
```

Full release gate:

```bash
npm run release:check
```

`release:check` runs repository checks, doctor, a local template refresh plus runtime validation, Plugin Check, package builds, deploy dry-run, and the Playground URL generator. Use `npm run release:check:dry-run` to inspect the sequence. Use `node scripts/release-check.mjs --skip-local --skip-plugin-check` only when Docker/wp-env is unavailable, then run those skipped gates on a real WordPress runtime before release.

## Runtime Validation

Monopage includes a WP-CLI health check for deployed or local installs:

```bash
wp monopage validate --require-focus
wp monopage validate --require-focus --format=json
wp monopage validate --require-focus --check-http
```

Validation checks:

- static front-page mode
- routing Home page existence and publish state
- active block theme and Monopage Canvas state
- saved `front-page` template
- template links and anchor targets
- registered Monopage Canvas patterns
- bundled Canvas pattern links and anchor targets
- Focus Mode state
- generated home and Site Editor URLs
- optional homepage HTTP response

Use `--allow-custom-theme` only when the target intentionally uses another block theme.

Use `--check-http` only when `home_url()` is reachable from the WP-CLI runtime. In `wp-env`, the CLI container may not be able to reach the browser-facing `localhost:8888` URL, so prefer `npm run local:validate`.

## Plugin Check

Local `wp-env` path:

```bash
npm run local:start
npm run plugin:check
npm run plugin:check:runtime
```

Real WP-CLI target:

```bash
node scripts/plugin-check.mjs --path=/path/to/wordpress
```

The Plugin Check helper loads Plugin Check's CLI file automatically:

```text
--require=./wp-content/plugins/plugin-check/cli.php
```

Use `--no-require` only for an environment that already registers `wp plugin check`.

## Packaging And Versioning

Update release metadata:

```bash
npm run version:set -- <next-version> --changelog="Short release note"
```

Build ZIPs:

```bash
npm run package
```

The package script runs version metadata checks before building.

## Deployment

Dry run:

```bash
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

Real deploy:

```bash
npm run package
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

Deployment sequence:

1. Confirm WordPress is installed.
2. Export a database backup.
3. Install and activate the Canvas theme ZIP.
4. Install and activate the Monopage plugin ZIP.
5. Run `wp monopage setup`.
6. Run `wp monopage validate --require-focus`.
7. Report `wp monopage status --format=json`.

## Codex Skill Expectations

The `monopage-deploy` skill should guide agents to:

- keep links on-page
- use the Site Editor `front-page` template as the visible homepage
- prefer `theme.json` and block settings before custom CSS
- use bundled Canvas patterns for common section work
- use generated raster images when the theme or docs need real visual energy
- save project-bound images into the repo
- run `npm test` before packaging
- run `npm run release:check` before release-minded changes when Docker/wp-env are available
- run `wp monopage validate --require-focus` after setup when a WordPress runtime is available
- run Plugin Check before release when a WordPress runtime is available
- back up before deployment
- avoid `--force-home` and `--force-template` unless explicitly approved

Install the skill locally:

```bash
npm run skill:install
```
