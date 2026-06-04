import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const preserveTemplate = Boolean(options["preserve-template"]);
const npm = "win32" === process.platform ? "npm.cmd" : "npm";
const steps = [
  {
    label: preserveTemplate ? "local setup" : "local template refresh",
    command: [npm, "run", preserveTemplate ? "local:setup" : "local:refresh-template"],
  },
  {
    label: "homepage smoke",
    command: [npm, "run", "local:smoke"],
  },
  {
    label: "admin smoke",
    command: [npm, "run", "local:admin-smoke"],
  },
  {
    label: "local status",
    command: [npm, "run", "local:status"],
  },
];

for (const step of steps) {
  runStep(step);
}

if (dryRun) {
  process.exit(0);
}

console.log("");
console.log("Local Monopage is ready:");
console.log("- Home: http://localhost:8888/");
console.log("- Admin: http://localhost:8888/wp-admin/");
console.log("- Site Editor: http://localhost:8888/wp-admin/site-editor.php?p=/wp_template/monopage-canvas//front-page&canvas=edit");

function runStep(step) {
  const printable = step.command.join(" ");

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

  if (0 !== result.status) {
    console.error(`Local readiness failed at ${step.label}: ${printable}`);
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

    if (-1 === equals) {
      parsed[body] = true;
    } else {
      parsed[body.slice(0, equals)] = body.slice(equals + 1);
    }
  }

  return parsed;
}
