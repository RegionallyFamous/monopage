---
name: monopage-deploy
description: Deploy, package, inspect, customize, or troubleshoot Monopage sites from Codex using WP-CLI and the Monopage Canvas rules. Use when the user asks to deploy Monopage, install Monopage on a WordPress host, package the Monopage plugin/theme, run Monopage setup, validate a Monopage one-page site, customize the Monopage one-page template, or manage Monopage Focus Mode.
---

# Monopage Deploy

Use this skill to deploy, validate, and lightly customize Monopage sites safely through WP-CLI.

## Monopage Product Rules

- Monopage is one page. Navigation should move visitors up and down the same page, not to other WordPress pages, posts, archives, or external URLs.
- Primary menu items, footer navigation, and starter-template CTAs should use section anchors such as `#top`, `#promise`, `#services`, `#showcase`, `#results`, `#pricing`, `#questions`, or `#start`.
- The starter template should not include `href="/"`, page URLs, post URLs, category/archive links, or external links unless the user explicitly asks for that specific off-page destination.
- The site title in Monopage Canvas should be non-linking in the starter template, because the header is a one-page scroll map rather than a site-wide navigation bar.
- Use the core Navigation block for the header menu with custom `#anchor` links. Do not fake navigation with Button blocks plus custom CSS unless the user explicitly wants button-like nav.
- Add new sections by giving the target block a stable anchor and linking to that anchor. Prefer clear section names over generic anchors.
- Do not add bundled Canvas patterns by default. The starter `front-page` template is the demo and source of inspiration.
- Edit the saved `front-page` template in the Site Editor. Do not treat the routing `Home` page's blank content as the source of truth for the visible homepage.
- If the backend Page editor appears blank, inspect or open the Site Editor `front-page` template. The routing `Home` page should redirect there on Monopage Canvas sites.
- Focus Mode should keep the Site Editor canvas direct and calm: top toolbar enabled, Distraction Free mode disabled, Spotlight/focus mode disabled, and the left navigation/sidebar toggle unavailable.
- Focus Mode should hide the public-site admin bar for focused editors so logged-in previews do not expose unrelated WordPress navigation.
- Monopage should not add a dashboard menu, settings page, or control panel. Setup, validation, deployment, and Focus Mode changes belong in WP-CLI and Codex workflows.
- The authoring experience should not teach users about unrelated WordPress admin areas. Keep the visible workflow centered on editing the one page; mention broader WordPress internals only in developer-facing docs or safety notes.
- Focus Mode is UX cleanup only. WordPress capabilities remain the security boundary.

## Design Rules

- Default Monopage work should feel like a cutting-edge ad agency building one-page campaigns: confident, witty, editorial, useful, and a little futuristic.
- Push the theme toward "retrofuture ad agency" energy: sharp composition, campaign boards, modular blocks, strong hierarchy, tactile print texture, Riso-style accents, and launch-ready polish.
- Use generated raster images when the theme or README needs real visual energy. Save project assets into the repo; do not leave referenced images in Codex's generated-image cache.
- Do not put readable text inside generated images unless the user provides exact text. Use the block template for copy so users can edit it.
- Avoid official WordPress logos in generated images unless the mark is intentionally and accurately sourced. Abstract block/editor/page motifs are safer.
- Keep the palette lively but controlled: ink, white, electric blue, teal, coral, lime, and selective Riso-like overprint. Avoid one-note purple gradients, beige/tan dominance, and generic SaaS stock imagery.
- Write copy like a good creative director, not a template generator: concrete, rhythmic, specific, and easy to replace.

## Block-First Theme Rules

- Prefer `theme.json` and block attributes for global typography, colors, spacing, button defaults, Navigation styling, and Site Title styling.
- Use custom CSS only when WordPress block settings cannot express the behavior cleanly: sticky header, hero background images, responsive safety rules, pseudo-elements, scroll margins, and bespoke editorial treatments.
- Before adding CSS, ask whether the same effect belongs in `theme.json`, block `style` attributes, or a core block setting.
- Keep cards at 8px radius or less, avoid nested cards, and keep sections as full-width bands or block groups rather than decorative card stacks.
- Use core blocks first: Group, Columns, Navigation, Buttons, Details, Table, Quote, Separator, Spacer, Site Title, Heading, Paragraph, and List.
- Keep the Canvas theme template-first. Do not add pattern inserter clutter unless the user explicitly asks for reusable patterns.
- If changing theme assets or CSS, run `npm run check:canvas`.

## Template And Pattern Checks

When changing `themes/monopage-canvas/templates/front-page.html`:

1. Keep all hard-coded links on-page.
2. Run `npm run check:links`.
3. Run `npm run check:canvas` when changing Canvas CSS, front-end styling, editor styling, theme assets, or template structure.
4. Run `npm test` before packaging or deploying.
5. If a user explicitly asks for an off-page link, note that it is outside the default Monopage one-page rule and do not add it to the primary starter menu unless they confirm.

