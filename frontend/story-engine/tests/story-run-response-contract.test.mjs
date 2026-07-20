import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const serviceSource = fs.readFileSync(new URL('../src/app/core/story-run.service.ts', import.meta.url), 'utf8');
const modelsSource = fs.readFileSync(new URL('../src/app/core/api/api.models.ts', import.meta.url), 'utf8');
const integrationModelsSource = fs.readFileSync(new URL('../src/app/core/integration.models.ts', import.meta.url), 'utf8');

function selection(i) {
  const quadrant = String(i).padStart(2, '0');
  return { quadrantId: `q${quadrant}`, versionId: `v0${((i - 1) % 7) + 1}`, slotId: `slot-${quadrant}` };
}

function normalized(run) {
  return run.selections ?? run.quadrantSelections ?? [];
}

function validSelections(selections) {
  const expected = new Set(Array.from({ length: 29 }, (_, i) => `q${String(i + 1).padStart(2, '0')}`));
  if (!Array.isArray(selections) || selections.length !== 29) return false;
  const seen = new Set();
  for (const item of selections) {
    if (typeof item.quadrantId !== 'string' || typeof item.versionId !== 'string' || typeof item.slotId !== 'string') return false;
    if (seen.has(item.quadrantId) || !expected.has(item.quadrantId) || !/^v0[1-7]$/.test(item.versionId) || !item.slotId) return false;
    seen.add(item.quadrantId);
  }
  return seen.size === expected.size && [...expected].every(id => seen.has(id));
}

test('API model declares canonical selections and legacy quadrantSelections compatibility', () => {
  assert.match(modelsSource, /export interface ApiQuadrantSelection\{quadrantId:string;versionId:string;slotId:string\}/);
  assert.match(modelsSource, /export interface ApiStoryRun\{storyRunId:string;resolutionMode:string;selections:ApiQuadrantSelection\[\];quadrantSelections\?:ApiQuadrantSelection\[\];revision\?:number\}/);
});



test('story run validation is a type guard from API strings to trusted selections', () => {
  assert.match(serviceSource, /import\{QuadrantId,SafeIntegrationError,StoryVersionId,SyncStatus\}from'\.\/integration\.models'/);
  assert.match(serviceSource, /type ValidatedApiQuadrantSelection=Omit<ApiQuadrantSelection,'quadrantId'\|'versionId'>&\{quadrantId:QuadrantId;versionId:StoryVersionId\}/);
  assert.match(serviceSource, /private validSelections\(selections:ApiQuadrantSelection\[\]\):selections is ValidatedApiQuadrantSelection\[\]/);
  assert.match(integrationModelsSource, /quadrantSelections:\{quadrantId:QuadrantId;versionId:StoryVersionId;slotId:string\}\[\]/);
  assert.match(serviceSource, /this\.store\.setSelections\(next,\{storyRunId,resolutionMode,quadrantSelections:selections/);
});

test('response with selections and 29 items is accepted', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  assert.equal(normalized({ selections }), selections);
  assert.equal(validSelections(normalized({ selections })), true);
});

test('legacy response with quadrantSelections can still be accepted', () => {
  const quadrantSelections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  assert.equal(normalized({ quadrantSelections }), quadrantSelections);
  assert.equal(validSelections(normalized({ quadrantSelections })), true);
});



test('q01 to q29 with v01 to v07 pass validation', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  assert.equal(validSelections(selections), true);
});

test('invalid quadrant is rejected', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  selections[0] = { ...selections[0], quadrantId: 'q30' };
  assert.equal(validSelections(selections), false);
});

test('invalid version is rejected', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  selections[0] = { ...selections[0], versionId: 'v08' };
  assert.equal(validSelections(selections), false);
});

test('duplicate quadrant is rejected', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  selections[1] = { ...selections[1], quadrantId: 'q01' };
  assert.equal(validSelections(selections), false);
});

test('empty slotId is rejected', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  selections[0] = { ...selections[0], slotId: '' };
  assert.equal(validSelections(selections), false);
});

test('response without both selection fields does not cause a TypeError', () => {
  assert.deepEqual(normalized({}), []);
  assert.equal(validSelections(normalized({})), false);
  assert.match(serviceSource, /run\.selections\?\?run\.quadrantSelections\?\?\[\]/);
});

test('response with fewer than 29 selections is rejected in a controlled way', () => {
  const selections = Array.from({ length: 28 }, (_, i) => selection(i + 1));
  assert.equal(validSelections(selections), false);
  assert.match(serviceSource, /STORY_RUN_INVALID_RESPONSE/);
});

test('valid response applies the 29 quadrants and preserves exact quadrant and version validation', () => {
  const selections = Array.from({ length: 29 }, (_, i) => selection(i + 1));
  const applied = new Map();
  for (const item of selections) applied.set(item.quadrantId, item.versionId);
  assert.equal(validSelections(selections), true);
  assert.equal(applied.size, 29);
  assert.equal(applied.get('q01'), 'v01');
  assert.equal(applied.get('q29'), 'v01');
  assert.match(serviceSource, /EXPECTED_QUADRANTS/);
  assert.match(serviceSource, /\^v0\[1-7\]\$/);
  assert.match(serviceSource, /this\.grid\.setQuadrantVersion\(s\.quadrantId,s\.versionId\)/);
});

test('invalid response is not classified as offline, while real network failure remains offline', () => {
  assert.match(serviceSource, /this\.store\.setSync\('error',0,error\)/);
  assert.doesNotMatch(serviceSource, /STORY_RUN_OFFLINE/);
  assert.match(serviceSource, /if\(!err\.status\|\|err\.status===0\)return'offline'/);
  assert.match(serviceSource, /if\(err\.status===401\)return'authentication-error'/);
  assert.match(serviceSource, /if\(err\.status===409\)return'conflict'/);
  assert.match(serviceSource, /if\(err\.status===422\)return'validation-error'/);
});

test('tna:story-run-resolved is emitted once for a successful apply only', () => {
  const matches = serviceSource.match(/this\.events\.emit\('tna:story-run-resolved'/g) || [];
  assert.equal(matches.length, 1);
  assert.match(serviceSource, /if\(result\)\{this\.events\.emit\('tna:story-run-resolved'/);
  assert.match(serviceSource, /else\{this\.rejectInvalidResponse\('STORY_RUN_SELECTIONS_INVALID'\)\}/);
});
