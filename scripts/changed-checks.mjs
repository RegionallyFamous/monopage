import { spawnSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const runCommands = Boolean(options.run);
const base = options.base || "HEAD";
const npm = "win32" === process.platform ? "npm.cmd" : "npm";
const files = options.files ? splitFiles(options.files) : getChangedFiles(base);
const recommendations = [];
const metadataOnlyCache = new Map();

if (!files.length) {
  console.log("No changed files found.");
  console.log("Use --files=path/to/file,path/to/other-file to preview recommendations for a planned change.");
  process.exit(0);
}

addRules();

if (!recommendations.length) {
  console.log("Changed files:");
  for (const file of files) {
    console.log(`- ${file}`);
  }
  console.log("");
  console.log("No focused checks matched. Run npm test before packaging or pushing.");
  process.exit(0);
}

console.log("Changed files:");
for (const file of files) {
  console.log(`- ${file}`);
}

console.log("");
console.log("Recommended checks:");
for (const recommendation of recommendations) {
  console.log(`- ${recommendation.command.join(" ")} (${recommendation.reason})`);
}

if (!runCommands) {
  console.log("");
  console.log(`Run them with: ${runCommandHint()}`);
  process.exit(0);
}

for (const recommendation of recommendations) {
  runStep(recommendation);
}

function addRules() {
  const has = (test) => files.some(test);

  if (has((file) => /\.(js|mjs)$/.test(file) || file === "package.json")) {
    add("npm run check:js", "JavaScript changed");
  }

  if (has((file) => file === "package.json")) {
    add("npm run check:scripts", "package scripts changed");
  }

  if (has((file) => /\.(json)$/.test(file) || file === "package-lock.json")) {
    add("npm run check:json", "JSON changed");
  }

  if (has((file) => file === "package.json" || file === "package-lock.json" || isVersionedFile(file))) {
    add("npm run check:versions", "version metadata may be affected");
  }

  if (has(isPhpFile)) {
    add("npm run lint:php", "PHP changed");
  }

  if (has(isCanvasFileNeedingThemeChecks)) {
    add("npm run check:canvas", "Canvas theme styling, assets, or patterns changed");
  }

  if (has(isTemplateOrPatternFile)) {
    add("npm run check:links", "template or pattern links may have changed");
  }

  if (has(isVisualCanvasFile)) {
    add("npm run local:review", "visual Canvas changes need runtime screenshots");
  }

  if (has(isAdminOrPluginRuntimeFile)) {
    add("npm run local:ready", "plugin or admin runtime behavior changed");
  }

  if (has(isPluginPhpFile)) {
    add("npm run plugin:check", "plugin PHP changed");
  }

  if (has(isDeployFile)) {
    add("npm run check:deploy", "deploy workflow changed");
  }

  if (has(isPlaygroundFile)) {
    add("npm run check:playground", "Playground wiring changed");
  }

  if (has(isDocumentationFile)) {
    add("npm run check:docs", "documentation command references may have changed");
  }

  if (has(isSkillFile)) {
    add("npm run check:skill", "Codex skill contract changed");
  }

  if (has(isHygieneFile)) {
    add("npm run check:hygiene", "repository hygiene rules changed");
  }

  if (has(isPackagingFile)) {
    add("npm run package:dry-run", "packaging inputs changed");
  }
}

function add(commandText, reason) {
  if (recommendations.some((recommendation) => recommendation.commandText === commandText)) {
    return;
  }

  recommendations.push({
    commandText,
    command: commandText.split(" "),
    reason,
  });
}

function runStep(recommendation) {
  console.log("");
  console.log(`== ${recommendation.commandText} ==`);

  const command = recommendation.command;
  const result = spawnSync(command[0], command.slice(1), {
    cwd: root,
    stdio: "inherit",
  });

  if (result.status !== 0) {
    console.error(`Changed-file check failed: ${recommendation.commandText}`);
    process.exit(1);
  }
}

function runCommandHint() {
  const args = ["--run"];

  if (options.files) {
    args.push(`--files=${options.files}`);
  }

  if (options.base && options.base !== "HEAD") {
    args.push(`--base=${options.base}`);
  }

  if (args.length === 1) {
    return "npm run check:changed:run";
  }

  return `npm run check:changed -- ${args.map(shellQuote).join(" ")}`;
}

function getChangedFiles(ref) {
  const tracked = git(["diff", "--name-only", ref, "--"]);
  const untracked = git(["ls-files", "--others", "--exclude-standard"]);

  return unique([...tracked, ...untracked]);
}

function git(args) {
  const result = spawnSync("git", args, {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status !== 0) {
    if (result.stderr.trim()) {
      console.error(result.stderr.trim());
    }
    console.error(`Could not inspect changed files with git ${args.join(" ")}.`);
    process.exit(1);
  }

  return result.stdout.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
}

function isPhpFile(file) {
  return file.endsWith(".php");
}

function isPluginPhpFile(file) {
  return file === "plugins/monopage/monopage.php" && !isMetadataOnlyVersionChange(file);
}

function isCanvasFile(file) {
  return file.startsWith("themes/monopage-canvas/");
}

function isCanvasFileNeedingThemeChecks(file) {
  return isCanvasFile(file) && !isMetadataOnlyVersionChange(file);
}

function isTemplateOrPatternFile(file) {
  return file === "themes/monopage-canvas/templates/front-page.html" || /^themes\/monopage-canvas\/patterns\/.+\.php$/.test(file);
}

function isVisualCanvasFile(file) {
  if (isMetadataOnlyVersionChange(file)) {
    return false;
  }

  return (
    file === "themes/monopage-canvas/style.css" ||
    file === "themes/monopage-canvas/theme.json" ||
    file === "themes/monopage-canvas/templates/front-page.html" ||
    file.startsWith("themes/monopage-canvas/assets/")
  );
}

function isAdminOrPluginRuntimeFile(file) {
  return (file === "plugins/monopage/monopage.php" && !isMetadataOnlyVersionChange(file)) || file.startsWith("plugins/monopage/assets/");
}

function isDeployFile(file) {
  return file === "scripts/deploy-monopage.mjs" || file === "scripts/check-deploy-plan.mjs";
}

function isPlaygroundFile(file) {
  return file.startsWith("playground/") || file === "scripts/playground-url.mjs" || file === "scripts/check-playground.mjs";
}

function isDocumentationFile(file) {
  return file === "README.md" || file.startsWith("docs/") || file === "skills/monopage-deploy/SKILL.md";
}

function isSkillFile(file) {
  return file.startsWith("skills/monopage-deploy/") || file === "scripts/check-skill.mjs";
}

function isHygieneFile(file) {
  return file === ".gitignore" || file === ".gitattributes" || file === "scripts/check-repo-hygiene.mjs";
}

function isPackagingFile(file) {
  return file === "scripts/package.mjs" || file === "scripts/check-package-contents.mjs" || isCanvasFile(file) || file.startsWith("plugins/monopage/");
}

function isVersionedFile(file) {
  return (
    file === "plugins/monopage/monopage.php" ||
    file === "plugins/monopage/readme.txt" ||
    file === "themes/monopage-canvas/style.css" ||
    file === "skills/monopage-deploy/SKILL.md"
  );
}

function isMetadataOnlyVersionChange(file) {
  if (options.files) {
    return false;
  }

  if (!metadataOnlyCache.has(file)) {
    metadataOnlyCache.set(file, inspectMetadataOnlyVersionChange(file));
  }

  return metadataOnlyCache.get(file);
}

function inspectMetadataOnlyVersionChange(file) {
  const allowedPatterns = metadataOnlyVersionPatterns(file);
  if (!allowedPatterns.length) {
    return false;
  }

  const changedLines = diffChangedLines(file);
  if (!changedLines.length) {
    return false;
  }

  return changedLines.every((line) => allowedPatterns.some((pattern) => pattern.test(line)));
}

function metadataOnlyVersionPatterns(file) {
  if (file === "themes/monopage-canvas/style.css") {
    return [/^Version:\s*[0-9]+\.[0-9]+\.[0-9]+$/];
  }

  if (file === "plugins/monopage/monopage.php") {
    return [
      /^ \* Version:\s*[0-9]+\.[0-9]+\.[0-9]+$/,
      /^define\(\s*'MONOPAGE_VERSION',\s*'[0-9]+\.[0-9]+\.[0-9]+'\s*\);$/,
    ];
  }

  return [];
}

function diffChangedLines(file) {
  const result = spawnSync("git", ["diff", "--unified=0", base, "--", file], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status !== 0) {
    return [];
  }

  return result.stdout
    .split(/\r?\n/)
    .filter((line) => /^[+-]/.test(line) && !line.startsWith("+++") && !line.startsWith("---"))
    .map((line) => line.slice(1));
}

function splitFiles(value) {
  return unique(
    String(value)
      .split(",")
      .map((file) => file.trim())
      .filter(Boolean),
  );
}

function unique(values) {
  return [...new Set(values)].sort();
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
  if (/^[A-Za-z0-9_./:@=,-]+$/.test(String(value))) {
    return String(value);
  }

  return `'${String(value).replace(/'/g, "'\\''")}'`;
}
