# Monopage

![Riso-style illustration of a continuous Monopage campaign page with modular blocks and anchor navigation](docs/assets/monopage-riso-hero.jpg)

Monopage turns WordPress into a focused one-page site studio.

It keeps WordPress core intact, activates a block theme, creates a routing `Home` page, and sends authoring directly to the editable Site Editor `front-page` template. The visible product is simple: one page, one canvas, one calm editing surface.

## What It Does

- Builds around a single editable homepage template.
- Uses same-page anchor navigation instead of page-to-page menus.
- Installs a minimal block theme with a polished default one-page marketing site.
- Opens focused users directly into the Site Editor canvas.
- Hides the WordPress admin sidebar, public admin bar, Site Editor left navigation toggle, dashboard menus, and settings/control screens.
- Keeps Spotlight and Distraction Free modes off while keeping the top toolbar on.
- Exposes setup, validation, packaging, and deployment through WP-CLI and Codex workflows.

Monopage does not fork WordPress, replace capabilities, or pretend menu hiding is security. WordPress roles and capabilities still define access. Monopage narrows the interface so site authors do not need to know the rest of WordPress exists.

## What Ships

- `plugins/monopage/`: setup, Focus Mode, admin redirects, Site Editor defaults, WP-CLI commands, and no dashboard settings menu.
- `themes/monopage-canvas/`: the block-first Canvas theme, default `front-page.html`, Riso-style assets, and insertable one-page section patterns.
- `scripts/`: local setup, validation, Plugin Check, responsive smoke tests, packaging, deploy helpers, and release gates.
- `skills/monopage-deploy/`: the Codex skill for deploying, validating, and customizing Monopage sites.
- `playground/blueprint.json`: the WordPress Playground demo blueprint.
- `docs/wiki/`: repo-backed GitHub wiki source for longer product and developer notes.

## Playground

Open the current demo in WordPress Playground:

[Launch Monopage Playground](https://playground.wordpress.net/?mode=seamless&blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2FRegionallyFamous%2Fmonopage%2Fmain%2Fplayground%2Fblueprint.json)

Generate the same URL from the repo:

```bash
npm run playground:url
```

The Blueprint installs Monopage Canvas, activates the Monopage plugin, logs in as `admin`, refreshes the default Canvas template, and lands in the Site Editor canvas without the outer Playground toolbar.

## Local Quickstart

```bash
npm install
npm run local:ready
```

Local WordPress runs at:

```text
http://localhost:8888
```

`local:ready` starts `wp-env`, refreshes the saved front-page template from the bundled Canvas template, validates Monopage, smokes the rendered homepage, smokes Focus Mode admin behavior, prints status, and returns the home/admin/Site Editor URLs.

Useful local commands:

```bash
npm run local:start
npm run local:refresh-template
npm run local:ready -- --preserve-template
npm run local:smoke
npm run local:admin-smoke
npm run local:validate
```

Visual and responsive review:

```bash
npm run local:review
npm run local:capture
npm run local:responsive
```

Screenshots are written to `build/screenshots/`. If Chrome or Chromium is installed somewhere unusual, set `MONOPAGE_CHROME=/path/to/browser`.

## One-Page Rules

Monopage navigation is a scroll map, not a traditional site menu.

- Header and footer navigation should link to anchors like `#top`, `#promise`, `#services`, `#showcase`, `#results`, `#pricing`, `#questions`, and `#start`.
- Starter templates and bundled patterns should not include page URLs, post URLs, archive URLs, `href="/"`, or external links unless someone explicitly asks for that destination.
- The Site Title in the Canvas starter template should not link to the homepage.
- New sections should get stable anchors before they are added to navigation.
- The routing `Home` page is not the editable content surface. The editable homepage is the Site Editor `front-page` template.

If the backend Page editor looks blank, that is expected. Open the Site Editor `front-page` template instead.

## Focus Mode

Focus Mode is enabled by default. For users who can edit the site, it:

- redirects `/wp-admin/` to the Site Editor front-page canvas;
- redirects generic admin screens back to the Site Editor;
- redirects attempts to edit the routing `Home` page into the Site Editor template;
- hides the admin menu and public admin bar;
- removes the Site Editor left navigation/sidebar toggle;
- keeps the top toolbar enabled;
- keeps Spotlight, focus-style, and Distraction Free editor modes disabled.

There is intentionally no Monopage dashboard menu, settings page, control panel, or in-admin escape hatch. Operational controls live in WP-CLI and Codex so the authoring surface stays clean.

## WP-CLI

Monopage exposes setup, status, focus, and validation commands:

```bash
wp monopage status
wp monopage setup
wp monopage focus enable
wp monopage focus disable
wp monopage validate --require-focus
```

Setup preserves an existing static front page by default. Use force flags only when you mean it:

```bash
wp monopage setup --force-home
wp monopage setup --force-template
```

`--force-home` replaces the current static front page assignment. `--force-template` replaces saved Site Editor edits to the `front-page` template with the bundled Canvas default.

## Checks

Run the full repository suite:

```bash
npm test
```

Run the Docker-free gate before a normal push:

```bash
npm run preflight
```

Run the full release-minded gate:

```bash
npm run release:check
```

Focused checks:

```bash
npm run status
npm run doctor
npm run check:changed
npm run check:changed:run
npm run check:links
npm run check:canvas
npm run check:docs
npm run check:skill
npm run plugin:check
npm run package:verify
```

`check:changed` recommends the right checks for the current diff. `check:docs` verifies documented repo commands. `check:skill` verifies the Codex skill still contains the required Monopage operating rules.

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

The Plugin Check helper installs and activates Plugin Check, then loads its WP-CLI file automatically.

## Packaging

Update version metadata:

```bash
npm run version:set -- <next-version> --changelog="Short release note"
```

Build and verify plugin/theme ZIPs:

```bash
npm run package
npm run package:verify
```

Generated artifacts live in `build/` and are ignored by Git.

## Deploy

Preview the deployment plan:

```bash
npm run check:deploy
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

Deploy to a real WP-CLI target:

```bash
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

The deploy helper packages and verifies the plugin/theme ZIPs, checks that WordPress is installed, exports a database backup, installs the theme, installs the plugin, runs setup, validates Focus Mode, and reports status.

Use `--force-home`, `--force-template`, and `--check-http` only when the target situation calls for them. The default deploy path is conservative.

## Codex Skill

Install or refresh the local Codex skill:

```bash
npm run skill:install
npm run skill:install:dry-run
```

The `monopage-deploy` skill tells Codex how to keep Monopage work on-page, use the Site Editor `front-page` template as the source of truth, prefer `theme.json` and block settings, run the right checks, package safely, back up before deploys, and avoid reintroducing dashboard controls.

## Project Notes

The README is the technical command reference. Longer product rationale, architecture notes, Canvas pattern guidance, and contributor workflow live in:

- [Wiki Home](docs/wiki/Home.md)
- [Developer Guide](docs/wiki/Developer-Guide.md)

Monopage is currently version `0.2.45`.
