import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const forceTemplate = Boolean(options["force-template"]);
const setupCommand = ["wp-env", "run", "cli", "wp", "monopage", "setup", "--force-home"];

if (forceTemplate) {
  setupCommand.push("--force-template");
}

const commands = [
  ["wp-env", "start"],
  ["wp-env", "run", "cli", "wp", "theme", "activate", "monopage-canvas"],
  ["wp-env", "run", "cli", "wp", "plugin", "activate", "monopage"],
  setupCommand,
  ["wp-env", "run", "cli", "wp", "monopage", "validate", "--require-focus"],
  ["wp-env", "run", "cli", "wp", "monopage", "status"],
];

for (const command of commands) {
  if (dryRun) {
    console.log(command.join(" "));
    continue;
  }

  const result = spawnSync(command[0], command.slice(1), {
    cwd: root,
    stdio: "inherit",
  });

  if (result.status !== 0) {
    console.error(`Command failed: ${command.join(" ")}`);
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
