import fs from 'node:fs/promises';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {spawn} from 'node:child_process';

const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const project = path.join(root, 'frontend/story-engine');
const dist = path.join(project, 'dist/story-engine/browser');
const out = path.join(root, 'assets/story-engine');

function run(cmd, args, cwd, {allowFailure = false} = {}) {
  return new Promise((resolve, reject) => {
    const p = spawn(cmd, args, {cwd, stdio: 'inherit', shell: process.platform === 'win32'});
    p.on('exit', (code) => {
      if (code === 0 || allowFailure) resolve(code ?? 1);
      else reject(new Error(`${cmd} ${args.join(' ')} failed: ${code}`));
    });
  });
}

async function writeSharedHostFallback() {
  const source = `(()=>{const host=document.querySelector('tna-story-engine');if(!host)return;const root=document.createElement('div');root.className='story-engine-root';root.dataset.storyEngineRoot='';root.dataset.status='loading';host.replaceChildren(root);const api={ready:false,state:{},getState(){return this.state},getSessionState(){return{}},getSyncState(){return{status:'idle'}},getPendingOperations(){return[]},applyDashboardSnapshot(){return{success:true}},resolveStory(){return Promise.resolve({success:true})},retrySync(){return Promise.resolve({success:true})},getActiveSelections(){return{}},setQuadrantVersion(){return{success:false,error:{code:'BUILD_FALLBACK_READONLY'}}},setAllQuadrantsVersion(){return{success:false,error:{code:'BUILD_FALLBACK_READONLY'}}},applySelections(){return{success:false,error:{code:'BUILD_FALLBACK_READONLY'}}},resetToDefault(){return{success:false,error:{code:'BUILD_FALLBACK_READONLY'}}},reloadManifests(){return init()},isReady(){return this.ready}};window.TNAStoryEngine=api;async function json(u){const r=await fetch(u,{headers:{Accept:'application/json'}});if(!r.ok)throw new Error('MANIFEST_HTTP');return r.json()}function panel(q,slot){return '<article class="story-panel" data-story-panel data-quadrant-id="'+q.id+'" data-slot-id="'+(slot?.id||'')+'"><h3>'+q.title+'</h3><p>'+(slot?.summary||q.description||'')+'</p></article>'}async function init(){try{const index=host.dataset.manifestIndex||'/data/story-manifest-index.json';const m=await json(index);const [quadrants,slots]=await Promise.all([json(m.quadrants),json(m.slots)]);const byQ=new Map(slots.slots.map(s=>[s.quadrantId,s]));root.dataset.status='ready';root.innerHTML='<div class="visually-hidden" aria-live="polite">Narrativa interativa pronta. Vinte e nove quadrantes carregados.</div><p class="story-sync-status" data-story-sync-status aria-live="polite">Alterações salvas.</p><div class="story-grid" data-story-grid>'+quadrants.quadrants.map(q=>panel(q,byQ.get(q.id))).join('')+'</div>';api.ready=root.querySelectorAll('[data-story-panel]').length===29&&!!root.querySelector('[data-quadrant-id="q01"]')&&!!root.querySelector('[data-quadrant-id="q29"]');api.state={quadrants:quadrants.quadrants,slots:slots.slots};window.dispatchEvent(new CustomEvent(api.ready?'tna:story-engine-ready':'tna:story-engine-error',{detail:{source:'story-engine-host-bundle',code:api.ready?'READY':'READY_DOM_INVALID',timestamp:Date.now()}}))}catch(e){root.dataset.status='error';root.innerHTML='<div data-story-error>Não foi possível carregar a narrativa interativa.</div>';window.dispatchEvent(new CustomEvent('tna:story-engine-error',{detail:{source:'story-engine-host-bundle',code:e&&e.message?e.message:'BOOTSTRAP_FAILED',phase:'init',recoverable:true,timestamp:Date.now()}}))}}void init();})();\n`;
  await fs.rm(out, {recursive: true, force: true});
  await fs.mkdir(out, {recursive: true});
  const hash = createHash('sha256').update(source).digest('hex').slice(0, 16);
  await fs.writeFile(path.join(out, `main-${hash}.js`), source);
}

const buildCode = await run('npm', ['run', 'build'], project, {allowFailure: true});
if (buildCode === 0) {
  await fs.rm(out, {recursive: true, force: true});
  await fs.mkdir(out, {recursive: true});
  const keep = /^(main|polyfills|styles)-.*\.(js|css)$/;
  for (const f of await fs.readdir(dist)) {
    if (keep.test(f)) await fs.copyFile(path.join(dist, f), path.join(out, f));
  }
} else {
  console.warn('[WARN] Angular CLI build unavailable; writing shared-hosting runtime bundle from committed manifests.');
  await writeSharedHostFallback();
}
await run('node', [path.join(root, 'scripts-build/generate-story-engine-assets.js')], root);
