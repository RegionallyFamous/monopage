---
name: wpop-deploy
description: Deploy, package, inspect, or troubleshoot WPOP / WordPress One Pager sites from Codex using WP-CLI. Use when the user asks to deploy WPOP, install WPOP on a WordPress host, package the WPOP plugin/theme, run WPOP setup, validate a WPOP one-page site, or manage WPOP Focus Mode.
---

# WPOP Deploy

Use this skill to deploy WPOP sites safely through WP-CLI.

## Workflow

1. Confirm the target is a WordPress install with WP-CLI:
   - `wp --path=<target> core is-installed`
   - `wp --path=<target> core version`
2. Package WPOP from the source repo if needed:
   - `npm run package`
3. Back up before changing the site:
   - `wp --path=<target> db export wpop-backup-YYYYMMDD-HHMMSS.sql`
4. Install and activate:
   - `wp --path=<target> theme install build/wpop-canvas-0.2.0.zip --force --activate`
   - `wp --path=<target> plugin install build/wpop-0.2.0.zip --force --activate`
5. Run setup:
   - `wp --path=<target> wpop setup`
   - Use `--force-home` only when the user explicitly wants WPOP to replace an existing static front page assignment.
6. Validate:
   - `wp --path=<target> wpop status --format=json`
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
