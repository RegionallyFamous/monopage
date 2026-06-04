const options = parseArgs(process.argv.slice(2));
const baseUrl = normalizeBaseUrl(options.url || process.env.MONOPAGE_SMOKE_URL || "http://localhost:8888");
const dryRun = Boolean(options["dry-run"]);
const maxAssetBytes = 750 * 1024;
const expectedCopy = [
  "One page. All signal.",
  "Monopage Agency 3000",
  "Run the signal",
  "Launch the brief",
  "Make the whole room point at one next move.",
];
const requiredAnchors = [
  "top",
  "promise",
  "services",
  "showcase",
  "results",
  "pricing",
  "questions",
  "start",
];
const themeAssets = [
  "/wp-content/themes/monopage-canvas/assets/monopage-riso-hero.jpg",
  "/wp-content/themes/monopage-canvas/assets/monopage-riso-flow.jpg",
  "/wp-content/themes/monopage-canvas/assets/monopage-riso-launch.jpg",
  "/wp-content/themes/monopage-canvas/assets/images/monopage-riso-future-agency-hero.jpg",
  "/wp-content/themes/monopage-canvas/assets/images/monopage-riso-print-lab.jpg",
  "/wp-content/themes/monopage-canvas/assets/images/monopage-riso-skyline-banner.jpg",
];

if (dryRun) {
  console.log(`[dry-run] Smoke homepage at ${baseUrl}/`);
  console.log(`[dry-run] Check ${expectedCopy.length} starter copy snippets.`);
  console.log(`[dry-run] Check required anchors: ${requiredAnchors.map((anchor) => `#${anchor}`).join(", ")}`);
  console.log(`[dry-run] Check body links stay on-page and target rendered anchors.`);
  console.log(`[dry-run] Check ${themeAssets.length} Monopage Canvas assets are served.`);
  process.exit(0);
}

const failures = [];
const homeHtml = await fetchText(`${baseUrl}/`, "homepage");
const bodyHtml = extractBody(homeHtml);
const bodyText = stripTags(bodyHtml);
const renderedIds = new Set(extractAttributes(bodyHtml, "id"));
const bodyHrefs = extractAttributes(bodyHtml, "href");

for (const snippet of expectedCopy) {
  if (!bodyText.includes(snippet)) {
    failures.push(`missing homepage copy: ${snippet}`);
  }
}

for (const anchor of requiredAnchors) {
  if (!renderedIds.has(anchor)) {
    failures.push(`missing required section anchor: #${anchor}`);
  }
}

for (const href of bodyHrefs) {
  if (!href.startsWith("#")) {
    failures.push(`body link is not a same-page anchor: ${href}`);
    continue;
  }

  const target = decodeURIComponent(href.slice(1));
  if (target && !renderedIds.has(target)) {
    failures.push(`body link target is missing: ${href}`);
  }
}

const stylesheet = await fetchText(`${baseUrl}/wp-content/themes/monopage-canvas/style.css`, "Canvas stylesheet");

for (const assetPath of themeAssets) {
  const filename = assetPath.split("/").pop();

  if (!stylesheet.includes(filename)) {
    failures.push(`Canvas stylesheet does not reference ${filename}`);
  }

  await assertAsset(assetPath);
}

if (failures.length) {
  console.error("Monopage homepage smoke check failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Homepage smoke passed for ${baseUrl}/.`);
console.log(`Checked ${expectedCopy.length} copy snippets, ${requiredAnchors.length} anchors, ${bodyHrefs.length} body links, and ${themeAssets.length} theme assets.`);

async function assertAsset(assetPath) {
  const response = await fetch(`${baseUrl}${assetPath}`, {
    method: "HEAD",
  });

  if (!response.ok) {
    failures.push(`asset request failed: ${assetPath} (${response.status})`);
    return;
  }

  const length = Number(response.headers.get("content-length") || 0);
  if (!Number.isFinite(length) || length <= 0) {
    failures.push(`asset has no content length: ${assetPath}`);
  } else if (length > maxAssetBytes) {
    failures.push(`asset is too large: ${assetPath} (${formatBytes(length)}, max ${formatBytes(maxAssetBytes)})`);
  }

  const type = response.headers.get("content-type") || "";
  if (!type.includes("image/")) {
    failures.push(`asset is not served as an image: ${assetPath} (${type || "unknown content type"})`);
  }
}

async function fetchText(url, label) {
  let response;

  try {
    response = await fetch(url);
  } catch (error) {
    console.error(`Could not fetch ${label} at ${url}: ${error.message}`);
    process.exit(1);
  }

  if (!response.ok) {
    console.error(`Could not fetch ${label} at ${url}: HTTP ${response.status}`);
    process.exit(1);
  }

  return response.text();
}

function extractBody(html) {
  const match = html.match(/<body\b[^>]*>([\s\S]*?)<\/body>/i);
  return match ? match[1] : html;
}

function extractAttributes(html, attribute) {
  const values = [];
  const pattern = new RegExp(`${attribute}\\s*=\\s*("[^"]*"|'[^']*'|[^\\s>]+)`, "gi");
  let match;

  while ((match = pattern.exec(html))) {
    values.push(unquote(match[1]));
  }

  return values;
}

function stripTags(html) {
  return html
    .replace(/<script\b[\s\S]*?<\/script>/gi, " ")
    .replace(/<style\b[\s\S]*?<\/style>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function unquote(value) {
  if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
    return value.slice(1, -1);
  }

  return value;
}

function normalizeBaseUrl(value) {
  return String(value).replace(/\/+$/, "");
}

function formatBytes(bytes) {
  return `${Math.round(bytes / 1024)} KB`;
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
