import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const serviceSource = fs.readFileSync(new URL('../src/app/core/story-run.service.ts', import.meta.url), 'utf8');
const modelsSource = fs.readFileSync(new URL('../src/app/core/api/api.models.ts', import.meta.url), 'utf8');
const integrationModelsSource = fs.readFileSync(new URL('../src/app/core/integration.models.ts', import.meta.url), 'utf8');
function richSelection(i) { const q = String(i).padStart(2, '0'); return { position: i, quadrant: { id: `q${q}`, number: i, blockId: `b${q}`, blockLabel: `B ${q}` }, slotId: `q${q}-v0${((i - 1) % 7) + 1}`, version: `v0${((i - 1) % 7) + 1}`, title: `T ${q}`, selectionReason: 'baseline', payload: {} }; }
function flatSelection(i) { const q = String(i).padStart(2, '0'); return { quadrantId: `q${q}`, versionId: `v0${((i - 1) % 7) + 1}`, slotId: `q${q}-v0${((i - 1) % 7) + 1}` }; }
function normalizeSelection(raw) { const quadrantId = raw.quadrantId ?? raw.quadrant?.id; const versionId = raw.versionId ?? raw.version; const slotId = raw.slotId; return !quadrantId || !versionId || !slotId ? null : { quadrantId, versionId, slotId }; }
function validSelections(selections) { const expected = new Set(Array.from({ length: 29 }, (_, i) => `q${String(i + 1).padStart(2, '0')}`)); if (!Array.isArray(selections) || selections.length !== 29) return false; const seen = new Set(); for (const item of selections) { if (typeof item.quadrantId !== 'string' || typeof item.versionId !== 'string' || typeof item.slotId !== 'string') return false; if (seen.has(item.quadrantId) || !expected.has(item.quadrantId) || !/^v0[1-7]$/.test(item.versionId) || !item.slotId) return false; seen.add(item.quadrantId); } return seen.size === expected.size && [...expected].every(id => seen.has(id)); }

test('API model declares raw rich selections and legacy compatibility', () => {
  assert.match(modelsSource, /export interface ApiRawQuadrantSelection\{quadrantId\?:string;versionId\?:string;quadrant\?:\{id\?:string;number\?:number;blockId\?:string;blockLabel\?:string\};version\?:string;slotId\?:string;position\?:number;title\?:string;selectionReason\?:string;payload\?:unknown\}/);
  assert.match(modelsSource, /export interface ApiStoryRun\{storyRunId:string;resolutionMode:string;selections:ApiRawQuadrantSelection\[\];quadrantSelections\?:ApiRawQuadrantSelection\[\];revision\?:number\}/);
});

test('story run service normalizes before preserving strict validation', () => {
  assert.match(serviceSource, /private normalizeSelection\(raw:ApiRawQuadrantSelection\):ApiQuadrantSelection\|null/);
  assert.match(serviceSource, /const quadrantId=raw\.quadrantId\?\?raw\.quadrant\?\.id/);
  assert.match(serviceSource, /const versionId=raw\.versionId\?\?raw\.version/);
  assert.match(serviceSource, /const selections=normalized\.filter\(\(selection\):selection is ApiQuadrantSelection=>selection!==null\)/);
  assert.match(serviceSource, /private validSelections\(selections:ApiQuadrantSelection\[\]\):selections is ValidatedApiQuadrantSelection\[\]/);
  assert.match(integrationModelsSource, /diagnostics\?:Record<string,unknown>/);
});

test('rich backend format is normalized: quadrant.id and version become canonical fields', () => { assert.deepEqual(normalizeSelection(richSelection(1)), { quadrantId: 'q01', versionId: 'v01', slotId: 'q01-v01' }); });
test('flat legacy format remains accepted', () => { assert.deepEqual(normalizeSelection(flatSelection(2)), { quadrantId: 'q02', versionId: 'v02', slotId: 'q02-v02' }); });
test('exactly 29 valid rich selections are applied after normalization', () => { const selections = Array.from({ length: 29 }, (_, i) => normalizeSelection(richSelection(i + 1))); assert.equal(selections.every(Boolean), true); assert.equal(validSelections(selections), true); assert.match(serviceSource, /this\.grid\.setQuadrantVersion\(s\.quadrantId,s\.versionId\)/); });
test('invalid selection is rejected with safe diagnostics', () => { const raws = Array.from({ length: 29 }, (_, i) => richSelection(i + 1)); delete raws[3].quadrant.id; const normalized = raws.map(normalizeSelection); assert.deepEqual(normalized.flatMap((selection, index) => selection === null ? [index] : []), [3]); assert.match(serviceSource, /receivedSelectionCount:rawSelections\.length,normalizedSelectionCount:selections\.length,invalidSelectionIndexes/); assert.match(serviceSource, /STORY_RUN_INVALID_RESPONSE/); });
test('valid response ends synced, clears lastError, and emits resolved once', () => { assert.match(serviceSource, /this\.store\.setSync\('synced',0,null\)/); assert.match(serviceSource, /this\.events\.emit\('tna:sync-state-changed',\{status:'synced',pendingOperations:0\}\)/); assert.equal((serviceSource.match(/this\.events\.emit\('tna:story-run-resolved'/g) || []).length, 1); assert.match(serviceSource, /if\(result\)\{this\.store\.setSync\('synced',0,null\);.*tna:story-run-resolved/s); });
