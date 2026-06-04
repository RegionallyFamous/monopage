import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;

const steps = [
  {
    label: "repository checks",
    command: ["npm", "test"],
  },
  {
    label: "doctor",
    command: ["npm", "run", "doctor"],
  },
  {
    label: "local Monopage template refresh and validation",
    command: ["npm", "run", "local:refresh-template"],
    skip: Boolean(options["skip-local"]),
  },
  {
    label: "local homepage smoke",
    command: ["npm", "run", "local:smoke"],
    skip: Boolean(options["skip-local"]),
  },
  {
    label: "local admin smoke",
    command: ["npm", "run", "local:admin-smoke"],
    skip: Boolean(options["skip-local"]),
  },
  {
    label: "Plugin Check",
    command: ["npm", "run", "plugin:check"],
    skip: Boolean(options["skip-plugin-check"]),
  },
  {
    label: "package artifacts",
    command: ["npm", "run", "package"],
    skip: Boolean(options["skip-package"]),
  },
  {
    label: "package contents",
    command: ["npm", "run", "package:verify"],
    skip: Boolean(options["skip-package"]),
  },
  {
    label: "deploy plan safety",
    command: ["npm", "run", "check:deploy"],
  },
  {
    label: "deploy dry run",
    command: ["npm", "run", "deploy:dry-run"],
  },
  {
    label: "Playground URL",
    command: ["npm", "run", "playground:url"],
  },
];

for (const step of steps) {
  if (step.skip) {
    console.log(`SKIP ${step.label}`);
    continue;
  }

  runStep(step);
}

if (!dryRun && !options["skip-package"]) {
  assertArtifact(`build/monopage-${version}.zip`);
  assertArtifact(`build/monopage-canvas-${version}.zip`);
}

console.log("");
console.log(`Release check passed for Monopage ${version}.`);

function runStep(step) {
  const printable = step.command.map(shellQuote).join(" ");

  if (dryRun) {
    console.log(`[dry-run] ${printable}`);
    return;
  }

  console.log("");
  console.log(`== ${step.label} ==`);

  const result = spawnSync(step.command[0], step.command.slice(1), {
    cwd: root,
    stdio: "inherit",
  });

  if (result.status !== 0) {
    console.error(`Release check failed at ${step.label}: ${printable}`);
    process.exit(1);
  }
}

function assertArtifact(relativePath) {
  const artifact = path.join(root, relativePath);

  if (!fs.existsSync(artifact)) {
    console.error(`Missing release artifact: ${relativePath}`);
    process.exit(1);
  }

  const stats = fs.statSync(artifact);
  if (stats.size <= 0) {
    console.error(`Release artifact is empty: ${relativePath}`);
    process.exit(1);
  }

  console.log(`OK artifact: ${relativePath}`);
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

function shellQuote(value) {
  if (/^[A-Za-z0-9_./:@=-]+$/.test(String(value))) {
    return String(value);
  }

  return `'${String(value).replace(/'/g, "'\\''")}'`;
}
