import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const componentSource = () => fs.readFileSync(new URL('../src/app/story-engine.component.ts', import.meta.url), 'utf8');
const bridgeSource = () => fs.readFileSync(new URL('../../../scripts/dashboard-story-bridge.js', import.meta.url), 'utf8');

test('story engine waits for rendered 29-panel DOM before announcing ready', () => {
  const src = componentSource();

  assert.match(src, /private readyEventEmitted=false/);
  assert.match(src, /announceReadyWhenRendered\(m,a\.version,a\.index\)/);
  assert.doesNotMatch(src, /queueMicrotask\(\(\)=>\{if\(this\.readyDom\(\)\)/);
  assert.match(src, /querySelectorAll\('\[data-story-panel\]'\)\.length===29/);
  assert.match(src, /querySelector\('\[data-quadrant-id="q01"\]'\)/);
  assert.match(src, /querySelector\('\[data-quadrant-id="q29"\]'\)/);
  assert.match(src, /requestAnimationFrame\(\(\)=>this\.announceReadyWhenRendered/);
  assert.match(src, /this\.readyEventEmitted=true;this\.live='Narrativa interativa pronta\. Vinte e nove quadrantes carregados\.';this\.events\.emit\('tna:story-engine-ready'/);
});

test('story engine ready wait reports diagnostic error through normal error flow', () => {
  const src = componentSource();

  assert.match(src, /if\(attempt>=60\)\{this\.fail\(new Error\('READY_DOM_INVALID'\),'ready-dom',indexPath\);return\}/);
  assert.doesNotMatch(src, /throw new Error\('READY_DOM_INVALID'\)/);
});

function createBridgeHarness({ engineReady = false } = {}) {
  const listeners = new Map();
  const timers = [];
  const intervals = [];
  const calls = [];
  const documentListeners = new Map();
  const context = {
    Date,
    CustomEvent: class CustomEvent {
      constructor(type, init = {}) {
        this.type = type;
        this.detail = init.detail;
      }
    },
    document: {
      readyState: 'complete',
      querySelector(selector) {
        return selector === '[data-dashboard-hub]' || selector === 'tna-story-engine' ? {} : null;
      },
      addEventListener(name, handler) {
        documentListeners.set(name, handler);
      },
      removeEventListener(name) {
        documentListeners.delete(name);
      },
    },
    window: {
      __TNADashboardStoryBridge: false,
      TNAStoryEngine: { isReady: () => engineReady },
      TNADashboardHub: { getStateSnapshot: () => ({ source: 'dashboard' }) },
      addEventListener(name, handler) {
        if (!listeners.has(name)) listeners.set(name, new Set());
        listeners.get(name).add(handler);
      },
      removeEventListener(name, handler) {
        listeners.get(name)?.delete(handler);
      },
      dispatchEvent(event) {
        calls.push(event.type);
        for (const handler of listeners.get(event.type) || []) {
          handler(event);
        }
      },
      setTimeout(handler) {
        timers.push(handler);
        return timers.length;
      },
      clearTimeout(id) {
        timers[id - 1] = null;
      },
      setInterval(handler) {
        intervals.push(handler);
        return intervals.length;
      },
      clearInterval(id) {
        intervals[id - 1] = null;
      },
    },
  };

  vm.runInNewContext(bridgeSource(), context);

  return {
    context,
    calls,
    fire(name, detail = {}) {
      for (const handler of listeners.get(name) || []) {
        handler({ type: name, detail });
      }
    },
    runNextTimer() {
      const timer = timers.shift();
      if (timer) timer();
    },
    runNextInterval() {
      const interval = intervals.find(Boolean);
      if (interval) interval();
    },
    setEngineReady(value) {
      engineReady = value;
    },
  };
}

test('dashboard bridge becomes ready from the normal story-engine-ready event without recursion', () => {
  const harness = createBridgeHarness();

  harness.fire('dashboard:feature-toggle', { controlId: 'c01' });
  harness.fire('tna:story-engine-ready');

  assert.equal(harness.context.window.TNADashboardStoryBridge.isReady(), true);
  assert.deepEqual(harness.calls.filter((name) => name === 'dashboard:feature-toggle'), ['dashboard:feature-toggle']);
});

test('dashboard bridge becomes ready when loaded after TNAStoryEngine is already ready', () => {
  const harness = createBridgeHarness({ engineReady: true });

  assert.equal(harness.context.window.TNADashboardStoryBridge.isReady(), true);
});

test('dashboard bridge polling flushes once when API readiness appears later', () => {
  const harness = createBridgeHarness();

  assert.equal(harness.context.window.TNADashboardStoryBridge.isReady(), false);
  harness.setEngineReady(true);
  harness.runNextInterval();
  assert.equal(harness.context.window.TNADashboardStoryBridge.isReady(), true);
  harness.fire('dashboard:feature-toggle', { controlId: 'c02' });
  assert.equal(harness.calls.filter((name) => name === 'dashboard:feature-toggle').length, 0);
});
