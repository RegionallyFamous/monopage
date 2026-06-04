import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const useWpEnv = Boolean(options["wp-env"]) || !options.path;
const runtime = Boolean(options.runtime);
const skipInstall = Boolean(options["skip-install"]);
const wpPath = options.path || "";
const wpSsh = options.ssh || "";
const target = options.target || (useWpEnv ? "monopage/monopage.php" : path.join(root, "plugins/monopage"));
const requireArg = "--require=./wp-content/plugins/plugin-check/cli.php";

const commands = [];

if (!skipInstall) {
  commands.push([...wpCommand("plugin", "install", "plugin-check", "--activate")]);
}

commands.push([
  ...wpCommand("plugin", "check", target),
  ...(runtime ? [requireArg] : []),
]);

for (const command of commands) {
  if (dryRun) {
    console.log(command.map(shellQuote).join(" "));
    continue;
  }

  run(command[0], command.slice(1));
}

function wpCommand(...args) {
  if (useWpEnv) {
    return ["wp-env", "run", "cli", "wp", ...args];
  }

  const base = ["wp"];

  if (wpSsh) {
    base.push(`--ssh=${wpSsh}`);
  }

  if (wpPath) {
    base.push(`--path=${wpPath}`);
  }

  return [...base, ...args];
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

function run(command, args) {
  const result = spawnSync(command, args, {
    cwd: root,
    stdio: "inherit",
  });

  if (0 !== result.status) {
    console.error(`Command failed: ${[command, ...args].join(" ")}`);
    process.exit(1);
  }
}

function shellQuote(value) {
  if (/^[A-Za-z0-9_./:@=-]+$/.test(String(value))) {
    return String(value);
  }

  return `'${String(value).replace(/'/g, "'\\''")}'`;
}
