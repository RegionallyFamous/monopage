import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;
const packages = [
  {
    label: "plugin",
    zip: options["plugin-zip"] || `build/monopage-${version}.zip`,
    root: "monopage/",
    required: [
      "monopage/monopage.php",
      "monopage/readme.txt",
      "monopage/assets/admin.css",
      "monopage/assets/site-editor.js",
    ],
    versionChecks: [
      {
        file: "monopage/monopage.php",
        pattern: /Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
        label: "plugin header",
      },
      {
        file: "monopage/monopage.php",
        pattern: /define\(\s*'MONOPAGE_VERSION',\s*'([^']+)'\s*\)/,
        label: "plugin constant",
      },
      {
        file: "monopage/readme.txt",
        pattern: /Stable tag:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
        label: "readme stable tag",
      },
    ],
  },
  {
    label: "theme",
    zip: options["theme-zip"] || `build/monopage-canvas-${version}.zip`,
    root: "monopage-canvas/",
    required: [
      "monopage-canvas/functions.php",
      "monopage-canvas/style.css",
      "monopage-canvas/theme.json",
      "monopage-canvas/templates/front-page.html",
      "monopage-canvas/templates/index.html",
      "monopage-canvas/templates/page.html",
      "monopage-canvas/patterns/offer-lab.php",
      "monopage-canvas/patterns/proof-strip.php",
      "monopage-canvas/patterns/pricing-deck.php",
      "monopage-canvas/patterns/question-stack.php",
      "monopage-canvas/patterns/final-push.php",
      "monopage-canvas/assets/monopage-riso-hero.jpg",
      "monopage-canvas/assets/monopage-riso-flow.jpg",
      "monopage-canvas/assets/monopage-riso-launch.jpg",
      "monopage-canvas/assets/images/monopage-riso-future-agency-hero.jpg",
      "monopage-canvas/assets/images/monopage-riso-print-lab.jpg",
      "monopage-canvas/assets/images/monopage-riso-skyline-banner.jpg",
    ],
    versionChecks: [
      {
        file: "monopage-canvas/style.css",
        pattern: /Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
        label: "theme header",
      },
    ],
  },
];
const forbiddenPatterns = [
  /(^|\/)\.DS_Store$/,
  /(^|\/)\.env(?:\.|$)/,
  /^__MACOSX\//,
  /(^|\/)node_modules\//,
  /(^|\/)build\//,
  /(^|\/)\.git\//,
  /(^|\/)npm-debug\.log/,
  /\.sql$/i,
  /\.zip$/i,
  /(^|\/)vendor\//,
];
const failures = [];

for (const packageSpec of packages) {
  checkPackage(packageSpec);
}

if (failures.length) {
  console.error("Package content checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Package contents match Monopage ${version}.`);

function checkPackage(packageSpec) {
  const zipPath = path.isAbsolute(packageSpec.zip) ? packageSpec.zip : path.join(root, packageSpec.zip);
  const zipLabel = path.isAbsolute(packageSpec.zip) ? packageSpec.zip : packageSpec.zip;

  if (!fs.existsSync(zipPath)) {
    failures.push(`missing ${packageSpec.label} ZIP: ${zipLabel}`);
    return;
  }

  const entries = listZipEntries(zipPath);
  if (!entries.length) {
    failures.push(`${zipLabel} has no entries`);
    return;
  }

  const entrySet = new Set(entries);

  for (const entry of entries) {
    if (!entry.startsWith(packageSpec.root)) {
      failures.push(`${zipLabel} has unexpected top-level path: ${entry}`);
    }

    for (const forbidden of forbiddenPatterns) {
      if (forbidden.test(entry)) {
        failures.push(`${zipLabel} includes forbidden path: ${entry}`);
      }
    }
  }

  for (const required of packageSpec.required) {
    if (!entrySet.has(required)) {
      failures.push(`${zipLabel} is missing required file: ${required}`);
    }
  }

  for (const versionCheck of packageSpec.versionChecks) {
    if (!entrySet.has(versionCheck.file)) {
      continue;
    }

    const content = readZipFile(zipPath, versionCheck.file);
    const match = content.match(versionCheck.pattern);

    if (!match) {
      failures.push(`${zipLabel} ${versionCheck.label}: version not found in ${versionCheck.file}`);
    } else if (match[1] !== version) {
      failures.push(`${zipLabel} ${versionCheck.label}: expected ${version}, found ${match[1]}`);
    }
  }
}

function listZipEntries(zipPath) {
  const result = spawnSync("unzip", ["-Z1", zipPath], {
    encoding: "utf8",
  });

  if (result.status !== 0) {
    failures.push(`could not list ${path.relative(root, zipPath)}; is unzip available?`);
    return [];
  }

  return result.stdout
    .split(/\r?\n/)
    .map((entry) => entry.trim())
    .filter(Boolean);
}

function readZipFile(zipPath, entry) {
  const result = spawnSync("unzip", ["-p", zipPath, entry], {
    encoding: "utf8",
  });

  if (result.status !== 0) {
    failures.push(`could not read ${entry} from ${path.relative(root, zipPath)}`);
    return "";
  }

  return result.stdout;
}

function parseArgs(args) {
  const parsed = {};

  for (const arg of args) {
    if (!arg.startsWith("--")) {
      continue;
    }

    const body = arg.slice(2);
    const equals = body.indexOf("=");

    if (equals === -1) {
      parsed[body] = true;
    } else {
      parsed[body.slice(0, equals)] = body.slice(equals + 1);
    }
  }

  return parsed;
}
