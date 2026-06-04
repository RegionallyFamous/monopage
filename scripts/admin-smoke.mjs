import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const options = parseArgs(process.argv.slice(2));
const dryRun = Boolean(options["dry-run"]);
const baseUrl = normalizeBaseUrl(options.url || process.env.MONOPAGE_SMOKE_URL || "http://localhost:8888");
const username = options.user || process.env.MONOPAGE_SMOKE_USER || "admin";
const password = options.password || process.env.MONOPAGE_SMOKE_PASSWORD || "password";
const userId = options["user-id"] || process.env.MONOPAGE_SMOKE_USER_ID || "1";
const wpEnv = resolveWpEnv();
const cookies = new Map();
const failures = [];

if (dryRun) {
  console.log(`[dry-run] Log in to ${baseUrl}/wp-login.php as ${username}.`);
  console.log("[dry-run] Enable Focus Mode and clear the full-dashboard escape for the smoke user.");
  console.log("[dry-run] Confirm /wp-admin/ redirects to the Site Editor front-page canvas.");
  console.log("[dry-run] Confirm the Site Editor entry redirects to the canvas when opened generically.");
  console.log("[dry-run] Confirm Media Library and Monopage controls stay reachable.");
  console.log("[dry-run] Confirm the routing Home page editor redirects to the Site Editor.");
  console.log("[dry-run] Confirm the full-dashboard escape stops the /wp-admin/ redirect.");
  process.exit(0);
}

const originalFocus = wpValue(["option", "get", "monopage_focus_enabled"]);
const originalFullDashboard = wpValue(["user", "meta", "get", userId, "monopage_full_dashboard"], { allowFailure: true });

try {
  wp(["option", "update", "monopage_focus_enabled", "1"]);
  wp(["user", "meta", "delete", userId, "monopage_full_dashboard"], { allowFailure: true });

  await login();

  const adminResponse = await request("/wp-admin/");
  assertSiteEditorRedirect(adminResponse, "/wp-admin/ Focus Mode redirect");

  const genericSiteEditorResponse = await request("/wp-admin/site-editor.php");
  assertSiteEditorRedirect(genericSiteEditorResponse, "generic Site Editor canvas redirect");

  const targetEditorUrl = getLocation(adminResponse);
  const targetEditorResponse = await request(targetEditorUrl.pathname + targetEditorUrl.search, { follow: true });
  assertStatus(targetEditorResponse, 200, "Site Editor canvas target");

  const mediaResponse = await request("/wp-admin/upload.php");
  assertStatus(mediaResponse, 200, "Media Library");

  const controlsResponse = await request("/wp-admin/admin.php?page=monopage", { follow: true });
  assertStatus(controlsResponse, 200, "Monopage controls");
  const controlsBody = await controlsResponse.text();
  if (!controlsBody.includes("Focus Mode") || !controlsBody.includes("Use Full WordPress Dashboard")) {
    failures.push("Monopage controls page did not render expected Focus Mode controls.");
  }

  const homePageId = wpValue(["option", "get", "page_on_front"]);
  const homeEditorResponse = await request(`/wp-admin/post.php?post=${encodeURIComponent(homePageId)}&action=edit`);
  assertSiteEditorRedirect(homeEditorResponse, "routing Home page editor redirect");

  wp(["user", "meta", "update", userId, "monopage_full_dashboard", "1"]);
  const fullDashboardResponse = await request("/wp-admin/");
  if (isSiteEditorRedirect(fullDashboardResponse)) {
    failures.push("Full-dashboard escape still redirected /wp-admin/ to the Site Editor.");
  } else {
    assertStatus(fullDashboardResponse, 200, "Full-dashboard escape /wp-admin/");
  }
} finally {
  restoreState();
}

