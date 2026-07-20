(function () {
  'use strict';

  if (window.__TNADashboardStoryBridge) {
    return;
  }

  window.__TNADashboardStoryBridge = true;

  const MAX_QUEUE = 40;
  const EVENTS = [
    'dashboard:state-snapshot',
    'dashboard:feature-toggle',
    'dashboard:parameter-change',
    'dashboard:widget-save',
    'dashboard:widget-reset',
  ];

  let ready = false;
  let queue = [];
  let replaying = false;
  let readinessTimer = null;
  let readinessAttempts = 0;

  function enqueue(name, detail) {
    if (name === 'dashboard:state-snapshot') {
      queue = queue.filter(([queuedName]) => queuedName !== name);
    }

    queue.push([name, detail]);

    if (queue.length > MAX_QUEUE) {
      queue.shift();
    }
  }

  function dispatchToAngular(name, detail) {
    replaying = true;

    try {
      window.dispatchEvent(new CustomEvent(name, { detail }));
    } finally {
      replaying = false;
    }
  }

  function snapshot() {
    const api = window.TNADashboardHub;

    if (!api?.getStateSnapshot) {
      return;
    }

    const detail = api.getStateSnapshot();

    if (ready) {
      dispatchToAngular('dashboard:state-snapshot', detail);
    } else {
      enqueue('dashboard:state-snapshot', detail);
    }
  }

  function forward(event) {
    if (replaying || event.detail?.source === 'angular') {
      return;
    }

    if (!ready) {
      enqueue(event.type, event.detail || {});
    }

    // Quando o Angular já está pronto, o evento original já está no window
    // e será recebido diretamente por ele. Não o redispare aqui.
  }

  function engineIsReady() {
    return Boolean(
      window.TNAStoryEngine &&
        typeof window.TNAStoryEngine.isReady === 'function' &&
        window.TNAStoryEngine.isReady()
    );
  }

  function stopReadinessWatch() {
    if (readinessTimer !== null) {
      window.clearInterval(readinessTimer);
      readinessTimer = null;
    }
  }

  function watchEngineReadiness() {
    stopReadinessWatch();

    if (engineIsReady()) {
      flush();
      return;
    }

    readinessAttempts = 0;

    readinessTimer = window.setInterval(() => {
      readinessAttempts += 1;

      if (engineIsReady()) {
        stopReadinessWatch();
        flush();
        return;
      }

      if (readinessAttempts >= 40) {
        stopReadinessWatch();
      }
    }, 250);
  }

  function flush() {
    if (ready) {
      return;
    }

    stopReadinessWatch();
    ready = true;

    const pending = queue.splice(0);

    for (const [name, detail] of pending) {
      dispatchToAngular(name, detail);
    }

    const hadSnapshot = pending.some(
      ([name]) => name === 'dashboard:state-snapshot'
    );

    if (!hadSnapshot) {
      snapshot();
    }
  }

  function restore(event) {
    window.TNADashboardHub?.applyExternalState?.(event.detail, {
      emitEvents: false,
    });
  }

  function init() {
    if (
      !document.querySelector('[data-dashboard-hub]') ||
      !document.querySelector('tna-story-engine')
    ) {
      return;
    }

    for (const name of EVENTS) {
      window.addEventListener(name, forward, { passive: true });
    }

    window.addEventListener('tna:dashboard-restore-state', restore, {
      passive: true,
    });
    window.addEventListener('tna:story-engine-ready', flush, {
      passive: true,
    });

    watchEngineReadiness();

    window.setTimeout(snapshot, 250);
    window.setTimeout(snapshot, 1200);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  window.TNADashboardStoryBridge = {
    isReady: () => ready,
    destroy() {
      for (const name of EVENTS) {
        window.removeEventListener(name, forward);
      }

      window.removeEventListener('tna:dashboard-restore-state', restore);
      window.removeEventListener('tna:story-engine-ready', flush);

      stopReadinessWatch();
      queue = [];
      ready = false;
      replaying = false;
      readinessAttempts = 0;
      window.__TNADashboardStoryBridge = false;
    },
  };
})();
