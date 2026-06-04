# Monopage Developer Guide

Monopage is a WordPress plugin, block theme, and Codex deployment skill for building one-page marketing sites with the Site Editor.

It does not fork WordPress. It narrows the experience around a single editable homepage template and uses WP-CLI for setup, validation, deployment, and Focus Mode changes.

## Documentation Model

The README is the command-first technical reference for installing, developing, checking, releasing, deploying, using Plugin Check, opening Playground, and installing the Codex skill.

The wiki carries the broader material: product intent, one-page rules, architecture, theme principles, Canvas patterns, contribution workflow, and developer background. When practical commands need to appear in both places, keep the README concise and put the reasoning and edge cases here.

## Architecture

- `plugins/monopage/monopage.php` owns setup, Focus Mode, admin redirects, the hidden public admin bar, and WP-CLI commands. It intentionally does not add a Monopage dashboard menu or settings page.
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

## Product Principles

<p>
  <img src="../assets/monopage-riso-editor-focus.jpg" alt="Riso-style focused editor canvas with modular blocks and a top toolbar" width="49%">
  <img src="../assets/monopage-riso-anchor-navigation.jpg" alt="Riso-style one-page layout with anchor navigation moving between sections" width="49%">
</p>

Monopage is meant to feel like one sharp campaign page, not a trimmed-down multi-page site. A visitor should move through sections on the same page; an editor should land on the actual Site Editor canvas; and a deploy should keep WordPress recognizable, reversible, and inspectable.

Working principles:

- One page means one page. Header navigation, footer navigation, and starter CTAs move to anchors on the same page.
- The Site Editor `front-page` template is the visible homepage. The WordPress `Home` page is only the routing page.
- Focus Mode simplifies the authoring path, but WordPress capabilities remain the security boundary.
- Use core blocks first: Group, Columns, Navigation, Buttons, Details, Table, Quote, Separator, Spacer, Site Title, Heading, Paragraph, and List.
- Prefer `theme.json` and block settings for global typography, colors, spacing, button defaults, Navigation styling, and Site Title styling.
- Use bundled Monopage Canvas patterns when adding common sections so new content inherits the same one-page structure.
- Use custom CSS only where it earns its keep: hero imagery, sticky header behavior, mobile safety, scroll margins, pseudo-elements, and editorial treatments core blocks cannot express cleanly.

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

- `Offer Lab` (`monopage-canvas/offer-lab`): three-card offer packaging.
- `Proof Strip` (`monopage-canvas/proof-strip`): dark results/metrics band.
- `Pricing Deck` (`monopage-canvas/pricing-deck`): three-plan pricing section.
- `Question Stack` (`monopage-canvas/question-stack`): compact FAQ section.
- `Final Push` (`monopage-canvas/final-push`): closing call-to-action band.

Pattern rules:

- Register patterns under the `monopage-canvas` category.
- Use core blocks and existing Canvas classes before adding CSS.
- Keep CTAs on-page with `#anchor` links.
- Give new full-section patterns a stable, unique anchor.
- Run `npm run check:links` and `npm run check:canvas` after pattern changes.

Patterns should be useful campaign sections, not decorative filler. They should give editors a complete section they can insert, retitle, and tune without leaving the one-page model.

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

Keep the default visual language cool and campaign-grade: Riso texture, sharp hierarchy, ink, white, electric blue, teal, coral, lime, and a little "Mad Men in the year 3000" energy.

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

For visual or layout changes, capture the rendered homepage after the template refresh:

```bash
npm run local:review
npm run local:ready -- --capture
npm run local:capture
npm run local:responsive
```

`local:review` refreshes the local template, runs homepage and admin smoke checks, captures desktop and mobile screenshots, runs the responsive smoke check, prints the Playground URL, and leaves screenshots in `build/screenshots/`.

`local:capture` is the narrower screenshot-only command. It uses a local Chrome or Chromium install. Set `MONOPAGE_CHROME=/path/to/browser` when the browser is installed outside the usual locations.

Local WordPress:

```text
http://localhost:8888
```

If Docker is not running, `wp-env` and Plugin Check cannot run. `npm run doctor` reports this.

## Contribution Workflow

Use the current diff to choose checks instead of running the heaviest workflow every time:

```bash
npm run status
npm run check:changed
npm run check:changed:run
```

For documentation-only edits, run:

```bash
npm run check:docs
```

For Canvas template, pattern, or theme work, keep the one-page link and block-first rules in view, then run the matching checks:

```bash
npm run check:links
npm run check:canvas
npm run local:review
```

For release-minded changes, do not rely on the fast CI gate alone. Use the release gate when Docker, wp-env, and a local WordPress runtime are available:

```bash
npm run release:check
```

If Docker or wp-env is unavailable, document what was skipped and run the skipped local readiness and Plugin Check steps later on a WordPress runtime.

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
- documented command validation
- repository hygiene validation
- one-page link validation for templates and patterns
- package script reference validation
- Playground Blueprint and seamless URL validation
- Codex skill contract validation
- version metadata validation
- package dry-run

Fast CI gate:

```bash
npm run preflight
```

