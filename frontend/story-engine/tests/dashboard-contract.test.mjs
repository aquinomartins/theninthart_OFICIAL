import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const manifest = JSON.parse(fs.readFileSync(new URL('../../../data/story-widgets.json', import.meta.url), 'utf8'));
const dashboardSource = fs.readFileSync(new URL('../../../scripts/dashboard-hub.js', import.meta.url), 'utf8');
const bridgeSource = fs.readFileSync(new URL('../src/app/core/dashboard-bridge.service.ts', import.meta.url), 'utf8');
const persistenceSource = fs.readFileSync(new URL('../src/app/core/story-persistence.service.ts', import.meta.url), 'utf8');
const queueSource = fs.readFileSync(new URL('../src/app/core/pending-operation-queue.service.ts', import.meta.url), 'utf8');

const paramsFor = (id) => manifest.items.find((widget) => widget.id === id).parameters.map((parameter) => parameter.id);
const forbiddenDemoParams = ['session-duration','mode','auto-start','task-title','task-priority','task-tags','criteria','period','visibility','notes'];

test('time-widget is mapped to timeline and emits only timeline parameters', () => {
  assert.match(dashboardSource, /'time-widget':'timeline'/);
  assert.match(dashboardSource, /canonicalWidgetId:'timeline'/);
  assert.deepEqual(paramsFor('timeline'), ['dominant-era','specific-period','displacement-intensity','temporal-duration','temporal-direction','paradox-level']);
  assert.match(dashboardSource, /storyWidgetsManifest/);
  assert.match(bridgeSource, /'time-widget':'timeline'/);
  assert.match(bridgeSource, /canonicalParams\(mapped,params\)/);
});

test('tasks-widget is mapped to dramatic-climate and emits only dramatic-climate parameters', () => {
  assert.match(dashboardSource, /'tasks-widget':'dramatic-climate'/);
  assert.match(dashboardSource, /canonicalWidgetId:'dramatic-climate'/);
  assert.deepEqual(paramsFor('dramatic-climate'), ['humor','wonder','suspense','danger','melancholy','emotional-intensity','life-death-balance','imagination-reality-balance']);
  assert.match(dashboardSource, /storyWidgetsManifest/);
});

test('public snapshots and parameter-change events use canonical widget ids', () => {
  assert.match(dashboardSource, /publicWidgetsState = \(\) => Object\.fromEntries\(Object\.values\(dashboardState\.widgets\)\.map\(\(w\)=>\[w\.canonicalWidgetId/);
  assert.match(dashboardSource, /emit\('dashboard:parameter-change',\{widgetId:w\.canonicalWidgetId \|\| widgetId/);
  assert.match(dashboardSource, /emit\('dashboard:widget-save',\{widgetId:w\.canonicalWidgetId \|\| widgetId/);
  assert.match(dashboardSource, /emit\('dashboard:widget-reset',\{widgetId:w\.canonicalWidgetId \|\| widgetId/);
});

test('range values are normalized as finite numbers with bounds and step before publication', () => {
  assert.match(dashboardSource, /if\(type==='range'\|\|type==='number'\|\|type==='duration'\)\{ const value=Number\(rawValue\)/);
  assert.match(dashboardSource, /if\(!Number\.isFinite\(value\)\) return undefined/);
  assert.match(dashboardSource, /next=min\+Math\.round\(\(next-min\)\/step\)\*step/);
  assert.match(dashboardSource, /filter\(\(\[,value\]\)=>value!==undefined\)/);
});

test('demonstrative parameters are not present in the canonical manifest and are filtered by the bridge', () => {
  const canonical = new Set(manifest.items.flatMap((widget) => widget.parameters.map((parameter) => parameter.id)));
  for (const id of forbiddenDemoParams) assert.equal(canonical.has(id), false, `${id} must not be canonical`);
  assert.match(bridgeSource, /Object\.fromEntries\(Object\.entries\(params\)\.filter\(\(\[key\]\)=>allowed\.has\(key\)\)\)/);
});

test('HTTP 422 is classified as validation-error instead of offline', () => {
  assert.match(persistenceSource, /err\.status===422\?'validation-error'/);
  assert.doesNotMatch(persistenceSource, /err\.status===409\?'conflict':'offline'/);
});

test('HTTP 422 operations are rejected and removed to avoid infinite retries', () => {
  assert.match(persistenceSource, /if\(err\.status===422\)\{this\.q\.reject\(op\.id\);this\.q\.remove\(op\.id\)\}/);
  assert.match(queueSource, /reject\(id:string\)/);
  assert.match(queueSource, /status:'rejected'/);
});

test('dashboard controls and bridge restore continue to avoid recursion and duplicate restore events', () => {
  assert.match(dashboardSource, /const publicControlsState = \(\) => Object\.fromEntries/);
  assert.match(dashboardSource, /if\(dashboardState\.restoringExternalState\) return/);
  assert.match(dashboardSource, /dashboardState\.restoringExternalState=!emitEvents/);
  assert.match(dashboardSource, /canonicalToVisualWidgetMap\[widgetId\]/);
});
