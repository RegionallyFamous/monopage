import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const dryRun = process.argv.includes("--dry-run");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;

runCheckVersions();

const artifacts = [
  {
    label: "plugin",
    cwd: path.join(root, "plugins"),
    source: "monopage",
    zip: path.join(root, "build", `monopage-${version}.zip`),
  },
  {
    label: "theme",
    cwd: path.join(root, "themes"),
    source: "monopage-canvas",
    zip: path.join(root, "build", `monopage-canvas-${version}.zip`),
  },
];

function fail(message) {
  console.error(message);
  process.exit(1);
}

function runCheckVersions() {
  const result = spawnSync("node", [path.join(root, "scripts/check-versions.mjs")], {
    cwd: root,
    stdio: "inherit",
  });

  if (result.status !== 0) {
    fail("Version metadata must be in sync before packaging.");
  }
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