When deciding which focused checks fit the current diff, use:

```bash
npm run check:changed
```

This inspects changed files and recommends matching checks. Actual diffs skip visual and runtime checks for version-only plugin/theme metadata changes; `--files` previews stay conservative. Use `npm run check:changed:run` to execute the recommended list.

When orienting in the repo before choosing a workflow, use:

```bash
npm run status
```

This prints the current Monopage version, branch, latest commit, changed-file summary, focused check recommendations, package artifact status and freshness, Codex skill install state, local URLs, and Playground URL without starting wp-env.

## CI Gate

Before a normal push from the Monopage repo, prefer:

```bash
npm run preflight
```

This runs the project status dashboard, repository checks, plugin/theme ZIP builds, and package content verification without requiring Docker, wp-env, or a live WordPress runtime. Use this as the fast local gate; GitHub Actions runs `npm run ci`, and the release gate below adds runtime validation.

## Release Gate

Before a release-minded commit or deploy from the Monopage repo, prefer:

```bash
npm run release:check
```

This runs repository checks, doctor, local readiness, Plugin Check, package builds, package content verification, deploy plan safety validation, deploy dry-run, and Playground URL generation. The local readiness step uses `npm run local:ready` so validation, homepage smoke, admin smoke, and status stay in one workflow. Use `npm run release:check:dry-run` to inspect the sequence. If Docker/wp-env is unavailable, use `node scripts/release-check.mjs --skip-local --skip-plugin-check` and run the skipped checks later on a WordPress runtime.

When preparing a local demo or checking the full local runtime, use:

```bash
npm run local:ready
```

This starts wp-env, refreshes the saved front-page template from the bundled Canvas template, validates Monopage, smokes the rendered homepage, smokes authenticated Focus Mode admin behavior, prints status, and returns the home/admin/Site Editor URLs. Use `npm run local:ready -- --preserve-template` when intentionally preserving saved Site Editor edits during setup.

When visual Canvas work needs local smoke checks, desktop/mobile screenshots, and a fresh Playground URL together, use:

```bash
npm run local:review
```

This runs `local:ready -- --capture`, checks desktop/tablet/mobile responsive safety, then prints the generated Playground URL. Use it after hero, mobile, image, first-viewport, or default-template composition changes when wp-env and a local Chrome/Chromium browser are available.

When checking responsive layout directly, use:

```bash
npm run local:responsive
```

This opens the local homepage in Chrome at desktop, tablet, and mobile sizes. It fails on horizontal page overflow, clipped hero buttons, hidden mobile navigation, or cropped logo-strip pills.

For a narrower template refresh, use:

```bash
npm run local:refresh-template
```

This intentionally runs `wp monopage setup --force-home --force-template` inside wp-env so validation uses the current bundled Canvas template, not an older saved Site Editor copy.

When checking the rendered local front end, use:

```bash
npm run local:smoke
```

This checks required starter copy, same-page body links, section anchors, and served Canvas image assets on the local homepage. Run it after `local:setup` or `local:refresh-template`.

When visual layout, hero, mobile, image, or first-viewport composition changes are part of the work and the broader local review flow is not needed, capture screenshots directly:

```bash
npm run local:capture
```

This uses a local Chrome or Chromium install and writes desktop and mobile screenshots to `build/screenshots/`. Use `npm run local:ready -- --capture` when setup, smoke checks, admin checks, status, and screenshots should run as one local workflow.

When checking Focus Mode admin behavior on the local wp-env runtime, use:

```bash
npm run local:admin-smoke
```

This logs in as the local admin user, confirms the public homepage hides the WordPress admin bar, confirms `/wp-admin/` redirects to the Site Editor front-page canvas, confirms generic Site Editor entrypoints redirect to the canvas, confirms direct Media Library requests redirect to the Site Editor, confirms legacy Monopage admin URLs are not reachable as control surfaces, and confirms the routing Home page editor redirects to the Site Editor. It restores the global Focus Mode option after the check.

When changing the Playground demo or URL behavior, use:

```bash
npm run check:playground
```

This verifies the Blueprint installs the expected theme/plugin repo paths, refreshes the default Canvas template, lands in the Site Editor canvas, and keeps the generated Playground URL in seamless mode.

When changing deploy behavior or safety flags, use:

```bash
npm run check:deploy
```

This verifies the dry-run deploy plan keeps package verification before WP-CLI changes, backs up the database before installs, installs the theme before the plugin, avoids `--force-home`, `--force-template`, and `--check-http` by default, and still honors those flags when explicitly requested.

When changing repository docs, package scripts, or helper script filenames, use:

```bash
npm run check:docs
```

