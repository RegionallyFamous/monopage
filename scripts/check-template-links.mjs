import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const themeDir = path.join(root, "themes/monopage-canvas");
const template = path.join(themeDir, "templates/front-page.html");
const patternDir = path.join(themeDir, "patterns");
const linkPattern = /href=(["'])(.*?)\1/g;
const idPattern = /\bid=(["'])(.*?)\1/g;
const blockAnchorPattern = /"anchor"\s*:\s*"([^"]+)"/g;
const navigationLinkPattern = /<!--\s+wp:navigation-link\s+({.*?})\s+\/-->/gs;
const sources = collectSources();
const invalidLinks = [];
const malformedBlocks = [];
const malformedTargets = [];
const missingTargets = [];
const duplicateIds = [];
const duplicateAnchors = [];
const anchors = new Map();

for (const source of sources) {
  collectAnchors(source);
}

for (const source of sources) {
  checkLinks(source);
}

function collectSources() {
  const found = [
    {
      label: "front-page template",
      file: template,
      html: fs.readFileSync(template, "utf8"),
    },
  ];

  if (!fs.existsSync(patternDir)) {
    return found;
  }

  const patternFiles = fs
    .readdirSync(patternDir)
    .filter((file) => file.endsWith(".php"))
    .sort();

  for (const file of patternFiles) {
    found.push({
      label: `pattern ${file}`,
      file: path.join(patternDir, file),
      html: fs.readFileSync(path.join(patternDir, file), "utf8"),
    });
  }

  return found;
}

function collectAnchors(source) {
  const sourceIds = new Set();
  const sourceAnchors = new Set();
  let match;

  idPattern.lastIndex = 0;
  while ((match = idPattern.exec(source.html))) {
    const id = match[2].trim();

    if (sourceIds.has(id)) {
      duplicateIds.push(`${source.label}: ${id}`);
      continue;
    }

    sourceIds.add(id);
    addAnchor(source, sourceAnchors, id);
  }

  blockAnchorPattern.lastIndex = 0;
  while ((match = blockAnchorPattern.exec(source.html))) {
    addAnchor(source, sourceAnchors, decodeJsonString(match[1]).trim());
  }
}

function addAnchor(source, sourceAnchors, anchor) {
  if (!anchor) {
    return;
  }

  if (sourceAnchors.has(anchor)) {
    return;
  }

  sourceAnchors.add(anchor);

  if (!anchors.has(anchor)) {
    anchors.set(anchor, source.label);
    return;
  }

  duplicateAnchors.push(`${anchor} (${anchors.get(anchor)} and ${source.label})`);
}

function checkLinks(source) {
  let match;

  linkPattern.lastIndex = 0;
  while ((match = linkPattern.exec(source.html))) {
    checkLink(source, match[2].trim());
  }

  navigationLinkPattern.lastIndex = 0;
  while ((match = navigationLinkPattern.exec(source.html))) {
    const attributes = parseBlockAttributes(match[1]);

    if (!attributes) {
      malformedBlocks.push(`${source.label}: ${match[0]}`);
      continue;
    }

    if (attributes.url) {
      checkLink(source, String(attributes.url).trim());
    }
  }
}

function checkLink(source, href) {
  if (!href.startsWith("#")) {
    invalidLinks.push(`${source.label}: ${href}`);
    return;
  }

  const target = decodeHashTarget(href);

  if (null === target) {
    malformedTargets.push(`${source.label}: ${href}`);
    return;
  }

  if (!target || !anchors.has(target)) {
    missingTargets.push(`${source.label}: ${href}`);
  }
}

if (invalidLinks.length || malformedBlocks.length || malformedTargets.length || missingTargets.length || duplicateIds.length || duplicateAnchors.length) {
  console.error("Monopage Canvas template and pattern links must stay on-page and target existing sections:");

  if (invalidLinks.length) {
    console.error("");
    console.error("Links must use hash anchors only:");
    for (const href of invalidLinks) {
      console.error(`- ${href}`);
    }
  }

  if (malformedBlocks.length) {
    console.error("");
    console.error("Navigation link block attributes must be valid JSON:");
    for (const block of malformedBlocks) {
      console.error(`- ${block}`);
    }
  }

  if (malformedTargets.length) {
    console.error("");
    console.error("Hash links must use valid URL fragments:");
    for (const href of malformedTargets) {
      console.error(`- ${href}`);
    }
  }

  if (missingTargets.length) {
    console.error("");
    console.error("Hash links must target an existing id or block anchor:");
    for (const href of missingTargets) {
      console.error(`- ${href}`);
    }
  }

  if (duplicateIds.length) {
    console.error("");
    console.error("HTML ids must be unique:");
    for (const id of duplicateIds) {
      console.error(`- ${id}`);
    }
  }

  if (duplicateAnchors.length) {
    console.error("");
    console.error("Bundled section anchors must be unique across the starter template and patterns:");
    for (const id of duplicateAnchors) {
      console.error(`- ${id}`);
    }
  }

  process.exit(1);
}

console.log(`Template and pattern links stay on-page and target ${anchors.size} anchors.`);

function decodeHashTarget(href) {
  try {
    return decodeURIComponent(href.slice(1)).trim();
  } catch (error) {
    return null;
  }
}

function parseBlockAttributes(value) {
  try {
    return JSON.parse(value);
  } catch (error) {
    return null;
  }
}

function decodeJsonString(value) {
  try {
    return JSON.parse(`"${value}"`);
  } catch (error) {
    return value;
  }
}
