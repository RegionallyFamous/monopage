import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const checkedRoots = ["plugins", "themes", "scripts"];
const checkedExtensions = new Set([".js", ".mjs"]);
const ignoredDirectories = new Set([".git", ".wp-env", "build", "node_modules", "vendor"]);
const failures = [];

const files = checkedRoots
  .flatMap((relativeRoot) => collectSyntaxFiles(path.join(root, relativeRoot)))
  .sort((a, b) => a.localeCompare(b));

if (!files.length) {
  console.error("No JavaScript files found to syntax check.");
  process.exit(1);
}

for (const file of files) {
  const relative = path.relative(root, file);
  const result = spawnSync("node", ["--check", file], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status === 0) {
    console.log(`OK ${relative}`);
    continue;
  }

  failures.push(relative);
  if (result.stderr.trim()) {
    console.error(result.stderr.trim());
  }
  if (result.stdout.trim()) {
    console.error(result.stdout.trim());
  }
}

if (failures.length) {
  console.error("");
  console.error("JavaScript syntax checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`JavaScript syntax checks passed for ${files.length} files.`);

function collectSyntaxFiles(directory) {
  if (!fs.existsSync(directory)) {
    return [];
  }

  const entries = fs.readdirSync(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      if (!ignoredDirectories.has(entry.name)) {
        files.push(...collectSyntaxFiles(fullPath));
      }
      continue;
    }

    if (entry.isFile() && checkedExtensions.has(path.extname(entry.name))) {
      files.push(fullPath);
    }
  }

  return files;
}