`preflight` runs the project dashboard and then the Docker-free `ci` gate. `ci` runs `npm test`, builds the plugin and theme ZIPs, and verifies their package contents. GitHub Actions runs `ci` on pushes and pull requests, then uploads the generated ZIPs as workflow artifacts.

Additional checks:

```bash
npm run doctor
npm run status
npm run preflight:dry-run
npm run check:changed
npm run check:js
npm run check:canvas
npm run check:docs
npm run check:hygiene
npm run check:links
npm run check:scripts
npm run local:review:dry-run
npm run local:ready -- --dry-run
npm run local:capture:dry-run
npm run local:responsive:dry-run
npm run local:validate
npm run local:admin-smoke
npm run local:smoke
npm run check:deploy
npm run check:playground
npm run check:skill
npm run check:versions
npm run package:verify
npm run plugin:check
npm run plugin:check:runtime
```

Full release gate:

```bash
npm run release:check
```

`local:smoke` checks the rendered homepage, required starter copy, same-page body links, section anchors, and served Canvas image assets. Run it after `local:setup` or `local:refresh-template`.

`status` prints a quick project dashboard: current version, branch, latest commit, changed files, focused check recommendations, package artifact status and freshness, Codex skill install state, local URLs, and the Playground URL. It is read-only and does not start wp-env.

`preflight` is the normal fast local gate before a commit or push. It runs `status`, then `ci`, without requiring Docker/wp-env. Use `release:check` before publishing or release-minded changes.

`check:changed` inspects changed files and recommends the focused checks that match them, such as `check:links` for template edits, `local:review` for visual Canvas edits, or Plugin Check for plugin PHP edits. Actual diffs skip visual and runtime checks for version-only plugin/theme metadata changes; `--files` previews stay conservative. Use `npm run check:changed:run` to execute the recommendations.

`local:review` is the preferred shortcut after visual Canvas changes. It runs local readiness with screenshots, checks desktop/tablet/mobile responsive safety, and then prints the Playground URL so local review and public-demo review stay paired.

`local:capture` saves desktop and mobile homepage screenshots after local setup so hero, header, first-viewport, and mobile overflow changes can be reviewed without hand-building browser commands.

`local:responsive` opens the local homepage in Chrome at desktop, tablet, and mobile sizes. It fails on horizontal page overflow, clipped hero buttons, hidden mobile navigation, or cropped logo-strip pills.

`check:js` discovers JavaScript files in `plugins/`, `themes/`, and `scripts/`, then runs `node --check` on each file. Add new helper scripts normally; the syntax gate picks them up without editing `package.json`.

`local:admin-smoke` logs in to the local wp-env admin and checks Focus Mode admin redirect behavior, generic Site Editor canvas redirection, direct Media Library redirection, legacy Monopage admin URL removal, and routing Home page editor redirection. It restores the global Focus Mode option after the check.

`check:playground` checks that the public Blueprint installs the expected GitHub theme/plugin directories, refreshes the Canvas front-page template, lands in the Site Editor canvas, and that `playground:url` keeps the outer Playground toolbar hidden with seamless mode.

`check:deploy` checks that the dry-run deploy plan packages and verifies ZIPs before WP-CLI changes, backs up before installing, installs the theme before the plugin, validates before status, and keeps `--force-home`, `--force-template`, and `--check-http` opt-in by default.

`check:docs` checks documented `npm run ...`, `npm test`, and `node scripts/*.mjs` commands in the README, developer wiki, and Codex skill so docs stay aligned with `package.json` and helper scripts.

`check:hygiene` checks `.gitignore`, `.gitattributes`, tracked files, and `export-ignore` behavior so package ZIPs, SQL backups, env files, debug logs, local wp-env data, build output, and dependency folders stay out of tracked source and generated source archives.

`check:scripts` checks package script references so chained `npm run ...`, `npm test`, and `node scripts/*.mjs` commands stay aligned with `package.json` and helper files.

`check:skill` checks that `skills/monopage-deploy/SKILL.md` and `agents/openai.yaml` preserve the required Monopage agent contract: one-page navigation, Site Editor `front-page` editing, block-first design, generated-image handling, deploy backups, force flag safety, and validation commands.

`package:verify` checks the current version's built plugin and theme ZIPs for required files, correct version metadata, expected top-level folders, and forbidden bundled paths such as env files, backups, archives, build output, dependency folders, and local metadata.

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
- run `npm run status` when orienting in the repo
- run `npm run preflight` before a normal commit or push
- run `npm run check:changed` when deciding which focused checks fit the current diff
- run `npm test` before packaging
- run `npm run release:check` before release-minded changes when Docker/wp-env are available
- run `npm run local:review` after visual Canvas changes when wp-env and a local browser are available
- run `npm run local:capture` after visual Canvas changes when a local browser is available
- run `npm run local:admin-smoke` after Focus Mode, admin redirect, or Site Editor entrypoint changes when wp-env is available
- run `wp monopage validate --require-focus` after setup when a WordPress runtime is available
- run Plugin Check before release when a WordPress runtime is available
- back up before deployment
- avoid `--force-home` and `--force-template` unless explicitly approved

Install the skill locally:

```bash
npm run skill:install
```
