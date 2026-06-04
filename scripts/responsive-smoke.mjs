import { spawn, spawnSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const skipIfMissing = Boolean(options["skip-if-missing"]);
const baseUrl = normalizeBaseUrl(options.url || process.env.MONOPAGE_RESPONSIVE_URL || "http://localhost:8888");
const timeoutMs = parseTimeout(options.timeout || "15000");
const viewports = [
  parseViewport(options.desktop || "1440x1000", "desktop"),
  parseViewport(options.tablet || "768x1024", "tablet"),
  parseViewport(options.mobile || "390x900", "mobile"),
];

async function inspectViewport(browser, viewport) {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), "monopage-responsive-"));
  const chrome = spawn(
    browser,
    [
      "--headless",
      "--disable-gpu",
      "--hide-scrollbars",
      "--no-default-browser-check",
      "--no-first-run",
      "--run-all-compositor-stages-before-draw",
      "--remote-debugging-port=0",
      `--user-data-dir=${userDataDir}`,
      `--window-size=${viewport.width},${viewport.height}`,
      `${baseUrl}/`,
    ],
    {
      cwd: root,
      stdio: ["ignore", "pipe", "pipe"],
    },
  );

  let page;

  try {
    const browserWsUrl = await waitForDevTools(chrome, timeoutMs);
    const pageWsUrl = await waitForPageWebSocket(browserWsUrl, timeoutMs);
    page = await CdpClient.connect(pageWsUrl, timeoutMs);

    await page.send("Page.enable");
    await page.send("Emulation.setDeviceMetricsOverride", {
      width: viewport.width,
      height: viewport.height,
      deviceScaleFactor: 1,
      mobile: viewport.width <= 600,
    });
    await page.send("Page.navigate", { url: `${baseUrl}/` });

    await waitForReady(page, timeoutMs);
    await settleLayout(page);

    const response = await evaluateWithRetry(page, {
      expression: `(${inspectResponsivePage.toString()})()`,
      returnByValue: true,
      awaitPromise: true,
    });

    if (response.exceptionDetails) {
      throw new Error(formatException(response.exceptionDetails));
    }

    const value = response.result?.value;
    if (!value || !Array.isArray(value.failures)) {
      throw new Error(`Responsive inspector returned an unexpected result for ${viewport.label}.`);
    }

    return {
      ...viewport,
      ...value,
    };
  } finally {
    if (page) {
      page.close();
    }

    if (!chrome.killed) {
      chrome.kill("SIGKILL");
    }

    cleanupChromeProcesses(userDataDir);
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
}

async function waitForReady(page, timeout) {
  const started = Date.now();

  while (Date.now() - started < timeout) {
    const response = await evaluateWithRetry(page, {
      expression: "document.readyState",
      returnByValue: true,
    });

    if ("complete" === response.result?.value) {
      return;
    }

    await delay(100);
  }

  throw new Error("Timed out waiting for the homepage to finish loading.");
}

async function settleLayout(page) {
  await evaluateWithRetry(page, {
    expression: "new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))",
    awaitPromise: true,
  });
}

async function evaluateWithRetry(page, params) {
  let lastError;

  for (let attempt = 0; attempt < 8; attempt++) {
    try {
      return await page.send("Runtime.evaluate", params);
    } catch (error) {
      if (!isNavigationRace(error)) {
        throw error;
      }

      lastError = error;
      await delay(150);
    }
  }

  throw lastError;
}

