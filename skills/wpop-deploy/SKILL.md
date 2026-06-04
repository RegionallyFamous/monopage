---
name: wpop-deploy
description: Deploy, package, inspect, customize, or troubleshoot WPOP / WordPress One Pager sites from Codex using WP-CLI and the WPOP Canvas rules. Use when the user asks to deploy WPOP, install WPOP on a WordPress host, package the WPOP plugin/theme, run WPOP setup, validate a WPOP one-page site, customize the WPOP one-page template, or manage WPOP Focus Mode.
---

# WPOP Deploy

Use this skill to deploy, validate, and lightly customize WPOP sites safely through WP-CLI.

## WPOP Product Rules

- WPOP is one page. Navigation should move visitors up and down the same page, not to other WordPress pages, posts, archives, or external URLs.
- Primary menu items, footer navigation, and starter-template CTAs should use section anchors such as `#top`, `#services`, `#results`, `#pricing`, `#questions`, or `#start`.
- The starter template should not include `href="/"`, page URLs, post URLs, category/archive links, or external links unless the user explicitly asks for that specific off-page destination.
- The site title in WPOP Canvas should be non-linking in the starter template, because the header is a one-page scroll map rather than a site-wide navigation bar.
- Add new sections by giving the target block a stable anchor and linking to that anchor. Prefer clear section names over generic anchors.
- Edit the saved `front-page` template in the Site Editor. Do not treat the routing `Home` page's blank content as the source of truth for the visible homepage.
- Focus Mode is UX cleanup only. WordPress capabilities remain the security boundary.

## Template Checks

When changing `themes/wpop-canvas/templates/front-page.html`:

1. Keep all hard-coded links on-page.
2. Run `npm run check:links`.
3. Run `npm test` before packaging or deploying.
4. If a user explicitly asks for an off-page link, note that it is outside the default WPOP one-page rule and do not add it to the primary starter menu unless they confirm.

## Workflow

1. Confirm the target is a WordPress install with WP-CLI:
   - `wp --path=<target> core is-installed`
   - `wp --path=<target> core version`
2. Package WPOP from the source repo if needed:
   - `npm run package`
3. Back up before changing the site:
   - `wp --path=<target> db export wpop-backup-YYYYMMDD-HHMMSS.sql`
4. Install and activate:
   - `wp --path=<target> theme install build/wpop-canvas-0.2.2.zip --force --activate`
   - `wp --path=<target> plugin install build/wpop-0.2.2.zip --force --activate`
5. Run setup:
   - `wp --path=<target> wpop setup`
   - Use `--force-home` only when the user explicitly wants WPOP to replace an existing static front page assignment.
6. Validate:
   - `wp --path=<target> wpop status --format=json`
   - Confirm starter-template links are on-page if the template was customized: `npm run check:links`
   - Confirm the homepage URL loads.
   - Confirm `/wp-admin/site-editor.php` loads for an authenticated user.
   - Confirm `/wp-admin/` redirects to the Site Editor while Focus Mode is active.
   - Confirm Media Library remains reachable.

## Helper Script

From the WPOP repo:

```bash
node scripts/deploy-wpop.mjs --dry-run --path=/path/to/wordpress
node scripts/deploy-wpop.mjs --path=/path/to/wordpress
```

For remote hosts, prefer WP-CLI SSH only when the ZIP paths are visible to the target or have been uploaded first:

```bash
node scripts/deploy-wpop.mjs --dry-run --ssh=user@example.com:/site --path=/var/www/html
```

## Safety Rules

- Always run or confirm a DB backup before install, setup, or Focus Mode changes.
- Do not pass `--force-home` on existing sites unless the user explicitly approves replacing the front page assignment.
- Treat Focus Mode as UX cleanup, not access control. WordPress roles and capabilities remain the security boundary.
- Keep WPOP Canvas active unless the target theme is a confirmed block theme with a front-page template.
- If deployment fails after the backup but before setup, report the backup filename and the last successful command.

## Local Skill Install

```bash
ln -s /Users/nick/Documents/GitHub/wpop/skills/wpop-deploy /Users/nick/.codex/skills/wpop-deploy
```
