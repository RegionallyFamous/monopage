import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const force = Boolean(options.force);
const codexHome = path.resolve(options["codex-home"] || process.env.CODEX_HOME || path.join(os.homedir(), ".codex"));
const skillsDir = path.resolve(options["skills-dir"] || path.join(codexHome, "skills"));
const source = path.join(root, "skills", "monopage-deploy");
const target = path.join(skillsDir, "monopage-deploy");

assertSkillSource(source);
installSkillLink(source, target);

if (dryRun) {
  console.log("Dry run complete. No files changed.");
} else {
  console.log(`Monopage skill installed: ${target}`);
}

function installSkillLink(sourcePath, targetPath) {
  ensureDirectory(skillsDir);

  const existing = lstatMaybe(targetPath);
  if (!existing) {
    createSymlink(sourcePath, targetPath);
    return;
  }

  if (!existing.isSymbolicLink()) {
    fail(`Skill target exists and is not a symlink: ${targetPath}`);
  }

  const current = resolveSymlink(targetPath);
  if (current === path.resolve(sourcePath)) {
    console.log(`Monopage skill already points to ${sourcePath}`);
    return;
  }

  if (!force) {
    fail(`Skill target points to ${current}. Re-run with --force to replace the symlink.`);
  }

  removeSymlink(targetPath);
  createSymlink(sourcePath, targetPath);
}

function ensureDirectory(directory) {
  if (fs.existsSync(directory)) {
    return;
  }

  if (dryRun) {
    console.log(`[dry-run] mkdir -p ${directory}`);
    return;
  }

  fs.mkdirSync(directory, { recursive: true });
}

function createSymlink(sourcePath, targetPath) {
  if (dryRun) {
    console.log(`[dry-run] ln -s ${sourcePath} ${targetPath}`);
    return;
  }

  fs.symlinkSync(sourcePath, targetPath, "dir");
}

function removeSymlink(targetPath) {
  if (dryRun) {
    console.log(`[dry-run] unlink ${targetPath}`);
    return;
  }

  fs.unlinkSync(targetPath);
}

function resolveSymlink(targetPath) {
  const link = fs.readlinkSync(targetPath);
  return path.resolve(path.dirname(targetPath), link);
}

function assertSkillSource(sourcePath) {
  if (!fs.existsSync(path.join(sourcePath, "SKILL.md"))) {
    fail(`Missing Monopage skill source: ${sourcePath}`);
  }
}

function lstatMaybe(targetPath) {
  try {
    return fs.lstatSync(targetPath);
  } catch (error) {
    if ("ENOENT" === error.code) {
      return null;
    }

    throw error;
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

function fail(message) {
  console.error(message);
  process.exit(1);
}
