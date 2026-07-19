import fs from 'node:fs/promises';
import path from 'node:path';
import {spawn} from 'node:child_process';

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const project = path.join(root, 'frontend/story-engine');
const dist = path.join(project, 'dist/story-engine/browser');
const out = path.join(root, 'assets/story-engine');

function run(cmd, args, cwd) {
  return new Promise((resolve, reject) => {
    const p = spawn(cmd, args, {cwd, stdio: 'inherit', shell: process.platform === 'win32'});
    p.on('error', reject);
    p.on('exit', (code) => {
      if (code === 0) resolve();
      else reject(new Error(`${cmd} ${args.join(' ')} failed: ${code}`));
    });
  });
}

async function assertDistReady() {
  const files = await fs.readdir(dist).catch(() => []);
  const publicFiles = files.filter((f) => /^(main|polyfills|styles)-.*\.(js|css)$/.test(f));
  const scripts = publicFiles.filter((f) => /^(main|polyfills)-.*\.js$/.test(f));
  const maps = files.filter((f) => f.endsWith('.map'));

  if (!scripts.some((f) => f.startsWith('main-'))) {
    throw new Error('Angular build did not produce a main JavaScript bundle.');
  }

  if (maps.length > 0) {
    throw new Error(`Angular build produced source maps: ${maps.join(', ')}`);
  }

  return publicFiles;
}

await run('npm', ['run', 'build'], project);
const publicFiles = await assertDistReady();
await fs.rm(out, {recursive: true, force: true});
await fs.mkdir(out, {recursive: true});

for (const f of publicFiles) {
  await fs.copyFile(path.join(dist, f), path.join(out, f));
}

await run('node', [path.join(root, 'scripts-build/generate-story-engine-assets.js')], root);
await run('node', [path.join(root, 'scripts-build/verify-story-engine-assets.js')], root);
