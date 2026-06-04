import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const skillPath = path.join(root, "skills/monopage-deploy/SKILL.md");
const metadataPath = path.join(root, "skills/monopage-deploy/agents/openai.yaml");
const skill = fs.readFileSync(skillPath, "utf8");
const metadata = fs.readFileSync(metadataPath, "utf8");
const failures = [];

const skillRequirements = [
  ["skill name", "name: monopage-deploy"],
  ["skill description", "Deploy, package, inspect, customize, or troubleshoot Monopage sites"],
  ["one-page rule", "Monopage is one page."],
  ["same-page anchors", "`#top`, `#promise`, `#services`, `#showcase`, `#results`, `#pricing`, `#questions`, or `#start`"],
  ["no off-page starter links", "The starter template should not include `href=\"/\"`"],
  ["non-linking Site Title", "site title in Monopage Canvas should be non-linking"],
  ["core Navigation block", "Use the core Navigation block"],
  ["front-page source of truth", "Edit the saved `front-page` template in the Site Editor"],
  ["blank Page editor guidance", "If the backend Page editor appears blank"],
  ["Focus Mode boundary", "Focus Mode is UX cleanup only"],
  ["retrofuture design direction", "retrofuture ad agency"],
  ["generated raster image rule", "Use generated raster images"],
  ["no readable generated text", "Do not put readable text inside generated images"],
  ["avoid WordPress marks", "Avoid official WordPress logos"],
  ["theme.json first", "Prefer `theme.json`"],
  ["custom CSS restraint", "Use custom CSS only when WordPress block settings cannot express"],
  ["core blocks first", "Use core blocks first"],
  ["Canvas patterns first", "Use Canvas patterns before inventing"],
  ["link check command", "npm run check:links"],
  ["Canvas check command", "npm run check:canvas"],
  ["test command", "npm test"],
  ["CI command", "npm run ci"],
  ["release gate command", "npm run release:check"],
  ["local ready command", "npm run local:ready"],
  ["template refresh command", "npm run local:refresh-template"],
  ["homepage smoke command", "npm run local:smoke"],
  ["admin smoke command", "npm run local:admin-smoke"],
  ["Playground check command", "npm run check:playground"],
  ["deploy plan check command", "npm run check:deploy"],
  ["WP-CLI install check", "wp --path=<target> core is-installed"],
  ["backup before deploy", "wp --path=<target> db export"],
  ["setup command", "wp --path=<target> monopage setup"],
  ["runtime validation", "wp --path=<target> monopage validate --require-focus"],
  ["status command", "wp --path=<target> monopage status --format=json"],
  ["Plugin Check command", "npm run plugin:check"],
  ["package verification command", "npm run package:verify"],
  ["package verification skip safety", "Use `--skip-package-verify` only when intentionally deploying custom ZIPs"],
  ["force-home safety", "Do not pass `--force-home`"],
  ["force-template safety", "Do not pass `--force-template`"],
  ["skill install command", "npm run skill:install"],
];

const metadataRequirements = [
  ["display name", 'display_name: "Monopage"'],
  ["one-page short description", "one-page WordPress sites"],
  ["default prompt skill name", "$monopage-deploy"],
  ["default prompt one-page rule", "keeping navigation on the same page"],
  ["implicit invocation", "allow_implicit_invocation: true"],
];

for (const [label, expected] of skillRequirements) {
  assertContains(skill, expected, `SKILL.md ${label}`);
}

for (const [label, expected] of metadataRequirements) {
  assertContains(metadata, expected, `agents/openai.yaml ${label}`);
}

if (failures.length) {
  console.error("Monopage skill contract checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Monopage skill contract covers ${skillRequirements.length} workflow rules and ${metadataRequirements.length} metadata rules.`);

function assertContains(content, expected, label) {
  if (!content.includes(expected)) {
    failures.push(`${label}: missing ${expected}`);
  }
}