function inspectResponsivePage() {
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const documentWidth = Math.max(
    document.documentElement.scrollWidth,
    document.body ? document.body.scrollWidth : 0,
  );
  const failures = [];
  const overflow = documentWidth - viewportWidth;

  if (overflow > 3) {
    failures.push(`document is ${Math.round(overflow)}px wider than the viewport`);
  }

  const header = requireVisible(".monopage-site-header", "site header", { inViewportX: true });
  const title = requireVisible(".wp-block-site-title", "site title", { inViewportX: true });
  const nav = requireVisible(".monopage-site-header .wp-block-navigation", "header navigation", { inViewportX: true });
  const firstNavItem = requireVisible(".monopage-site-header .wp-block-navigation-item__content", "first navigation item", { inViewportX: true });
  const hero = requireVisible(".monopage-hero", "hero", { inViewportX: true });
  const heroHeading = requireVisible(".monopage-hero h1", "hero heading", { inViewportX: true });
  const heroCopy = requireVisible(".monopage-hero-copy > p:not(.monopage-kicker)", "hero copy", { inViewportX: true });
  const heroButtons = requireAllVisible(".monopage-hero .wp-block-button__link", "hero button", { inViewportX: true });
  const heroFacts = requireAllVisible(".monopage-mini-value", "hero fact", { inViewportX: true });
  const logoPills = requireAllVisible(".monopage-logo-pill", "logo strip pill", { inViewportX: true });
  const nextSection = document.querySelector(".monopage-logo-strip");

  if (heroHeading && !normalizeText(heroHeading.textContent).includes("One page. All signal.")) {
    failures.push("hero heading text is not the expected starter headline");
  }

  if (viewportWidth <= 782 && nav) {
    const navStyle = window.getComputedStyle(nav);
    const navContainer = nav.querySelector(".wp-block-navigation__container");
    const navOverflows = navContainer && navContainer.scrollWidth > nav.clientWidth + 2;
    if (navOverflows && !/(auto|scroll)/.test(navStyle.overflowX)) {
      failures.push("mobile header navigation overflows without horizontal scrolling");
    }
  }

  const overlayButton = document.querySelector(".wp-block-navigation__responsive-container-open");
  if (overlayButton && isVisible(overlayButton)) {
    failures.push("navigation overlay button is visible; Monopage mobile nav should remain as same-page links");
  }

  if (viewportWidth <= 600 && header && Math.round(header.getBoundingClientRect().height) > 140) {
    failures.push("mobile header is taller than 140px");
  }

  if (nextSection) {
    const top = Math.round(nextSection.getBoundingClientRect().top);
    if (top > viewportHeight - 32) {
      failures.push("first viewport does not show a hint of the next section below the hero");
    }
  } else {
    failures.push("missing logo strip after hero");
  }

  return {
    failures,
    viewportWidth,
    viewportHeight,
    documentWidth: Math.round(documentWidth),
    headerHeight: header ? Math.round(header.getBoundingClientRect().height) : 0,
    heroHeight: hero ? Math.round(hero.getBoundingClientRect().height) : 0,
    checkedElements: [
      title,
      nav,
      firstNavItem,
      hero,
      heroHeading,
      heroCopy,
      ...heroButtons,
      ...heroFacts,
      ...logoPills,
    ].filter(Boolean).length,
  };

  function requireAllVisible(selector, label, config = {}) {
    const elements = [...document.querySelectorAll(selector)];
    if (!elements.length) {
      failures.push(`missing ${label}: ${selector}`);
      return [];
    }

    elements.forEach((element, index) => assertReadable(element, `${label} ${index + 1}`, config));
    return elements;
  }

  function requireVisible(selector, label, config = {}) {
    const element = document.querySelector(selector);
    if (!element) {
      failures.push(`missing ${label}: ${selector}`);
      return null;
    }

    assertReadable(element, label, config);
    return element;
  }

  function assertReadable(element, label, config = {}) {
    const rect = element.getBoundingClientRect();

    if (!isVisible(element)) {
      failures.push(`${label} is not visible`);
      return;
    }

    if (rect.width < 1 || rect.height < 1) {
      failures.push(`${label} has no rendered size`);
    }

    if (config.inViewportX && (rect.left < -1 || rect.right > viewportWidth + 1)) {
      failures.push(`${label} extends outside the viewport horizontally`);
    }

    if (element.scrollWidth > element.clientWidth + 2) {
      failures.push(`${label} content is clipped horizontally`);
    }

    const clipping = clippedByAncestor(element);
    if (clipping) {
      failures.push(`${label} is clipped by ${clipping}`);
    }
  }

  function clippedByAncestor(element) {
    const rect = element.getBoundingClientRect();
    let ancestor = element.parentElement;

    while (ancestor && ancestor !== document.documentElement) {
      const style = window.getComputedStyle(ancestor);
      if (/(hidden|clip|auto|scroll)/.test(style.overflowX)) {
        const ancestorRect = ancestor.getBoundingClientRect();
        if (rect.left < ancestorRect.left - 1 || rect.right > ancestorRect.right + 1) {
          return ancestor.className || ancestor.tagName.toLowerCase();
        }
      }

      ancestor = ancestor.parentElement;
    }

    return "";
  }

  function isVisible(element) {
    const style = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();

    return style.display !== "none"
      && style.visibility !== "hidden"
      && Number(style.opacity) !== 0
      && rect.width > 0
      && rect.height > 0;
  }

  function normalizeText(value) {
    return String(value).replace(/\s+/g, " ").trim();
  }
}

