import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const manifest = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const scripts = manifest.scripts || {};
const scriptNames = new Set(Object.keys(scripts));
const failures = [];

for (const [scriptName, command] of Object.entries(scripts)) {
  checkScriptReferences(scriptName, command);
}

if (failures.length) {
  console.error("Package script checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Package script references are valid across ${scriptNames.size} scripts.`);

function checkScriptReferences(scriptName, command) {
  for (const match of command.matchAll(/\bnpm\s+run\s+([A-Za-z0-9:_-]+)/g)) {
    const target = match[1];
    if (!scriptNames.has(target)) {
      failures.push(`${scriptName} references missing package script: npm run ${target}`);
    }
  }

  for (const match of command.matchAll(/\bnpm\s+test\b/g)) {
    if (!scriptNames.has("test")) {
      failures.push(`${scriptName} references npm test but package.json has no test script`);
    }
  }

  for (const match of command.matchAll(/\bnode\s+(scripts\/[A-Za-z0-9._/-]+\.mjs)\b/g)) {
    const scriptPath = match[1];
    if (!fs.existsSync(path.join(root, scriptPath))) {
      failures.push(`${scriptName} references missing helper script: node ${scriptPath}`);
    }
  }
}
