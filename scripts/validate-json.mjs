import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const files = [
  ".wp-env.json",
  "package.json",
  "playground/blueprint.json",
  "themes/monopage-canvas/theme.json",
];

for (const file of files) {
  const fullPath = path.join(root, file);
  JSON.parse(fs.readFileSync(fullPath, "utf8"));
  console.log(`Valid JSON: ${file}`);
}
