import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const version = manifest.version;
const failures = [];

const defaultPlan = getPlan(["--dry-run"]);

assertExactPlan(defaultPlan, [
  {
    label: "package build",
    pattern: /^node .*\/scripts\/package\.mjs$/,
  },
  {
    label: "package content verification",
    pattern: new RegExp(
      `^node .*/scripts/check-package-contents\\.mjs --plugin-zip=.*/build/monopage-${escapeRegExp(version)}\\.zip --theme-zip=.*/build/monopage-canvas-${escapeRegExp(version)}\\.zip$`
    ),
  },
  {
    label: "WordPress install check",
    pattern: /^wp --path=\/path\/to\/wordpress core is-installed$/,
  },
  {
    label: "database backup",
    pattern: /^wp --path=\/path\/to\/wordpress db export \/path\/to\/wordpress\/monopage-backup-\d{8}T\d{6}Z\.sql$/,
  },
  {
    label: "theme install before plugin install",
    pattern: new RegExp(`^wp --path=/path/to/wordpress theme install .*/build/monopage-canvas-${escapeRegExp(version)}\\.zip --force --activate$`),
  },
  {
    label: "plugin install after theme install",
    pattern: new RegExp(`^wp --path=/path/to/wordpress plugin install .*/build/monopage-${escapeRegExp(version)}\\.zip --force --activate$`),
  },
  {
    label: "safe setup",
    pattern: /^wp --path=\/path\/to\/wordpress monopage setup$/,
  },
  {
    label: "required validation",
    pattern: /^wp --path=\/path\/to\/wordpress monopage validate --require-focus$/,
  },
  {
    label: "status report",
    pattern: /^wp --path=\/path\/to\/wordpress monopage status --format=json$/,
  },
]);

assertNoDefaultForceFlags(defaultPlan);

const optInPlan = getPlan(["--dry-run", "--skip-package", "--skip-package-verify", "--force-home", "--force-template", "--check-http"]);
assertIncludes(optInPlan, /^wp --path=\/path\/to\/wordpress monopage setup --force-home --force-template$/, "explicit force flags");
assertIncludes(optInPlan, /^wp --path=\/path\/to\/wordpress monopage validate --require-focus --check-http$/, "explicit HTTP validation");
assertAbsent(optInPlan, /scripts\/package\.mjs/, "package build when --skip-package is used");
assertAbsent(optInPlan, /scripts\/check-package-contents\.mjs/, "package verification when --skip-package-verify is used");

if (failures.length) {
  console.error("Deploy plan checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Deploy dry-run plan preserves Monopage ${version} safety order.`);

function getPlan(extraArgs) {
  const result = spawnSync("node", [path.join(root, "scripts/deploy-monopage.mjs"), ...extraArgs], {
    cwd: root,
    encoding: "utf8",
  });

  if (result.status !== 0) {
    failures.push(`deploy dry-run failed: ${result.stderr.trim() || result.stdout.trim() || `exit ${result.status}`}`);
    return [];
  }

  return result.stdout
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}

function assertExactPlan(lines, expected) {
  if (lines.length !== expected.length) {
    failures.push(`default deploy plan has ${lines.length} commands; expected ${expected.length}`);
  }

  for (const [index, expectation] of expected.entries()) {
    const line = lines[index] || "";
    if (!expectation.pattern.test(line)) {
      failures.push(`command ${index + 1} should be ${expectation.label}; got: ${line || "(missing)"}`);
    }
  }
}

function assertNoDefaultForceFlags(lines) {
  const setupLine = lines.find((line) => /\bmonopage setup\b/.test(line));
  const validateLine = lines.find((line) => /\bmonopage validate\b/.test(line));

  if (!setupLine) {
    failures.push("default deploy plan is missing setup command");
  } else if (/--force-home|--force-template/.test(setupLine)) {
    failures.push(`default setup command must not force saved site choices: ${setupLine}`);
  }

  if (!validateLine) {
    failures.push("default deploy plan is missing validation command");
  } else if (/--check-http/.test(validateLine)) {
    failures.push(`default validation command must not require HTTP reachability: ${validateLine}`);
  }
}

function assertIncludes(lines, pattern, label) {
  if (!lines.some((line) => pattern.test(line))) {
    failures.push(`missing ${label} in opt-in deploy plan`);
  }
}

function assertAbsent(lines, pattern, label) {
  if (lines.some((line) => pattern.test(line))) {
    failures.push(`unexpected ${label} in opt-in deploy plan`);
  }
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
