# Monopage

Monopage is a focused WordPress setup for sites that should behave like a single editable homepage. It keeps WordPress intact, activates a block theme, and narrows the admin experience toward the Site Editor.

## Project Layout

- `plugins/monopage/`: Focus Mode, one-page setup, admin controls, and WP-CLI commands.
- `themes/monopage-canvas/`: Minimal block theme with the canonical editable marketing `front-page.html`.
- `skills/monopage-deploy/`: Companion Codex skill source for deploying Monopage through WP-CLI.
- `scripts/`: Packaging, local setup, validation, and deploy helpers.

## Local Development

```bash
npm install
npm run local:start
npm run local:setup
npm run local:status
```

Check local release metadata and tool availability:

```bash
npm run doctor
```

## Playground

Open Monopage in WordPress Playground:

https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2FRegionallyFamous%2Fmonopage%2Fmain%2Fplayground%2Fblueprint.json

## Packaging

Update release metadata with one command:

```bash
npm run version:set -- 0.2.10 --changelog="Short release note"
```

```bash
npm run package
```

This writes plugin and theme ZIPs into `build/`.

## Deploy Dry Run

```bash
npm run deploy:dry-run
node scripts/deploy-monopage.mjs --dry-run --path=/path/to/wordpress
```

For a real local WP-CLI deployment:

```bash
npm run package
node scripts/deploy-monopage.mjs --path=/path/to/wordpress
```

Add `--force-home` only when Monopage should replace an existing static front page assignment.

Add `--force-template` only when Monopage should replace the saved Site Editor `front-page` template with the current Monopage Canvas default.

## Install The Companion Skill

```bash
npm run skill:install
```

Preview the install without changing your Codex skill folder:

```bash
npm run skill:install:dry-run
```
