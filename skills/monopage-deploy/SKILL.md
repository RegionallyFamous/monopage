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
- Add new sections by giving the target block a stable anchor and linking to that anchor. Prefer clear section names over generic anchors.
- Edit the saved `front-page` template in the Site Editor. Do not treat the routing `Home` page's blank content as the source of truth for the visible homepage.
- If the backend Page editor appears blank, inspect or open the Site Editor `front-page` template. The routing `Home` page should redirect there on Monopage Canvas sites.
- Focus Mode is UX cleanup only. WordPress capabilities remain the security boundary.

## Template Checks

When changing `themes/monopage-canvas/templates/front-page.html`:

1. Keep all hard-coded links on-page.
2. Run `npm run check:links`.
3. Run `npm test` before packaging or deploying.
4. If a user explicitly asks for an off-page link, note that it is outside the default Monopage one-page rule and do not add it to the primary starter menu unless they confirm.

## Workflow

1. Confirm the target is a WordPress install with WP-CLI:
   - `wp --path=<target> core is-installed`
   - `wp --path=<target> core version`
2. Package Monopage from the source repo if needed:
   - `npm run package`
3. Back up before changing the site:
   - `wp --path=<target> db export monopage-backup-YYYYMMDD-HHMMSS.sql`
4. Install and activate:
   - `wp --path=<target> theme install build/monopage-canvas-0.2.4.zip --force --activate`
   - `wp --path=<target> plugin install build/monopage-0.2.4.zip --force --activate`
5. Run setup:
   - `wp --path=<target> monopage setup`
   - Use `--force-home` only when the user explicitly wants Monopage to replace an existing static front page assignment.
6. Validate:
   - `wp --path=<target> monopage status --format=json`
   - Confirm starter-template links are on-page if the template was customized: `npm run check:links`
   - Confirm the homepage URL loads.
   - Confirm `/wp-admin/site-editor.php` loads for an authenticated user.
   - Confirm `/wp-admin/` redirects to the Site Editor while Focus Mode is active.
   - Confirm Media Library remains reachable.

## Helper Script

From the Monopage repo:

```bash
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

For remote hosts, prefer WP-CLI SSH only when the ZIP paths are visible to the target or have been uploaded first:

```bash
node scripts/deploy-monopage.mjs --dry-run --ssh=user@example.com:/site --path=/var/www/html
```

## Safety Rules

- Always run or confirm a DB backup before install, setup, or Focus Mode changes.
- Do not pass `--force-home` on existing sites unless the user explicitly approves replacing the front page assignment.
- Treat Focus Mode as UX cleanup, not access control. WordPress roles and capabilities remain the security boundary.
- Keep Monopage Canvas active unless the target theme is a confirmed block theme with a front-page template.
- If deployment fails after the backup but before setup, report the backup filename and the last successful command.

## Local Skill Install

```bash
ln -s /Users/nick/Documents/GitHub/monopage/skills/monopage-deploy /Users/nick/.codex/skills/monopage-deploy
```
