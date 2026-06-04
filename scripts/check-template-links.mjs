import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const template = path.join(root, "themes/monopage-canvas/templates/front-page.html");
const html = fs.readFileSync(template, "utf8");
const linkPattern = /href=(["'])(.*?)\1/g;
const idPattern = /\bid=(["'])(.*?)\1/g;
const blockAnchorPattern = /"anchor"\s*:\s*"([^"]+)"/g;
const navigationLinkPattern = /<!--\s+wp:navigation-link\s+({.*?})\s+\/-->/g;
const invalidLinks = [];
const malformedBlocks = [];
const malformedTargets = [];
const missingTargets = [];
const duplicateIds = [];
const seenIds = new Set();
const anchors = new Set();
let match;

while ((match = idPattern.exec(html))) {
  const id = match[2].trim();

  if (!id) {
    continue;
  }

  if (seenIds.has(id)) {
    duplicateIds.push(id);
  }

  seenIds.add(id);
  anchors.add(id);
}

while ((match = blockAnchorPattern.exec(html))) {
  const anchor = decodeJsonString(match[1]).trim();

  if (anchor) {
    anchors.add(anchor);
  }
}

while ((match = linkPattern.exec(html))) {
  checkLink(match[2].trim());
}

while ((match = navigationLinkPattern.exec(html))) {
  const attributes = parseBlockAttributes(match[1]);

  if (!attributes) {
    malformedBlocks.push(match[0]);
    continue;
  }

  if (attributes.url) {
    checkLink(String(attributes.url).trim());
  }
}

function checkLink(href) {
  if (!href.startsWith("#")) {
    invalidLinks.push(href);
    return;
  }

  const target = decodeHashTarget(href);

  if (null === target) {
    malformedTargets.push(href);
    return;
  }

  if (!target || !anchors.has(target)) {
    missingTargets.push(href);
  }
}

if (invalidLinks.length || malformedBlocks.length || malformedTargets.length || missingTargets.length || duplicateIds.length) {
  console.error("Monopage Canvas front-page links must stay on-page and target existing sections:");

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

  process.exit(1);
}

console.log(`Template links stay on-page and target ${anchors.size} anchors.`);

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
