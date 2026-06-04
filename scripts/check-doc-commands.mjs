import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const scripts = new Set(Object.keys(manifest.scripts || {}));
const docs = [
  "README.md",
  "docs/wiki/Developer-Guide.md",
  "docs/wiki/Home.md",
  "skills/monopage-deploy/SKILL.md",
];
const failures = [];

for (const doc of docs) {
  checkDocument(doc);
}

if (failures.length) {
  console.error("Documented command checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Documented npm and helper commands are valid across ${docs.length} files.`);

function checkDocument(relativeFile) {
  const file = path.join(root, relativeFile);

  if (!fs.existsSync(file)) {
    failures.push(`missing documentation file: ${relativeFile}`);
    return;
  }

  const content = fs.readFileSync(file, "utf8");

  for (const match of content.matchAll(/\bnpm\s+run\s+([A-Za-z0-9:_-]+)/g)) {
    const script = match[1];
    if (!scripts.has(script)) {
      failures.push(`${relativeFile} references missing package script: npm run ${script}`);
    }
  }

  for (const match of content.matchAll(/\bnpm\s+test\b/g)) {
    if (!scripts.has("test")) {
      failures.push(`${relativeFile} references npm test but package.json has no test script`);
    }
  }

  for (const match of content.matchAll(/\bnode\s+(scripts\/[A-Za-z0-9._/-]+\.mjs)\b/g)) {
    const scriptPath = match[1];
    if (!fs.existsSync(path.join(root, scriptPath))) {
      failures.push(`${relativeFile} references missing helper script: node ${scriptPath}`);
    }
  }
}
