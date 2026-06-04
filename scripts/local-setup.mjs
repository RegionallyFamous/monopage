import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const dryRun = process.argv.includes("--dry-run");

const commands = [
  ["wp-env", "start"],
  ["wp-env", "run", "cli", "wp", "theme", "activate", "monopage-canvas"],
  ["wp-env", "run", "cli", "wp", "plugin", "activate", "monopage"],
  ["wp-env", "run", "cli", "wp", "monopage", "setup", "--force-home"],
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
