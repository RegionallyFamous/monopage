# WPOP

WPOP, WordPress One Pager, is a focused WordPress setup for sites that should behave like a single editable homepage. It keeps WordPress intact, activates a minimal block theme, and narrows the admin experience toward the Site Editor.

## Project Layout

- `plugins/wpop/`: Focus Mode, one-page setup, admin controls, and WP-CLI commands.
- `themes/wpop-canvas/`: Minimal block theme with the canonical editable marketing `front-page.html`.
- `skills/wpop-deploy/`: Companion Codex skill source for deploying WPOP through WP-CLI.
- `scripts/`: Packaging, local setup, validation, and deploy helpers.

## Local Development

```bash
npm install
npm run local:start
npm run local:setup
npm run local:status
```

## Playground

Open WPOP in WordPress Playground:

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/RegionallyFamous/wpop/main/playground/blueprint.json

## Packaging

```bash
npm run package
```

This writes plugin and theme ZIPs into `build/`.

## Deploy Dry Run

```bash
npm run deploy:dry-run
node scripts/deploy-wpop.mjs --dry-run --path=/path/to/wordpress
```

For a real local WP-CLI deployment:

```bash
npm run package
node scripts/deploy-wpop.mjs --path=/path/to/wordpress
```

Add `--force-home` only when WPOP should replace an existing static front page assignment.

## Install The Companion Skill

```bash
ln -s /Users/nick/Documents/GitHub/wpop/skills/wpop-deploy /Users/nick/.codex/skills/wpop-deploy
```