function waitForDevTools(chrome, timeout) {
  return new Promise((resolve, reject) => {
    let output = "";
    const timer = setTimeout(() => {
      reject(new Error(`Timed out waiting for Chrome DevTools after ${timeout}ms.\n${output.trim()}`));
    }, timeout);

    const onData = (chunk) => {
      output += chunk.toString();
      const match = output.match(/DevTools listening on (ws:\/\/[^\s]+)/);
      if (match) {
        clearTimeout(timer);
        resolve(match[1]);
      }
    };

    chrome.stdout.on("data", onData);
    chrome.stderr.on("data", onData);
    chrome.on("error", (error) => {
      clearTimeout(timer);
      reject(error);
    });
    chrome.on("exit", (code) => {
      clearTimeout(timer);
      if (code !== null && !/DevTools listening on ws:\/\//.test(output)) {
        reject(new Error(`Chrome exited before DevTools was ready with code ${code}.\n${output.trim()}`));
      }
    });
  });
}

async function waitForPageWebSocket(browserWsUrl, timeout) {
  const endpoint = new URL(browserWsUrl);
  const targetsUrl = `http://${endpoint.host}/json`;
  const started = Date.now();

  while (Date.now() - started < timeout) {
    const response = await fetch(targetsUrl).catch(() => null);
    if (response && response.ok) {
      const targets = await response.json();
      const page = targets.find((target) => "page" === target.type && target.webSocketDebuggerUrl);
      if (page) {
        return page.webSocketDebuggerUrl;
      }
    }

    await delay(100);
  }

  throw new Error("Timed out waiting for a Chrome page target.");
}

class CdpClient {
  static connect(url, timeout) {
    return new Promise((resolve, reject) => {
      const socket = new WebSocket(url);
      const client = new CdpClient(socket);
      const timer = setTimeout(() => {
        reject(new Error(`Timed out connecting to Chrome target after ${timeout}ms.`));
      }, timeout);

      socket.addEventListener("open", () => {
        clearTimeout(timer);
        resolve(client);
      }, { once: true });

      socket.addEventListener("error", () => {
        clearTimeout(timer);
        reject(new Error("Chrome target WebSocket connection failed."));
      }, { once: true });
    });
  }

  constructor(socket) {
    this.socket = socket;
    this.nextId = 1;
    this.pending = new Map();

    socket.addEventListener("message", (event) => this.handleMessage(event));
    socket.addEventListener("close", () => this.rejectPending(new Error("Chrome target WebSocket closed.")));
  }

  send(method, params = {}) {
    const id = this.nextId++;
    const message = JSON.stringify({ id, method, params });

    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.socket.send(message);
    });
  }

  close() {
    this.socket.close();
  }

  handleMessage(event) {
    const data = JSON.parse(String(event.data));
    if (!data.id || !this.pending.has(data.id)) {
      return;
    }

    const pending = this.pending.get(data.id);
    this.pending.delete(data.id);

    if (data.error) {
      pending.reject(new Error(`${data.error.message || "CDP error"} (${data.error.code || "unknown"})`));
    } else {
      pending.resolve(data.result || {});
    }
  }

  rejectPending(error) {
    for (const pending of this.pending.values()) {
      pending.reject(error);
    }

    this.pending.clear();
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

  return { label, width, height };
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

function formatException(exceptionDetails) {
  return exceptionDetails.text || exceptionDetails.exception?.description || "Responsive inspector failed in the browser.";
}

function isNavigationRace(error) {
  return /Execution context was destroyed|Cannot find context|Inspected target navigated or closed/i.test(error.message || "");
}

function normalizeBaseUrl(value) {
  return String(value).replace(/\/+$/, "");
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
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

async function main() {
  if (dryRun) {
    console.log(`[dry-run] Check responsive layout at ${baseUrl}/`);
    for (const viewport of viewports) {
      console.log(`[dry-run] ${viewport.label}: ${viewport.width}x${viewport.height}`);
    }
    console.log("[dry-run] Confirm no horizontal page overflow, clipped hero buttons, or cropped logo-strip pills.");
    return;
  }

  const browser = findBrowser();
  if (!browser) {
    const message = "Could not find Chrome or Chromium. Set MONOPAGE_CHROME=/path/to/browser or install Chrome/Chromium.";
    if (skipIfMissing) {
      console.log(`SKIP responsive smoke: ${message}`);
      return;
    }

    console.error(message);
    process.exit(1);
  }

  const results = [];
  const failures = [];

  for (const viewport of viewports) {
    const result = await inspectViewport(browser, viewport);
    results.push(result);

    for (const failure of result.failures) {
      failures.push(`${viewport.label}: ${failure}`);
    }
  }

  if (failures.length) {
    console.error(`Monopage responsive smoke failed for ${baseUrl}/:`);
    for (const failure of failures) {
      console.error(`- ${failure}`);
    }
    process.exit(1);
  }

  console.log(`Responsive smoke passed for ${baseUrl}/.`);
  for (const result of results) {
    console.log(
      `- ${result.label}: ${result.width}x${result.height}, document ${result.documentWidth}px wide, header ${result.headerHeight}px, hero ${result.heroHeight}px`,
    );
  }
}

await main();
