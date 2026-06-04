import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const failures = [];

const requiredGitignoreRules = [
  ".DS_Store",
  "node_modules/",
  "build/",
  ".wp-env/",
  "npm-debug.log*",
  ".env",
  ".env.*",
  "!.env.example",
  "*.sql",
  "*.zip",
];

const requiredAttributeRules = [
  "* text=auto",
  "*.php text eol=lf",
  "*.mjs text eol=lf",
  "*.js text eol=lf",
  "*.json text eol=lf",
  "*.css text eol=lf",
  "*.html text eol=lf",
  "*.jpg binary",
  "*.zip binary",
  "/.github/** export-ignore",
  "/.wp-env/** export-ignore",
  "/.wp-env.json export-ignore",
  "/build/** export-ignore",
  "/node_modules/** export-ignore",
  ".env export-ignore",
  ".env.* export-ignore",
  "*.sql export-ignore",
  "*.zip export-ignore",
  "npm-debug.log* export-ignore",
];

const forbiddenTrackedPatterns = [
  [/\.DS_Store$/, "macOS metadata"],
  [/^build\//, "built package output"],
  [/^node_modules\//, "Node dependency tree"],
  [/^\.wp-env\//, "local WordPress runtime data"],
  [/(^|\/)npm-debug\.log/, "npm debug log"],
  [/(^|\/)\.env(?:\.|$)/, "environment file"],
  [/\.sql$/i, "database backup"],
  [/\.zip$/i, "archive artifact"],
];

checkRequiredLines(".gitignore", requiredGitignoreRules);
checkRequiredLines(".gitattributes", requiredAttributeRules);
checkTrackedFiles();
checkExportIgnoreRules();

if (failures.length) {
  console.error("Repository hygiene checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("Repository hygiene rules are in place.");

function checkRequiredLines(relativeFile, requiredLines) {
  const file = path.join(root, relativeFile);

  if (!fs.existsSync(file)) {
    failures.push(`missing ${relativeFile}`);
    return;
  }

  const lines = new Set(
    fs
      .readFileSync(file, "utf8")
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter((line) => line && !line.startsWith("#")),
  );

  for (const rule of requiredLines) {
    if (!lines.has(rule)) {
      failures.push(`${relativeFile} is missing rule: ${rule}`);
    }
  }
}

function checkTrackedFiles() {
  const result = spawnSync("git", ["ls-files", "-z"], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status !== 0) {
    failures.push("could not inspect tracked files with git ls-files");
    return;
  }

  const trackedFiles = result.stdout.split("\0").filter(Boolean);

  for (const file of trackedFiles) {
    for (const [pattern, label] of forbiddenTrackedPatterns) {
      if (pattern.test(file)) {
        failures.push(`tracked ${label}: ${file}`);
      }
    }
  }
}

function checkExportIgnoreRules() {
  const samples = [
    ".github/workflows/ci.yml",
    ".wp-env/docker-compose.yml",
    ".wp-env.json",
    "build/monopage.zip",
    "node_modules/example/index.js",
    ".env",
    ".env.local",
    "monopage-backup.sql",
    "monopage.zip",
    "npm-debug.log",
  ];

  const result = spawnSync("git", ["check-attr", "export-ignore", "--", ...samples], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status !== 0) {
    failures.push("could not inspect export-ignore attributes with git check-attr");
    return;
  }

  for (const line of result.stdout.split(/\r?\n/).filter(Boolean)) {
    const [file, , value] = line.split(": ");

    if (value !== "set") {
      failures.push(`export-ignore is not set for ${file}`);
    }
  }
}
