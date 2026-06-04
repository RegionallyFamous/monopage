import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const requiredFiles = [
  "plugins/monopage/monopage.php",
  "plugins/monopage/assets/site-editor.js",
  "plugins/monopage/assets/admin.css",
  "themes/monopage-canvas/functions.php",
  "themes/monopage-canvas/style.css",
  "themes/monopage-canvas/templates/front-page.html",
  "themes/monopage-canvas/theme.json",
  "skills/monopage-deploy/SKILL.md",
  "playground/blueprint.json",
  "scripts/check-playground.mjs",
  "scripts/homepage-smoke.mjs",
  "scripts/plugin-check.mjs",
];
const commands = [
  { name: "node", required: true, note: "JavaScript tooling" },
  { name: "php", required: true, note: "PHP linting" },
  { name: "zip", required: true, note: "package builds" },
  { name: "docker", required: false, note: "wp-env and Plugin Check" },
  { name: "wp", required: false, note: "real-host deploys" },
  { name: "wp-env", required: false, note: "local WordPress smoke tests" },
];
const failures = [];

console.log(`Monopage ${manifest.version}`);
console.log(`Repo: ${root}`);
console.log("");

runCheckVersions();
runScriptCheck("canvas theme", "scripts/check-canvas-theme.mjs");
runScriptCheck("template links", "scripts/check-template-links.mjs");
runScriptCheck("Playground", "scripts/check-playground.mjs");
checkFiles();
checkCommands();
checkDockerDaemon();
checkSkillSymlink();

if (failures.length) {
  console.log("");
  console.error("Doctor found blocking issues:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("");
console.log("Doctor finished.");

function runCheckVersions() {
  const result = spawnSync("node", [path.join(root, "scripts/check-versions.mjs")], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status === 0) {
    console.log(`OK version metadata: ${result.stdout.trim()}`);
    return;
  }

  failures.push("version metadata is out of sync");
  if (result.stderr.trim()) {
    console.error(result.stderr.trim());
  }
}

function runScriptCheck(label, script) {
  const result = spawnSync("node", [path.join(root, script)], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status === 0) {
    console.log(`OK ${label}: ${result.stdout.trim()}`);
    return;
  }

  failures.push(`${label} check failed`);
  if (result.stderr.trim()) {
    console.error(result.stderr.trim());
  }
  if (result.stdout.trim()) {
    console.error(result.stdout.trim());
  }
}

function checkFiles() {
  for (const file of requiredFiles) {
    const fullPath = path.join(root, file);
    if (fs.existsSync(fullPath)) {
      console.log(`OK file: ${file}`);
    } else {
      failures.push(`missing file: ${file}`);
    }
  }
}

function checkCommands() {
  for (const command of commands) {
    const found = commandExists(command.name);
    if (found) {
      console.log(`OK command: ${command.name} (${command.note})`);
      continue;
    }

    const message = `missing command: ${command.name} (${command.note})`;
    if (command.required) {
      failures.push(message);
    } else {
      console.log(`WARN ${message}`);
    }
  }
}

function checkDockerDaemon() {
  if (!commandExists("docker")) {
    return;
  }

  const result = spawnSync("docker", ["info"], {
    encoding: "utf8",
  });

  if (result.status === 0) {
    console.log("OK docker daemon: running");
    return;
  }

  console.log("WARN docker daemon is not running; wp-env and Plugin Check cannot run yet.");
}

function checkSkillSymlink() {
  const codexHome = process.env.CODEX_HOME || path.join(process.env.HOME || "", ".codex");
  const target = path.join(codexHome, "skills", "monopage-deploy");
  const expected = path.join(root, "skills", "monopage-deploy");

  try {
    const stats = fs.lstatSync(target);
    if (!stats.isSymbolicLink()) {
      console.log(`WARN skill target exists but is not a symlink: ${target}`);
      return;
    }

    const current = path.resolve(path.dirname(target), fs.readlinkSync(target));
    if (current === expected) {
      console.log(`OK skill symlink: ${target}`);
    } else {
      console.log(`WARN skill symlink points to ${current}`);
    }
  } catch (error) {
    if ("ENOENT" === error.code) {
      console.log("WARN skill symlink not installed. Run npm run skill:install.");
      return;
    }

    throw error;
  }
}

function commandExists(command) {
  const result = spawnSync("sh", ["-lc", `command -v ${shellQuote(command)}`], {
    encoding: "utf8",
  });

  return result.status === 0;
}

function shellQuote(value) {
  return `'${String(value).replace(/'/g, "'\\''")}'`;
}
