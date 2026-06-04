import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const dryRun = process.argv.includes("--dry-run");

const artifacts = [
  {
    label: "plugin",
    cwd: path.join(root, "plugins"),
    source: "wpop",
    zip: path.join(root, "build", "wpop-0.1.0.zip"),
  },
  {
    label: "theme",
    cwd: path.join(root, "themes"),
    source: "wpop-canvas",
    zip: path.join(root, "build", "wpop-canvas-0.1.0.zip"),
  },
];

function fail(message) {
  console.error(message);
  process.exit(1);
}

for (const artifact of artifacts) {
  const sourcePath = path.join(artifact.cwd, artifact.source);
  if (!fs.existsSync(sourcePath)) {
    fail(`Missing ${artifact.label} source: ${sourcePath}`);
  }
}

if (dryRun) {
  for (const artifact of artifacts) {
    console.log(`[dry-run] zip ${artifact.source} -> ${path.relative(root, artifact.zip)}`);
  }
  process.exit(0);
}

fs.mkdirSync(path.join(root, "build"), { recursive: true });

for (const artifact of artifacts) {
  if (fs.existsSync(artifact.zip)) {
    fs.rmSync(artifact.zip);
  }

  const result = spawnSync(
    "zip",
    [
      "-qr",
      artifact.zip,
      artifact.source,
      "-x",
      "*/.DS_Store",
      "*/node_modules/*",
      "*/build/*",
    ],
    { cwd: artifact.cwd, stdio: "inherit" },
  );

  if (result.status !== 0) {
    fail(`Could not package ${artifact.label}. Is the zip command available?`);
  }

  console.log(`Created ${path.relative(root, artifact.zip)}`);
}
