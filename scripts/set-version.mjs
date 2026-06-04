import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const args = parseArgs(process.argv.slice(2));
const version = args._[0] || "";
const changelog = args.changelog || "";
const dryRun = Boolean(args["dry-run"]);
const semverPattern = /^[0-9]+\.[0-9]+\.[0-9]+$/;
const changedFiles = new Set();

if (!semverPattern.test(version)) {
  fail("Usage: node scripts/set-version.mjs <x.y.z> --changelog=\"Short release note\"");
}

const readme = readFile("plugins/monopage/readme.txt");
if (!readme.includes(`= ${version} =`) && !changelog.trim()) {
  fail(`Missing --changelog for new version ${version}.`);
}

updatePackageJson();
replaceInFile("plugins/monopage/monopage.php", [
  [/Version:\s*[0-9]+\.[0-9]+\.[0-9]+/, `Version:           ${version}`],
  [/define\(\s*'MONOPAGE_VERSION',\s*'[^']+'\s*\)/, `define( 'MONOPAGE_VERSION', '${version}' )`],
]);
replaceInFile("plugins/monopage/readme.txt", [
  [/Stable tag:\s*[0-9]+\.[0-9]+\.[0-9]+/, `Stable tag: ${version}`],
]);
ensureChangelogEntry();
replaceInFile("themes/monopage-canvas/style.css", [
  [/Version:\s*[0-9]+\.[0-9]+\.[0-9]+/, `Version: ${version}`],
]);
replaceInFile("skills/monopage-deploy/SKILL.md", [
  [/build\/monopage-canvas-[0-9]+\.[0-9]+\.[0-9]+\.zip/g, `build/monopage-canvas-${version}.zip`],
  [/build\/monopage-[0-9]+\.[0-9]+\.[0-9]+\.zip/g, `build/monopage-${version}.zip`],
]);

if (changedFiles.size) {
  for (const file of changedFiles) {
    console.log(`${dryRun ? "[dry-run] would update" : "Updated"} ${file}`);
  }
} else {
  console.log(`Version metadata already set to ${version}.`);
}

function updatePackageJson() {
  const file = "package.json";
  const fullPath = path.join(root, file);
  const manifest = JSON.parse(fs.readFileSync(fullPath, "utf8"));

  if (manifest.version === version) {
    return;
  }

  manifest.version = version;
  writeFile(file, `${JSON.stringify(manifest, null, 2)}\n`);
}

function ensureChangelogEntry() {
  const file = "plugins/monopage/readme.txt";
  const content = readFile(file);

  if (content.includes(`= ${version} =`)) {
    return;
  }

  const marker = "== Changelog ==\n\n";
  if (!content.includes(marker)) {
    fail("Could not find plugin readme changelog marker.");
  }

  const entry = `= ${version} =\n* ${changelog.trim()}\n\n`;
  writeFile(file, content.replace(marker, `${marker}${entry}`));
}

function replaceInFile(file, replacements) {
  let content = readFile(file);

  for (const [pattern, replacement] of replacements) {
    if (!pattern.test(content)) {
      fail(`Could not update ${file}; missing pattern ${pattern}`);
    }

    content = content.replace(pattern, replacement);
  }

  writeFile(file, content);
}

function readFile(file) {
  return fs.readFileSync(path.join(root, file), "utf8");
}

function writeFile(file, content) {
  const fullPath = path.join(root, file);
  const current = fs.existsSync(fullPath) ? fs.readFileSync(fullPath, "utf8") : "";

  if (current === content) {
    return;
  }

  changedFiles.add(file);

  if (!dryRun) {
    fs.writeFileSync(fullPath, content);
  }
}

function parseArgs(rawArgs) {
  const parsed = { _: [] };

  for (const arg of rawArgs) {
    if (!arg.startsWith("--")) {
      parsed._.push(arg);
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

function fail(message) {
  console.error(message);
  process.exit(1);
}
