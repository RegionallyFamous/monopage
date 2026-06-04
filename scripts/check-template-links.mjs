import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const template = path.join(root, "themes/wpop-canvas/templates/front-page.html");
const html = fs.readFileSync(template, "utf8");
const linkPattern = /href=(["'])(.*?)\1/g;
const invalidLinks = [];
let match;

while ((match = linkPattern.exec(html))) {
  const href = match[2].trim();

  if (!href.startsWith("#")) {
    invalidLinks.push(href);
  }
}

if (invalidLinks.length) {
  console.error("WPOP Canvas front-page links must stay on the same page:");
  for (const href of invalidLinks) {
    console.error(`- ${href}`);
  }
  process.exit(1);
}

console.log("Template links stay on-page.");
