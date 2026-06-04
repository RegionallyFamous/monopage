# Monopage Developer Guide

Monopage is a WordPress plugin, block theme, and Codex deployment skill for building one-page marketing sites with the Site Editor.

It does not fork WordPress. It narrows the experience around a single editable homepage template, keeps the real WordPress admin available through an escape hatch, and uses WP-CLI for deployment.

## Architecture

- `plugins/monopage/monopage.php` owns setup, Focus Mode, admin redirects, the Monopage settings page, and WP-CLI commands.
- `themes/monopage-canvas/` owns the default block theme, `theme.json`, `templates/front-page.html`, and bundled section patterns.
- `skills/monopage-deploy/` teaches Codex agents how to package, deploy, customize, and validate Monopage sites.
- `playground/blueprint.json` installs the plugin and theme into WordPress Playground and refreshes the default Canvas template for the demo.
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
npm run local:ready
```

Refresh the saved local Site Editor template from the current bundled Canvas template:

```bash
npm run local:refresh-template
```

`local:ready` starts wp-env, refreshes the saved front-page template from the bundled Canvas template, validates Monopage, smokes the rendered homepage, smokes authenticated Focus Mode admin behavior, prints status, and returns the home/admin/Site Editor URLs. Use `npm run local:ready -- --preserve-template` when you intentionally want to preserve saved Site Editor edits during setup.

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
- deploy dry-run safety validation
- one-page link validation for templates and patterns
- Playground Blueprint and seamless URL validation
- Codex skill contract validation
- version metadata validation
- package dry-run

Additional checks:

```bash
npm run doctor
npm run local:ready -- --dry-run
npm run local:validate
npm run local:admin-smoke
npm run local:smoke
npm run check:deploy
npm run check:playground
npm run check:skill
npm run package:verify
npm run plugin:check
npm run plugin:check:runtime
```

Full release gate:

```bash
npm run release:check
```

`local:smoke` checks the rendered homepage, required starter copy, same-page body links, section anchors, and served Canvas image assets. Run it after `local:setup` or `local:refresh-template`.

`local:admin-smoke` logs in to the local wp-env admin and checks Focus Mode admin redirect behavior, generic Site Editor canvas redirection, Media Library reachability, Monopage controls, routing Home page editor redirection, and the full-dashboard escape. It restores Focus Mode and user escape state after the check.

`check:playground` checks that the public Blueprint installs the expected GitHub theme/plugin directories, refreshes the Canvas front-page template, lands in the Site Editor canvas, and that `playground:url` keeps the outer Playground toolbar hidden with seamless mode.

`check:deploy` checks that the dry-run deploy plan packages and verifies ZIPs before WP-CLI changes, backs up before installing, installs the theme before the plugin, validates before status, and keeps `--force-home`, `--force-template`, and `--check-http` opt-in by default.

`check:skill` checks that `skills/monopage-deploy/SKILL.md` and `agents/openai.yaml` preserve the required Monopage agent contract: one-page navigation, Site Editor `front-page` editing, block-first design, generated-image handling, deploy backups, force flag safety, and validation commands.

`package:verify` checks the current version's built plugin and theme ZIPs for required files, correct version metadata, expected top-level folders, and forbidden bundled paths.

`release:check` runs repository checks, doctor, local readiness, Plugin Check, package builds, package content verification, deploy plan safety validation, deploy dry-run, and the Playground URL generator. The local readiness step runs the same `local:ready` workflow used during day-to-day development. Use `npm run release:check:dry-run` to inspect the sequence. Use `node scripts/release-check.mjs --skip-local --skip-plugin-check` only when Docker/wp-env is unavailable, then run those skipped gates on a real WordPress runtime before release.

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
npm run package:verify
```

The package script runs version metadata checks before building.

## Deployment

Dry run:

```bash
npm run check:deploy
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

Real deploy:

```bash
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

The deploy helper packages and verifies the plugin/theme ZIP contents before installing them. Use `--skip-package` only when the ZIPs are already built, and `--skip-package-verify` only when intentionally deploying custom ZIPs outside the current version contract.

Deployment sequence:

1. Package the plugin and Canvas theme ZIPs unless skipped.
2. Verify built ZIP contents unless skipped.
3. Confirm WordPress is installed.
4. Export a database backup.
5. Install and activate the Canvas theme ZIP.
6. Install and activate the Monopage plugin ZIP.
7. Run `wp monopage setup`.
8. Run `wp monopage validate --require-focus`.
9. Report `wp monopage status --format=json`.

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
- run `npm run local:admin-smoke` after Focus Mode, admin redirect, or Site Editor entrypoint changes when wp-env is available
- run `wp monopage validate --require-focus` after setup when a WordPress runtime is available
- run Plugin Check before release when a WordPress runtime is available
- back up before deployment
- avoid `--force-home` and `--force-template` unless explicitly approved

Install the skill locally:

```bash
npm run skill:install
```
