import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const npm = "win32" === process.platform ? "npm.cmd" : "npm";
const localReadyArgs = ["run", "local:ready", "--", "--capture"];

if (options["preserve-template"]) {
  localReadyArgs.push("--preserve-template");
}

const steps = [
  {
    label: "local visual readiness",
    command: [npm, ...localReadyArgs],
  },
  {
    label: "responsive smoke",
    command: [npm, "run", "local:responsive"],
  },
  {
    label: "Playground URL",
    command: [npm, "run", "playground:url"],
  },
];

for (const step of steps) {
  runStep(step);
}

if (!dryRun) {
  console.log("");
  console.log("Visual review is ready:");
  console.log("- Desktop screenshot: build/screenshots/monopage-home-desktop.png");
  console.log("- Mobile screenshot: build/screenshots/monopage-home-mobile.png");
  console.log("- Responsive smoke: passed");
}

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
    console.error(`Local review failed at ${step.label}: ${printable}`);
    process.exit(1);
  }
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
