import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = readJson("package.json");
const version = manifest.version;
const checks = [
  {
    label: "plugin header",
    file: "plugins/monopage/monopage.php",
    pattern: /Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
  },
  {
    label: "plugin constant",
    file: "plugins/monopage/monopage.php",
    pattern: /define\(\s*'MONOPAGE_VERSION',\s*'([^']+)'\s*\)/,
  },
  {
    label: "plugin readme stable tag",
    file: "plugins/monopage/readme.txt",
    pattern: /Stable tag:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
  },
  {
    label: "theme header",
    file: "themes/monopage-canvas/style.css",
    pattern: /Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/,
  },
];

const failures = [];

for (const check of checks) {
  const content = readFile(check.file);
  const match = content.match(check.pattern);

  if (!match) {
    failures.push(`${check.label}: could not find version in ${check.file}`);
    continue;
  }

  if (match[1] !== version) {
    failures.push(`${check.label}: expected ${version}, found ${match[1]}`);
  }
}

assertContains("plugins/monopage/readme.txt", `= ${version} =`, "plugin readme changelog");
assertContains("skills/monopage-deploy/SKILL.md", "build/monopage-<version>.zip", "skill plugin ZIP example");
assertContains("skills/monopage-deploy/SKILL.md", "build/monopage-canvas-<version>.zip", "skill theme ZIP example");

if (failures.length) {
  console.error("Monopage version metadata is out of sync:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Version metadata matches ${version}.`);

function assertContains(file, expected, label) {
  if (!readFile(file).includes(expected)) {
    failures.push(`${label}: missing ${expected}`);
  }
}

function readFile(file) {
  return fs.readFileSync(path.join(root, file), "utf8");
}

function readJson(file) {
  return JSON.parse(readFile(file));
}
