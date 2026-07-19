import fs from 'node:fs/promises';
import path from 'node:path';

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const assetRoot = path.join(root, 'assets/story-engine');
const manifestPath = path.join(assetRoot, 'story-engine-assets.json');
const forbidden = 'BUILD_FALLBACK_READONLY';

function fail(message) {
  throw new Error(`[story-engine-assets] ${message}`);
}

function assertAssetPath(value, kind) {
  if (typeof value !== 'string') fail(`${kind} entry must be a string.`);
  if (!value.startsWith('/assets/story-engine/')) fail(`${kind} entry must use /assets/story-engine/: ${value}`);
  if (value.includes('..')) fail(`${kind} entry must not traverse directories: ${value}`);
  if (/^[a-z]+:/i.test(value) || /^[A-Za-z]:[\\/]/.test(value)) fail(`${kind} entry must not be absolute local or URL path: ${value}`);
}

async function readManifest() {
  try {
    return JSON.parse(await fs.readFile(manifestPath, 'utf8'));
  } catch (error) {
    fail(`manifest is not valid JSON: ${error.message}`);
  }
}

async function verifyListedFile(assetPath, kind) {
  assertAssetPath(assetPath, kind);
  const relative = assetPath.replace('/assets/story-engine/', '');
  const physical = path.join(assetRoot, relative);
  const resolved = path.resolve(physical);
  if (!resolved.startsWith(assetRoot + path.sep)) fail(`${kind} escapes asset directory: ${assetPath}`);
  const stat = await fs.stat(resolved).catch(() => null);
  if (!stat?.isFile()) fail(`${kind} file does not exist: ${assetPath}`);
  if (resolved.endsWith('.map')) fail(`${kind} must not be a source map: ${assetPath}`);
  const contents = await fs.readFile(resolved, 'utf8');
  if (contents.includes(forbidden)) fail(`${kind} contains forbidden fallback marker: ${assetPath}`);
  if (/\/(home|workspace|tmp|Users)\//.test(contents) || /[A-Za-z]:\\/.test(contents)) {
    fail(`${kind} contains an absolute local path: ${assetPath}`);
  }
  return {assetPath, contents};
}

const manifest = await readManifest();
const scripts = Array.isArray(manifest.scripts) ? manifest.scripts : fail('scripts must be an array.');
const styles = Array.isArray(manifest.styles) ? manifest.styles : [];

if (scripts.length === 0) fail('scripts must not be empty.');
if (!scripts.some((script) => /\/main-[^/]+\.js$|\/main\.js$/.test(script))) fail('main bundle is missing.');

const listed = [...styles.map((assetPath) => [assetPath, 'style']), ...scripts.map((assetPath) => [assetPath, 'script'])];
if (new Set(listed.map(([assetPath]) => assetPath)).size !== listed.length) fail('manifest contains duplicate assets.');

const verifiedScripts = [];
for (const [assetPath, kind] of listed) {
  const verified = await verifyListedFile(assetPath, kind);
  if (kind === 'script') verifiedScripts.push(verified);
}

const main = verifiedScripts.find(({assetPath}) => /\/main-[^/]+\.js$|\/main\.js$/.test(assetPath));
if (!main) fail('main bundle was not physically verified.');

const angularEvidence = [
  'TNAStoryEngine',
  'tna-story-engine',
  'tna:story-engine-ready',
  'data-angular-story-grid',
];
const missingEvidence = angularEvidence.filter((needle) => !main.contents.includes(needle));
if (missingEvidence.length > 0) {
  fail(`main bundle does not contain Angular application evidence: ${missingEvidence.join(', ')}`);
}

const allFiles = await fs.readdir(assetRoot);
const maps = allFiles.filter((file) => file.endsWith('.map'));
if (maps.length > 0) fail(`asset directory contains source maps: ${maps.join(', ')}`);

console.log(`[OK] story-engine-assets.json verified: ${styles.length} CSS, ${scripts.length} JS`);