This verifies documented `npm run ...`, `npm test`, and `node scripts/*.mjs` commands in the README, developer wiki, and this skill still point to real scripts.

When changing package scripts, use:

```bash
npm run check:scripts
```

This verifies chained `npm run ...`, `npm test`, and `node scripts/*.mjs` commands in `package.json` still point to real package scripts and helper files.

## Workflow

1. Confirm the target is a WordPress install with WP-CLI:
   - `wp --path=<target> core is-installed`
   - `wp --path=<target> core version`
2. Package Monopage from the source repo if needed:
   - `npm run package`
   - `npm run package:verify`
3. Back up before changing the site:
   - `wp --path=<target> db export monopage-backup-YYYYMMDD-HHMMSS.sql`
4. Install and activate:
   - `wp --path=<target> theme install build/monopage-canvas-<version>.zip --force --activate`
   - `wp --path=<target> plugin install build/monopage-<version>.zip --force --activate`
5. Run setup:
   - `wp --path=<target> monopage setup`
   - Use `--force-home` only when the user explicitly wants Monopage to replace an existing static front page assignment.
   - Use `--force-template` only when the user explicitly wants Monopage to replace saved Site Editor `front-page` template changes with the current Monopage Canvas default.
6. Validate:
   - `wp --path=<target> monopage validate --require-focus`
   - Add `--check-http` only when the target can serve its `home_url()` to WP-CLI during validation.
   - `wp --path=<target> monopage status --format=json`
   - Confirm starter-template links target existing on-page anchors if the template was customized: `npm run check:links`
   - Confirm Canvas styles and CSS assets are wired for both the Site Editor and front end if the theme was customized: `npm run check:canvas`
   - Smoke the rendered local homepage after Canvas template or asset changes: `npm run local:smoke`
   - Smoke Focus Mode admin redirects and chrome hiding on local wp-env after admin or Site Editor changes: `npm run local:admin-smoke`
   - Confirm the public Playground demo path after Blueprint or URL changes: `npm run check:playground`
   - Confirm deploy plan ordering and default safety flags after deployment helper changes: `npm run check:deploy`
   - Run Plugin Check on a local or staging WordPress install before release: `npm run plugin:check`
   - Verify package contents before installing built ZIPs: `npm run package:verify`
   - Confirm the homepage URL loads.
   - Confirm `/wp-admin/site-editor.php` loads for an authenticated user.
   - Confirm `/wp-admin/` redirects to the Site Editor while Focus Mode is active.
   - Confirm direct Media Library/admin screens redirect back to the Site Editor while editor upload endpoints still work through the editor.

## Helper Script

From the Monopage repo:

```bash
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

The deploy script packages and verifies the plugin/theme ZIP contents before installing them, then runs `wp monopage validate --require-focus` after setup and before status. Use `--skip-package` only when the ZIPs are already built. Use `--skip-package-verify` only when intentionally deploying custom ZIPs outside the current version contract. Use `--check-http` only when homepage HTTP requests from WP-CLI are expected to work in the target environment. Do not use `--check-http` for normal `wp-env` validation because the CLI container may not be able to reach the host-facing `localhost` URL.

Run WordPress Plugin Check through the local `wp-env` stack:

```bash
npm run local:start
npm run plugin:check
npm run plugin:check:runtime
```

If Docker or `wp-env` is unavailable, use the same runner against a real WP-CLI target:

```bash
node scripts/plugin-check.mjs --path=/path/to/wordpress
```

The Plugin Check helper loads Plugin Check's CLI file automatically. Use `--no-require` only for an environment that already registers `wp plugin check`.

Validate a configured target:

```bash
wp --path=/path/to/wordpress monopage validate --require-focus
wp --path=/path/to/wordpress monopage validate --require-focus --format=json
```

For remote hosts, prefer WP-CLI SSH only when the ZIP paths are visible to the target or have been uploaded first:

```bash
node scripts/deploy-monopage.mjs --dry-run --ssh=user@example.com:/site --path=/var/www/html
```

## Safety Rules

- Always run or confirm a DB backup before install, setup, or Focus Mode changes.
- Do not pass `--force-home` on existing sites unless the user explicitly approves replacing the front page assignment.
- Do not pass `--force-template` unless the user explicitly approves replacing saved front-page template edits.
- Treat Focus Mode as UX cleanup, not access control. WordPress roles and capabilities remain the security boundary.
- Keep Monopage Canvas active unless the target theme is a confirmed block theme with a front-page template.
- If deployment fails after the backup but before setup, report the backup filename and the last successful command.

## Local Skill Install

```bash
npm run skill:install
```

The installer creates the `monopage-deploy` skill symlink under `$CODEX_HOME/skills` or `~/.codex/skills`. It removes the old `wpop-deploy` entry only when that legacy entry is a symlink.

To preview the install:

```bash
npm run skill:install:dry-run
```
