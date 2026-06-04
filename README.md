# Monopage

![Riso-style illustration of a continuous Monopage campaign page with modular blocks and anchor navigation](docs/assets/monopage-riso-hero.jpg)

Monopage turns WordPress into a focused one-page site studio. It keeps WordPress core intact, activates a block theme, creates a routing Home page, and sends authoring straight to the editable Site Editor `front-page` template.

This README is the practical command reference. The deeper product model, architecture, theme rules, and contributor guidance live in the [developer wiki](docs/wiki/Developer-Guide.md).

## What Ships

- `plugins/monopage/`: setup flow, Focus Mode, admin redirects, hidden public admin bar, and WP-CLI commands without a dashboard settings menu.
- `themes/monopage-canvas/`: The block-first one-page Canvas theme, default `front-page.html`, and insertable section patterns.
- `scripts/`: Validation, packaging, local setup, Plugin Check, Playground URL, and deploy helpers.
- `skills/monopage-deploy/`: The Codex skill for packaging, deploying, validating, and customizing Monopage sites.
- `docs/wiki/`: Repo-backed source for the GitHub wiki.

## Playground

Open Monopage in WordPress Playground without the outer Playground toolbar:

https://playground.wordpress.net/?mode=seamless&blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2FRegionallyFamous%2Fmonopage%2Fmain%2Fplayground%2Fblueprint.json

Generate the current Playground URL:

```bash
npm run playground:url
```

The Blueprint installs Monopage Canvas, activates the Monopage plugin, logs in as `admin`, runs setup with a template refresh, and lands in the Site Editor canvas.

## Install And Local Development

```bash
npm install
npm run local:ready
```

Local WordPress runs at:

```text
http://localhost:8888
```

`local:ready` starts wp-env, refreshes the saved front-page template from the bundled Canvas template, validates Monopage, smokes the rendered homepage, smokes authenticated Focus Mode admin behavior, prints status, and returns the home/admin/Site Editor URLs.

Use these when you need a narrower local workflow:

```bash
npm run local:start
npm run local:refresh-template
npm run local:ready -- --preserve-template
npm run local:smoke
npm run local:admin-smoke
npm run local:validate
```

For visual or responsive review:

```bash
npm run local:review
npm run local:ready -- --capture
npm run local:capture
npm run local:responsive
```

Useful local dry runs:

```bash
npm run local:review:dry-run
npm run local:ready -- --dry-run
npm run local:capture:dry-run
npm run local:responsive:dry-run
```

Screenshots are written to `build/screenshots/`. Set `MONOPAGE_CHROME=/path/to/browser` if Chrome or Chromium is installed somewhere unusual.

## Checks

Run the full repository test suite:

```bash
npm test
```

Run the Docker-free local gate before a normal push:

```bash
npm run preflight
```

Run the release gate before publishing or release-minded deploys:

```bash
npm run release:check
```

Useful focused checks:

```bash
npm run doctor
npm run status
npm run preflight:dry-run
npm run check:changed
npm run check:changed:run
npm run check:js
npm run check:canvas
npm run check:deploy
npm run check:docs
npm run check:hygiene
npm run check:links
npm run check:scripts
npm run check:playground
npm run check:skill
npm run check:versions
npm run package:verify
```

`check:changed` recommends checks for the current diff. `check:docs` verifies documented `npm run ...`, `npm test`, and `node scripts/*.mjs` commands in the README, wiki, and Codex skill.

`preflight` runs `status` and the Docker-free `ci` gate. GitHub Actions runs `ci` on pushes and pull requests, builds the plugin/theme ZIPs, verifies package contents, and stores the ZIPs as workflow artifacts.

## Release And Packaging

Update release metadata:

```bash
npm run version:set -- <next-version> --changelog="Short release note"
```

Build and verify the plugin/theme ZIPs:

```bash
npm run package
npm run package:verify
```

Preview or adapt the release gate:

```bash
npm run release:check:dry-run
node scripts/release-check.mjs --skip-local --skip-plugin-check
```

Use the skipped release-check path only when Docker or wp-env is unavailable, then run the local/runtime checks later on a WordPress runtime.

## Plugin Check

With Docker running:

```bash
npm run local:start
npm run plugin:check
npm run plugin:check:runtime
npm run plugin:check:dry-run
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
wp monopage validate --require-focus --format=json
wp monopage validate --require-focus --check-http
```

Use `--check-http` only when `home_url()` is reachable from the WP-CLI runtime. For local `wp-env`, use:

```bash
npm run local:validate
```

## Deploy

Preview the deployment command sequence:

```bash
npm run check:deploy
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

Deploy to a real local WP-CLI target:

```bash
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

The deploy helper packages and verifies the plugin/theme ZIP contents before installing them. Use `--skip-package` only when you already built the ZIPs, and `--skip-package-verify` only when intentionally deploying custom ZIPs outside the current version contract.

Use `--force-home` only when Monopage should replace an existing static front page assignment.

Use `--force-template` only when Monopage should replace saved Site Editor edits to the `front-page` template with the current Canvas default.

Add `--check-http` only when the target environment can serve the homepage back to WP-CLI during validation.

## Install The Codex Skill

```bash
npm run skill:install
npm run skill:install:dry-run
```

The skill bakes in the Monopage rules for same-page navigation, block-first theme work, WP-CLI deploys, backups, Plugin Check, and Canvas validation.

## Wiki

Start with [docs/wiki/Home.md](docs/wiki/Home.md) and [docs/wiki/Developer-Guide.md](docs/wiki/Developer-Guide.md) for architecture, design principles, Canvas patterns, contribution workflow, and long-form developer guidance.
