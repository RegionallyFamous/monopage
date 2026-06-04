import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const themeDir = path.join(root, "themes/monopage-canvas");
const styleFile = path.join(themeDir, "style.css");
const functionsFile = path.join(themeDir, "functions.php");
const patternDir = path.join(themeDir, "patterns");
const maxCssImageBytes = 750 * 1024;
const failures = [];

checkStyleHooks();
checkCssAssets();
checkPatternCategory();
checkPatterns();

if (failures.length) {
  console.error("Monopage Canvas theme checks failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("Canvas theme styles, assets, and patterns are wired for editor and front end.");

function checkStyleHooks() {
  const content = readFile(functionsFile);
  const checks = [
    ["editor style support", /add_theme_support\(\s*'editor-styles'\s*\)/],
    ["editor style.css registration", /add_editor_style\(\s*'style\.css'\s*\)/],
    ["front-end enqueue hook", /add_action\(\s*'wp_enqueue_scripts'\s*,\s*'monopage_canvas_enqueue_styles'\s*\)/],
    ["front-end stylesheet enqueue", /wp_enqueue_style\(/],
    ["front-end stylesheet URI", /get_stylesheet_uri\(\)/],
  ];

  for (const [label, pattern] of checks) {
    if (!pattern.test(content)) {
      failures.push(`missing ${label} in themes/monopage-canvas/functions.php`);
    }
  }
}

function checkPatternCategory() {
  const content = readFile(functionsFile);
  const checks = [
    ["pattern category hook", /add_action\(\s*'init'\s*,\s*'monopage_canvas_register_pattern_categories'\s*\)/],
    ["pattern category registration", /register_block_pattern_category\(/],
    ["Monopage pattern category slug", /'monopage-canvas'/],
  ];

  for (const [label, pattern] of checks) {
    if (!pattern.test(content)) {
      failures.push(`missing ${label} in themes/monopage-canvas/functions.php`);
    }
  }
}

function checkPatterns() {
  if (!fs.existsSync(patternDir)) {
    failures.push("missing bundled pattern directory: themes/monopage-canvas/patterns");
    return;
  }

  const files = fs
    .readdirSync(patternDir)
    .filter((file) => file.endsWith(".php"))
    .sort();

  if (!files.length) {
    failures.push("missing bundled Canvas patterns");
    return;
  }

  for (const file of files) {
    const content = readFile(path.join(patternDir, file));
    const relative = `themes/monopage-canvas/patterns/${file}`;

    if (!/Title:\s*\S+/.test(content)) {
      failures.push(`missing pattern Title header: ${relative}`);
    }

    if (!/Slug:\s*monopage-canvas\/[a-z0-9-]+/.test(content)) {
      failures.push(`missing Monopage pattern Slug header: ${relative}`);
    }

    if (!/Categories:\s*.*\bmonopage-canvas\b/.test(content)) {
      failures.push(`missing Monopage pattern category: ${relative}`);
    }
  }
}

function checkCssAssets() {
  const css = readFile(styleFile);
  const urls = extractCssUrls(css);

  if (!urls.length) {
    failures.push("style.css does not reference any theme assets");
    return;
  }

  for (const url of urls) {
    const result = resolveThemeAsset(url);

    if (result.skip) {
      continue;
    }

    if (result.error) {
      failures.push(result.error);
      continue;
    }

    if (!fs.existsSync(result.path)) {
      failures.push(`missing CSS asset: ${url}`);
      continue;
    }

    const stats = fs.statSync(result.path);
    if (isImageAsset(result.path) && stats.size > maxCssImageBytes) {
      failures.push(`CSS image asset is too large: ${url} (${formatBytes(stats.size)}, max ${formatBytes(maxCssImageBytes)})`);
    }
  }
}

function extractCssUrls(css) {
  const urls = [];
  const pattern = /url\(\s*(?:"([^"]+)"|'([^']+)'|([^'")]+))\s*\)/g;
  let match;

  while ((match = pattern.exec(css))) {
    urls.push((match[1] || match[2] || match[3] || "").trim());
  }

  return urls;
}

function resolveThemeAsset(url) {
  if (!url || url.startsWith("data:") || url.startsWith("#")) {
    return { skip: true };
  }

  if (/^[a-z][a-z0-9+.-]*:/i.test(url) || url.startsWith("//")) {
    return { error: `CSS asset must be packaged with the theme: ${url}` };
  }

  if (url.startsWith("/")) {
    return { error: `CSS asset must use a theme-relative path: ${url}` };
  }

  const cleanUrl = url.split(/[?#]/)[0];
  const assetPath = path.resolve(themeDir, cleanUrl);
  const relative = path.relative(themeDir, assetPath);

  if (relative.startsWith("..") || path.isAbsolute(relative)) {
    return { error: `CSS asset escapes the theme directory: ${url}` };
  }

  return { path: assetPath };
}

function isImageAsset(file) {
  return /\.(avif|gif|jpe?g|png|svg|webp)$/i.test(file);
}

function formatBytes(bytes) {
  return `${Math.round(bytes / 1024)} KB`;
}

function readFile(file) {
  return fs.readFileSync(file, "utf8");
}
