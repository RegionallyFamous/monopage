import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const blueprintPath = path.join(root, "playground/blueprint.json");
const urlScriptPath = path.join(root, "scripts/playground-url.mjs");
const blueprint = JSON.parse(fs.readFileSync(blueprintPath, "utf8"));
const urlScript = fs.readFileSync(urlScriptPath, "utf8");
const repoUrl = "https://github.com/RegionallyFamous/monopage";
const failures = [];

assertEqual(blueprint.$schema, "https://playground.wordpress.net/blueprint-schema.json", "Blueprint schema URL");
assertEqual(blueprint.landingPage, "/wp-admin/site-editor.php?canvas=edit", "Blueprint landing page");
assertEqual(blueprint.preferredVersions?.wp, "latest", "Blueprint WordPress version");
assertTruthy(blueprint.preferredVersions?.php, "Blueprint PHP version");
assertEqual(blueprint.features?.networking, true, "Blueprint networking");
assertArray(blueprint.steps, "Blueprint steps");

const loginStep = findStep("login");
assertEqual(loginStep?.username, "admin", "Blueprint login username");
assertTruthy(loginStep?.password, "Blueprint login password");

const themeStep = findStep("installTheme");
assertGitDirectoryStep(themeStep, {
  label: "theme",
  path: "themes/monopage-canvas",
  targetFolderName: "monopage-canvas",
});

const pluginStep = findStep("installPlugin");
assertGitDirectoryStep(pluginStep, {
  label: "plugin",
  path: "plugins/monopage",
  targetFolderName: "monopage",
});

const setupStep = findStep("runPHP");
assertContains(setupStep?.code || "", "require_once '/wordpress/wp-load.php'", "Blueprint setup loads WordPress");
assertContains(setupStep?.code || "", "update_option('blogname', 'Monopage Playground')", "Blueprint sets Playground title");
assertContains(setupStep?.code || "", "monopage_setup_one_pager", "Blueprint runs Monopage setup");
assertContains(setupStep?.code || "", "'force_home' => true", "Blueprint forces routing Home page");
assertContains(setupStep?.code || "", "'force_template' => true", "Blueprint refreshes Canvas template");

assertContains(urlScript, "RegionallyFamous", "Playground URL owner");
assertContains(urlScript, "monopage", "Playground URL repo");
assertContains(urlScript, "main", "Playground URL branch");
assertContains(urlScript, "raw.githubusercontent.com", "Playground URL raw blueprint host");
assertContains(urlScript, "playground/blueprint.json", "Playground URL blueprint path");
assertContains(urlScript, "mode=seamless", "Playground URL seamless mode");

if (failures.length) {
  console.error("Playground checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("Playground Blueprint and URL are wired for the Monopage demo.");

function findStep(stepName) {
  return Array.isArray(blueprint.steps) ? blueprint.steps.find((step) => step.step === stepName) : undefined;
}

function assertGitDirectoryStep(step, expected) {
  assertTruthy(step, `Blueprint ${expected.label} install step`);
  assertEqual(step?.[`${expected.label}Data`]?.resource, "git:directory", `Blueprint ${expected.label} resource`);
  assertEqual(step?.[`${expected.label}Data`]?.url, repoUrl, `Blueprint ${expected.label} repo URL`);
  assertEqual(step?.[`${expected.label}Data`]?.ref, "main", `Blueprint ${expected.label} branch`);
  assertEqual(step?.[`${expected.label}Data`]?.refType, "branch", `Blueprint ${expected.label} ref type`);
  assertEqual(step?.[`${expected.label}Data`]?.path, expected.path, `Blueprint ${expected.label} source path`);
  assertEqual(step?.options?.activate, true, `Blueprint ${expected.label} activation`);
  assertEqual(step?.options?.targetFolderName, expected.targetFolderName, `Blueprint ${expected.label} target folder`);
}

function assertArray(value, label) {
  if (!Array.isArray(value) || value.length === 0) {
    failures.push(`${label} must be a non-empty array`);
  }
}

function assertTruthy(value, label) {
  if (!value) {
    failures.push(`${label} is missing`);
  }
}

function assertEqual(actual, expected, label) {
  if (actual !== expected) {
    failures.push(`${label}: expected ${expected}, found ${String(actual)}`);
  }
}

function assertContains(value, expected, label) {
  if (!String(value).includes(expected)) {
    failures.push(`${label}: missing ${expected}`);
  }
}
