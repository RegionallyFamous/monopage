import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;

const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const wpPath = options.path || "/path/to/wordpress";
const wpSsh = options.ssh || "";
const forceHome = Boolean(options["force-home"]);
const forceTemplate = Boolean(options["force-template"]);
const skipPackage = Boolean(options["skip-package"]);
const skipPackageVerify = Boolean(options["skip-package-verify"]);
const checkHttp = Boolean(options["check-http"]);
const pluginZip = path.resolve(root, options["plugin-zip"] || `build/monopage-${version}.zip`);
const themeZip = path.resolve(root, options["theme-zip"] || `build/monopage-canvas-${version}.zip`);
const backupName = `monopage-backup-${timestamp()}.sql`;
const backupFile = options["backup-file"] || `${wpPath.replace(/\/$/, "")}/${backupName}`;

if (!dryRun && (!options.path || wpPath === "/path/to/wordpress")) {
  fail("Missing --path=/path/to/wordpress for a real deployment.");
}

const packageCommand = ["node", path.join(root, "scripts/package.mjs")];
const packageVerifyCommand = [
  "node",
  path.join(root, "scripts/check-package-contents.mjs"),
  `--plugin-zip=${pluginZip}`,
  `--theme-zip=${themeZip}`,
];
const preflightCommands = [
  ...(!skipPackage ? [packageCommand] : []),
  ...(!skipPackageVerify ? [packageVerifyCommand] : []),
];

const wpBase = ["wp"];
if (wpSsh) {
  wpBase.push(`--ssh=${wpSsh}`);
}
if (wpPath) {
  wpBase.push(`--path=${wpPath}`);
}

const commands = [
  [...wpBase, "core", "is-installed"],
  [...wpBase, "db", "export", backupFile],
  [...wpBase, "theme", "install", themeZip, "--force", "--activate"],
  [...wpBase, "plugin", "install", pluginZip, "--force", "--activate"],
  [
    ...wpBase,
    "monopage",
    "setup",
    ...(forceHome ? ["--force-home"] : []),
    ...(forceTemplate ? ["--force-template"] : []),
  ],
  [...wpBase, "monopage", "validate", "--require-focus", ...(checkHttp ? ["--check-http"] : [])],
  [...wpBase, "monopage", "status", "--format=json"],
];

for (const command of preflightCommands) {
  if (dryRun) {
    console.log(command.map(shellQuote).join(" "));
  } else {
    run(command[0], command.slice(1), { cwd: root });
  }
}

if (!dryRun) {
  assertFile(pluginZip, "plugin ZIP");
  assertFile(themeZip, "theme ZIP");
}

for (const command of commands) {
  if (dryRun) {
    console.log(command.map(shellQuote).join(" "));
  } else {
    run(command[0], command.slice(1), { cwd: root });
  }
}

if (!dryRun) {
  console.log("Monopage deployment complete.");
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

function run(command, args, config = {}) {
  const result = spawnSync(command, args, {
    stdio: "inherit",
    ...config,
  });

  if (result.status !== 0) {
    fail(`Command failed: ${[command, ...args].join(" ")}`);
  }
}

function assertFile(filePath, label) {
  if (!fs.existsSync(filePath)) {
    fail(`Missing ${label}: ${filePath}`);
  }
}

function shellQuote(value) {
  if (/^[A-Za-z0-9_./:@=-]+$/.test(String(value))) {
    return String(value);
  }

  return `'${String(value).replace(/'/g, "'\\''")}'`;
}

function timestamp() {
  return new Date().toISOString().replace(/[-:]/g, "").replace(/\..+$/, "Z");
}

function fail(message) {
  console.error(message);
  process.exit(1);
}