if (failures.length) {
  console.error("Monopage admin smoke failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Admin smoke passed for ${baseUrl}/.`);
console.log("Checked Focus Mode admin redirect, Site Editor canvas redirect, Media Library, Monopage controls, routing Home editor redirect, and full-dashboard escape.");

async function login() {
  await request("/wp-login.php", { follow: true });

  const body = new URLSearchParams({
    log: username,
    pwd: password,
    "wp-submit": "Log In",
    redirect_to: `${baseUrl}/wp-admin/`,
    testcookie: "1",
  });

  const response = await request("/wp-login.php", {
    method: "POST",
    body,
    contentType: "application/x-www-form-urlencoded",
  });

  if (![200, 302, 303].includes(response.status)) {
    failures.push(`Login returned HTTP ${response.status}.`);
  }

  if (![...cookies.keys()].some((name) => name.startsWith("wordpress_logged_in_"))) {
    failures.push("Login did not set a wordpress_logged_in cookie.");
  }
}

async function request(target, config = {}) {
  const url = new URL(target, baseUrl);
  const headers = new Headers(config.headers || {});

  if (cookies.size) {
    headers.set("Cookie", cookieHeader());
  }

  if (config.contentType) {
    headers.set("Content-Type", config.contentType);
  }

  const response = await fetch(url, {
    method: config.method || "GET",
    body: config.body,
    headers,
    redirect: config.follow ? "follow" : "manual",
  });

  updateCookies(response);
  return response;
}

function updateCookies(response) {
  let setCookies = [];

  if ("function" === typeof response.headers.getSetCookie) {
    setCookies = response.headers.getSetCookie();
  }

  if (!setCookies.length) {
    const combined = response.headers.get("set-cookie");
    if (combined) {
      setCookies = splitSetCookie(combined);
    }
  }

  for (const cookie of setCookies) {
    const pair = cookie.split(";")[0];
    const separator = pair.indexOf("=");
    if (separator <= 0) {
      continue;
    }

    cookies.set(pair.slice(0, separator), pair.slice(separator + 1));
  }
}

function splitSetCookie(value) {
  return String(value).split(/,\s*(?=[^=;,]+=)/);
}

function cookieHeader() {
  return [...cookies.entries()].map(([name, value]) => `${name}=${value}`).join("; ");
}

function assertSiteEditorRedirect(response, label) {
  if (!isSiteEditorRedirect(response)) {
    failures.push(`${label} did not redirect to the front-page Site Editor canvas. Got HTTP ${response.status}${formatLocation(response)}.`);
  }
}

function isSiteEditorRedirect(response) {
  if (response.status < 300 || response.status > 399) {
    return false;
  }

  const location = response.headers.get("location") || "";
  return location.includes("/wp-admin/site-editor.php")
    && location.includes("front-page")
    && location.includes("canvas=edit");
}

function getLocation(response) {
  const location = response.headers.get("location");
  if (!location) {
    failures.push("Redirect response is missing a Location header.");
    return new URL("/wp-admin/site-editor.php", baseUrl);
  }

  return new URL(location, baseUrl);
}

function assertStatus(response, expected, label) {
  if (response.status !== expected) {
    failures.push(`${label} returned HTTP ${response.status}${formatLocation(response)}; expected ${expected}.`);
  }
}

function formatLocation(response) {
  const location = response.headers.get("location");
  return location ? ` location=${location}` : "";
}

function wp(args, config = {}) {
  const result = spawnSync(wpEnv, ["run", "cli", "wp", ...args], {
    cwd: root,
    encoding: "utf8",
  });

  if (0 !== result.status && !config.allowFailure) {
    throw new Error(`WP-CLI command failed: wp ${args.join(" ")}\n${result.stderr || result.stdout}`);
  }

  return result;
}

function wpValue(args, config = {}) {
  const result = wp(args, config);
  if (0 !== result.status) {
    return null;
  }

  const valueLine = result.stdout
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .find((line) => !line.includes("Starting '") && !line.includes("Ran `") && !line.includes("Command failed"));

  return valueLine || "";
}

function restoreState() {
  if (null === originalFocus) {
    wp(["option", "delete", "monopage_focus_enabled"], { allowFailure: true });
  } else {
    wp(["option", "update", "monopage_focus_enabled", originalFocus], { allowFailure: true });
  }

  if (null === originalFullDashboard || "" === originalFullDashboard) {
    wp(["user", "meta", "delete", userId, "monopage_full_dashboard"], { allowFailure: true });
  } else {
    wp(["user", "meta", "update", userId, "monopage_full_dashboard", originalFullDashboard], { allowFailure: true });
  }
}

function resolveWpEnv() {
  const extension = "win32" === process.platform ? ".cmd" : "";
  const local = path.join(root, "node_modules", ".bin", `wp-env${extension}`);

  return fs.existsSync(local) ? local : `wp-env${extension}`;
}

function normalizeBaseUrl(value) {
  return String(value).replace(/\/+$/, "");
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
