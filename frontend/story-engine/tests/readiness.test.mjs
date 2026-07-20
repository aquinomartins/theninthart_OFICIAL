import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const componentSource = fs.readFileSync(new URL('../src/app/story-engine.component.ts', import.meta.url), 'utf8');
const bridgeSource = fs.readFileSync(new URL('../../../scripts/dashboard-story-bridge.js', import.meta.url), 'utf8');

test('Story Engine waits for the rendered 29 panels before emitting ready once', () => {
  assert.match(componentSource, /private readyEventEmitted=false/);
  assert.match(componentSource, /root\.querySelectorAll\('\[data-story-panel\]'\)\.length===29/);
  assert.match(componentSource, /root\.querySelector\('\[data-quadrant-id="q01"\]'\)/);
  assert.match(componentSource, /root\.querySelector\('\[data-quadrant-id="q29"\]'\)/);
  assert.match(componentSource, /requestAnimationFrame\(\(\)=>this\.announceReadyWhenRendered/);
  assert.match(componentSource, /if\(this\.readyEventEmitted\)return/);
  assert.match(componentSource, /this\.readyEventEmitted=true/);
  assert.match(componentSource, /this\.events\.emit\('tna:story-engine-ready'/);
  assert.doesNotMatch(componentSource, /throw new Error\('READY_DOM_INVALID'\)/);
  assert.doesNotMatch(componentSource, /queueMicrotask\(\(\).*READY_DOM_INVALID/s);
});

test('Story Engine reports READY_DOM_INVALID only after the frame attempt limit', () => {
  assert.match(componentSource, /if\(attempt>=60\)\{this\.fail\(new Error\('READY_DOM_INVALID'\),'ready-dom',indexPath\);return\}/);
  assert.match(componentSource, /this\.announceReadyWhenRendered\(m,a\.version,a\.index\)/);
  assert.match(componentSource, /this\.readyEventEmitted=false;this\.state\.setLoading/);
  assert.match(componentSource, /reloadManifests:\(\)=>this\.init\(\)/);
});

function createWindow({ engineReady = false } = {}) {
  const listeners = new Map();
  let nextTimer = 1;
  const intervals = new Map();
  const timeouts = new Map();
  const dispatched = [];

  class CustomEvent {
    constructor(type, options = {}) {
      this.type = type;
      this.detail = options.detail;
    }
  }

  const window = {
    __intervals: intervals,
    __timeouts: timeouts,
    __dispatched: dispatched,
    TNAStoryEngine: { isReady: () => engineReady },
    TNADashboardHub: {
      getStateSnapshot: () => ({ controls: { example: true } }),
      applyExternalState: () => undefined,
    },
    addEventListener(type, listener) {
      const list = listeners.get(type) || [];
      list.push(listener);
      listeners.set(type, list);
    },
    removeEventListener(type, listener) {
      listeners.set(type, (listeners.get(type) || []).filter(item => item !== listener));
    },
    dispatchEvent(event) {
      dispatched.push(event);
      for (const listener of [...(listeners.get(event.type) || [])]) {
        listener(event);
      }
      return true;
    },
    setInterval(callback, delay) {
      const id = nextTimer++;
      intervals.set(id, { callback, delay });
      return id;
    },
    clearInterval(id) {
      intervals.delete(id);
    },
    setTimeout(callback, delay) {
      const id = nextTimer++;
      timeouts.set(id, { callback, delay });
      return id;
    },
    clearTimeout(id) {
      timeouts.delete(id);
    },
    CustomEvent,
  };

  const document = {
    readyState: 'complete',
    querySelector(selector) {
      if (selector === '[data-dashboard-hub]' || selector === 'tna-story-engine') {
        return {};
      }
      return null;
    },
    addEventListener: window.addEventListener,
    removeEventListener: window.removeEventListener,
  };

  const context = vm.createContext({ window, document, CustomEvent, console });
  vm.runInContext(bridgeSource, context);
  return { window, setEngineReady(value) { engineReady = value; } };
}

test('Dashboard bridge becomes ready from the ready event without recursive redispatch', () => {
  const { window } = createWindow();

  assert.equal(window.TNADashboardStoryBridge.isReady(), false);
  window.dispatchEvent(new window.CustomEvent('dashboard:feature-toggle', { detail: { id: 'c01' } }));
  window.dispatchEvent(new window.CustomEvent('tna:story-engine-ready', { detail: { source: 'angular' } }));

  assert.equal(window.TNADashboardStoryBridge.isReady(), true);
  const dashboardEvents = window.__dispatched.filter(event => event.type === 'dashboard:feature-toggle');
  assert.equal(dashboardEvents.length, 2);
});

test('Dashboard bridge flushes when the global API is already ready', () => {
  const { window } = createWindow({ engineReady: true });

  assert.equal(window.TNADashboardStoryBridge.isReady(), true);
  assert.equal(window.__intervals.size, 0);
});

test('Dashboard bridge polls the global API, clears timers on ready, and destroy removes listeners and timers', () => {
  const harness = createWindow();
  const { window } = harness;

  assert.equal(window.TNADashboardStoryBridge.isReady(), false);
  assert.equal(window.__intervals.size, 1);

  const [id, interval] = [...window.__intervals.entries()][0];
  harness.setEngineReady(true);
  interval.callback();

  assert.equal(window.TNADashboardStoryBridge.isReady(), true);
  assert.equal(window.__intervals.has(id), false);

  window.TNADashboardStoryBridge.destroy();
  assert.equal(window.TNADashboardStoryBridge.isReady(), false);
  assert.equal(window.__intervals.size, 0);

  window.dispatchEvent(new window.CustomEvent('tna:story-engine-ready'));
  assert.equal(window.TNADashboardStoryBridge.isReady(), false);
});
