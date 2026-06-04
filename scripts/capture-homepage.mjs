import { spawnSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const skipIfMissing = Boolean(options["skip-if-missing"]);
const isolatedProfile = Boolean(options["isolated-profile"]);
const baseUrl = normalizeBaseUrl(options.url || process.env.MONOPAGE_CAPTURE_URL || "http://localhost:8888");
const outputDir = path.resolve(root, options["output-dir"] || "build/screenshots");
const timeoutMs = parseTimeout(options.timeout || "15000");
const viewports = [
  {
    label: "desktop",
    ...parseViewport(options.desktop || "1440x1000", "desktop"),
  },
  {
    label: "mobile",
    ...parseViewport(options.mobile || "390x900", "mobile"),
  },
];

if (dryRun) {
  console.log(`[dry-run] Capture ${baseUrl}/ into ${path.relative(root, outputDir) || "."}`);
  for (const viewport of viewports) {
    console.log(`[dry-run] ${viewport.label}: ${viewport.width}x${viewport.height}`);
  }
  console.log(`[dry-run] timeout: ${timeoutMs}ms per viewport`);
  process.exit(0);
}

const browser = findBrowser();
if (!browser) {
  const message = "Could not find Chrome or Chromium. Set MONOPAGE_CHROME=/path/to/browser or install Chrome/Chromium.";
  if (skipIfMissing) {
    console.log(`SKIP homepage screenshots: ${message}`);
    process.exit(0);
  }

  console.error(message);
  process.exit(1);
}

fs.mkdirSync(outputDir, { recursive: true });

const captures = [];

for (const viewport of viewports) {
  captures.push(captureViewport(browser, viewport));
}

console.log(`Captured Monopage homepage screenshots from ${baseUrl}/:`);
for (const capture of captures) {
  console.log(`- ${capture.label}: ${capture.path} (${capture.width}x${capture.height}, ${formatBytes(capture.size)})`);
}

function captureViewport(browser, viewport) {
  const file = path.join(outputDir, `monopage-home-${viewport.label}.png`);
  const userDataDir = isolatedProfile ? fs.mkdtempSync(path.join(os.tmpdir(), "monopage-chrome-")) : "";
  const args = [
    "--headless",
    "--disable-gpu",
    "--hide-scrollbars",
    "--no-default-browser-check",
    "--no-first-run",
    "--run-all-compositor-stages-before-draw",
    `--window-size=${viewport.width},${viewport.height}`,
    `--screenshot=${file}`,
    `${baseUrl}/`,
  ];

  if (userDataDir) {
    args.splice(6, 0, `--user-data-dir=${userDataDir}`);
  }

  const result = spawnSync(
    browser,
    args,
    {
      cwd: root,
      encoding: "utf8",
      killSignal: "SIGKILL",
      timeout: timeoutMs,
    },
  );

  if (result.error && "ETIMEDOUT" === result.error.code) {
    if (userDataDir) {
      cleanupChromeProcesses(userDataDir);
      fs.rmSync(userDataDir, { recursive: true, force: true });
    }
    console.error(`Homepage screenshot timed out for ${viewport.label} after ${timeoutMs}ms.`);
    process.exit(1);
  }

  if (userDataDir) {
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }

  if (result.status !== 0) {
    if (result.stdout.trim()) {
      console.error(result.stdout.trim());
    }
    if (result.stderr.trim()) {
      console.error(result.stderr.trim());
    }
    console.error(`Homepage screenshot failed for ${viewport.label} at ${viewport.width}x${viewport.height}.`);
    process.exit(1);
  }

  assertPng(file, viewport.label);

  return {
    label: viewport.label,
    path: file,
    width: viewport.width,
    height: viewport.height,
    size: fs.statSync(file).size,
  };
}

function assertPng(file, label) {
  if (!fs.existsSync(file)) {
    console.error(`Chrome did not create the ${label} screenshot: ${file}`);
    process.exit(1);
  }

  const stats = fs.statSync(file);
  if (stats.size < 10 * 1024) {
    console.error(`The ${label} screenshot looks too small to be useful: ${file} (${formatBytes(stats.size)}).`);
    process.exit(1);
  }

  const header = fs.readFileSync(file).subarray(0, 8);
  const pngHeader = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
  if (!header.equals(pngHeader)) {
    console.error(`The ${label} screenshot is not a PNG: ${file}`);
    process.exit(1);
  }
}

function findBrowser() {
  const envPaths = [process.env.MONOPAGE_CHROME, process.env.CHROME_PATH, process.env.BROWSER_PATH].filter(Boolean);
  const appPaths = [
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/Applications/Chromium.app/Contents/MacOS/Chromium",
    path.join(os.homedir(), "Applications/Google Chrome.app/Contents/MacOS/Google Chrome"),
    path.join(os.homedir(), "Applications/Chromium.app/Contents/MacOS/Chromium"),
  ];
  const commands = [
    "google-chrome-stable",
    "google-chrome",
    "chromium",
    "chromium-browser",
    "chrome",
  ];

  for (const browserPath of [...envPaths, ...appPaths]) {
    if (isExecutable(browserPath)) {
      return browserPath;
    }
  }

  for (const command of commands) {
    const result = spawnSync("sh", ["-lc", `command -v ${shellQuote(command)}`], {
      encoding: "utf8",
    });

    if (result.status === 0 && result.stdout.trim()) {
      return result.stdout.trim().split(/\r?\n/)[0];
    }
  }

  return "";
}

function isExecutable(file) {
  try {
    fs.accessSync(file, fs.constants.X_OK);
    return true;
  } catch (error) {
    return false;
  }
}

function parseViewport(value, label) {
  const match = String(value).match(/^(\d+)x(\d+)$/);
  if (!match) {
    console.error(`Invalid ${label} viewport "${value}". Use WIDTHxHEIGHT, for example 390x900.`);
    process.exit(1);
  }

  const width = Number(match[1]);
  const height = Number(match[2]);
  if (width < 240 || height < 240) {
    console.error(`Invalid ${label} viewport "${value}". Width and height must be at least 240.`);
    process.exit(1);
  }

  return { width, height };
}

function parseTimeout(value) {
  const timeout = Number(value);
  if (!Number.isFinite(timeout) || timeout < 1000) {
    console.error(`Invalid timeout "${value}". Use milliseconds, minimum 1000.`);
    process.exit(1);
  }

  return timeout;
}

function cleanupChromeProcesses(userDataDir) {
  spawnSync("pkill", ["-f", userDataDir], {
    encoding: "utf8",
  });
}

function normalizeBaseUrl(value) {
  return String(value).replace(/\/+$/, "");
}

function formatBytes(bytes) {
  if (bytes < 1024 * 1024) {
    return `${Math.round(bytes / 1024)} KB`;
  }

  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
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
  return `'${String(value).replace(/'/g, "'\\''")}'`;
}
