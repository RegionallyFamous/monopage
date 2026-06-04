import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;
const packages = [
  {
    label: "plugin",
    zip: `build/monopage-${version}.zip`,
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
    zip: `build/monopage-canvas-${version}.zip`,
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
  /^__MACOSX\//,
  /(^|\/)node_modules\//,
  /(^|\/)build\//,
  /(^|\/)\.git\//,
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
  const zipPath = path.join(root, packageSpec.zip);

  if (!fs.existsSync(zipPath)) {
    failures.push(`missing ${packageSpec.label} ZIP: ${packageSpec.zip}`);
    return;
  }

  const entries = listZipEntries(zipPath);
  if (!entries.length) {
    failures.push(`${packageSpec.zip} has no entries`);
    return;
  }

  const entrySet = new Set(entries);

  for (const entry of entries) {
    if (!entry.startsWith(packageSpec.root)) {
      failures.push(`${packageSpec.zip} has unexpected top-level path: ${entry}`);
    }

    for (const forbidden of forbiddenPatterns) {
      if (forbidden.test(entry)) {
        failures.push(`${packageSpec.zip} includes forbidden path: ${entry}`);
      }
    }
  }

  for (const required of packageSpec.required) {
    if (!entrySet.has(required)) {
      failures.push(`${packageSpec.zip} is missing required file: ${required}`);
    }
  }

  for (const versionCheck of packageSpec.versionChecks) {
    if (!entrySet.has(versionCheck.file)) {
      continue;
    }

    const content = readZipFile(zipPath, versionCheck.file);
    const match = content.match(versionCheck.pattern);

    if (!match) {
      failures.push(`${packageSpec.zip} ${versionCheck.label}: version not found in ${versionCheck.file}`);
    } else if (match[1] !== version) {
      failures.push(`${packageSpec.zip} ${versionCheck.label}: expected ${version}, found ${match[1]}`);
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
