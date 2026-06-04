import { spawnSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;
const pluginZip = `build/monopage-${version}.zip`;
const themeZip = `build/monopage-canvas-${version}.zip`;
const localUrls = {
  home: "http://localhost:8888/",
  admin: "http://localhost:8888/wp-admin/",
  editor: "http://localhost:8888/wp-admin/site-editor.php?p=/wp_template/monopage-canvas//front-page&canvas=edit",
};

console.log(`Monopage ${version}`);
console.log(`Repo: ${root}`);
console.log(`Branch: ${gitText(["branch", "--show-current"]) || "(detached)"}`);
console.log(`Commit: ${gitText(["log", "-1", "--oneline"]) || "(unknown)"}`);

console.log("");
console.log("Working Tree");
const changedFiles = changed();
if (changedFiles.length) {
  console.log(`${changedFiles.length} changed file${changedFiles.length === 1 ? "" : "s"}:`);
  for (const file of changedFiles) {
    console.log(`- ${file}`);
  }
} else {
  console.log("Clean.");
}

console.log("");
console.log("Focused Checks");
const changedCheck = runNode(["scripts/changed-checks.mjs"]);
console.log(indent(changedCheck.stdout.trim() || changedCheck.stderr.trim() || "No focused check output."));

console.log("");
console.log("Artifacts");
printArtifact(pluginZip);
printArtifact(themeZip);

console.log("");
console.log("Codex Skill");
printSkillStatus();

console.log("");
console.log("Local URLs");
const homeReachable = await isReachable(localUrls.home);
console.log(`- Home: ${localUrls.home} (${homeReachable ? "reachable" : "not reachable"})`);
console.log(`- Admin: ${localUrls.admin}`);
console.log(`- Site Editor: ${localUrls.editor}`);

console.log("");
console.log("Playground");
const playground = runNode(["scripts/playground-url.mjs"]);
console.log(`- ${playground.stdout.trim() || "Could not generate Playground URL."}`);

console.log("");
console.log("Next Commands");
if (changedFiles.length) {
  console.log("- npm run check:changed:run");
  console.log("- npm run release:check");
} else {
  console.log("- npm run local:review");
  console.log("- npm run release:check");
}

function changed() {
  const tracked = gitLines(["diff", "--name-only", "HEAD", "--"]);
  const untracked = gitLines(["ls-files", "--others", "--exclude-standard"]);
  return [...new Set([...tracked, ...untracked])].sort();
}

function printArtifact(relativePath) {
  const file = path.join(root, relativePath);
  if (!fs.existsSync(file)) {
    console.log(`- ${relativePath}: missing`);
    return;
  }

  const stats = fs.statSync(file);
  console.log(`- ${relativePath}: ${formatBytes(stats.size)}`);
}

function printSkillStatus() {
  const codexHome = process.env.CODEX_HOME || path.join(os.homedir(), ".codex");
  const target = path.join(codexHome, "skills", "monopage-deploy");
  const expected = path.join(root, "skills", "monopage-deploy");

  try {
    const stats = fs.lstatSync(target);
    if (!stats.isSymbolicLink()) {
      console.log(`- ${target}: exists but is not a symlink`);
      console.log("- Run: npm run skill:install -- --force");
      return;
    }

    const current = path.resolve(path.dirname(target), fs.readlinkSync(target));
    if (current === expected) {
      console.log(`- ${target}: installed`);
      return;
    }

    console.log(`- ${target}: points to ${current}`);
    console.log("- Run: npm run skill:install -- --force");
  } catch (error) {
    if (error.code === "ENOENT") {
      console.log(`- ${target}: missing`);
      console.log("- Run: npm run skill:install");
      return;
    }

    throw error;
  }
}

async function isReachable(url) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 1200);

  try {
    const response = await fetch(url, {
      method: "HEAD",
      signal: controller.signal,
    });
    return response.ok || response.status < 500;
  } catch (error) {
    return false;
  } finally {
    clearTimeout(timeout);
  }
}

function runNode(args) {
  return spawnSync("node", args, {
    cwd: root,
    encoding: "utf8",
  });
}

function gitText(args) {
  const result = spawnSync("git", args, {
    cwd: root,
    encoding: "utf8",
  });

  return result.status === 0 ? result.stdout.trim() : "";
}

function gitLines(args) {
  const text = gitText(args);
  return text ? text.split(/\r?\n/).filter(Boolean) : [];
}

function indent(text) {
  return text
    .split(/\r?\n/)
    .map((line) => `  ${line}`)
    .join("\n");
}

function formatBytes(bytes) {
  if (bytes < 1024 * 1024) {
    return `${Math.round(bytes / 1024)} KB`;
  }

  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
