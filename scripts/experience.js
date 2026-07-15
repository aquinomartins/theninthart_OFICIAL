import { assets } from './modules/asset-manifest.js';
import { createDetailCarousel } from './modules/carousel.js';
import { renderKitchen } from './modules/kitchen-renderer.js';
import { initScrollScenes } from './modules/scroll-scenes.js';
import { initAdminPanel } from './modules/admin-panel.js';
import { createSession, updateSession, localPersistence } from './modules/state-store.js';
import { createPublicSnapshot } from './modules/public-state.js';
import { createSeed, getDominantEra } from './modules/temporal-engine.js';

const qs = (selector, root = document) => root.querySelector(selector);
const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];

async function loadJson(path) {
  const response = await fetch(path);
  if (!response.ok) throw new Error(`Não foi possível carregar ${path}`);
  return response.json();
}

async function boot() {
  hydrateAssetImages();
  initFloatingNarrative();
  initScrollScenes();
  const dataset = { eras: await loadJson('data/eras.json'), parts: await loadJson('data/machine-parts.json'), objects: await loadJson('data/kitchen-objects.json'), panels: await loadJson('data/story-panels.json') };
  let publicSnapshot = await localPersistence.loadPublicSnapshot() || createPublicSnapshot(await localPersistence.loadSessions(), dataset.eras);
  let session = restoreFromUrl(dataset) || await localPersistence.loadCurrentSession() || createSession(dataset);
  const credentialRecord = await localPersistence.loadCredentialStatus();
  session = updateSession(session, dataset, { credentialStatus: credentialRecord?.status || session.credentialStatus || null });

  async function persistAndRender() { await localPersistence.saveSession(session); updateUrl(session); renderAll(); }
  function renderAll() {
    const dominant = getDominantEra(session.vector, dataset.eras);
    document.documentElement.style.setProperty('--temporal-accent', dataset.parts.find((part) => session.selectedParts.includes(part.id))?.accent || dominant.theme.accent || '#0a84ff');
    document.body.dataset.dominantEra = dominant.id;
    qs('[data-hero-subtitle]').textContent = heroLine(dominant, session);
    qs('[data-dominant-label]').textContent = `Estado dominante: ${dominant.label} · ${Math.round((session.vector[dominant.id] || 0) * 100)}%`;
    qs('[data-machine-summary]').textContent = dataset.parts.filter((part) => session.selectedParts.includes(part.id)).map((part) => part.summary).at(-1) || 'A máquina aguarda uma peça.';
    qs('[data-mechanism-copy]').textContent = `A montagem atual puxa a cozinha para ${dominant.label.toLowerCase()} sem apagar rastros das outras eras.`;
    qs('[data-vector-readout]').textContent = JSON.stringify(session.vector, null, 2);
    qs('[data-state-summary]').textContent = `Sua linha: ${session.selectedParts.length} peça(s), ${session.selectedObjects.length} objeto(s), dominante ${dominant.label}.`;
    qs('[data-public-pulse]').textContent = `Cozinha pública: ${Math.round(Math.max(...Object.values(publicSnapshot.vector)) * 100)}% em ${getDominantEra(publicSnapshot.vector, dataset.eras).label}.`;
    renderBars(qs('[data-bars="individual"]'), session.vector, dataset.eras);
    renderBars(qs('[data-bars="public"]'), publicSnapshot.vector, dataset.eras);
    renderObjectList(dataset, session);
    renderKitchen(qs('[data-kitchen-scene]'), qs('[data-kitchen-status]'), dataset, session, publicSnapshot, dominant, createSeed(publicSnapshot));
    renderCredentialState(session.credentialStatus);
    qsa('[data-toggle-tech]').forEach((button) => button.addEventListener('click', () => { const note = qs('[data-tech-note]'); const open = note.hasAttribute('hidden'); note.toggleAttribute('hidden', !open); button.setAttribute('aria-expanded', String(open)); }, { once: true }));
  }

  createDetailCarousel(qs('[data-machine-carousel]'), dataset.parts, (part, user) => {
    qs('[data-part-image]').src = assets[part.imageKey] || assets.mechanism;
    if (user && !session.selectedParts.includes(part.id)) {
      session = updateSession(session, dataset, { selectedParts: [...session.selectedParts, part.id].slice(-5) });
      persistAndRender();
    } else {
      renderAll();
    }
  });

  renderObjectList(dataset, session);
  bindEvents(dataset, () => session, (next) => { session = next; }, async () => { publicSnapshot = await refreshPublic(dataset); renderAll(); });
  initAdminPanel({ dialog: qs('[data-admin-dialog]'), dataset, persistence: localPersistence, onRestore: () => location.reload() });
  qs('[data-open-admin]')?.addEventListener('click', () => qs('[data-admin-dialog]').showModal());

  await persistAndRender();
}

function hydrateAssetImages() {
  qsa('[data-asset-key]').forEach((image) => {
    const source = assets[image.dataset.assetKey];
    if (source && image.getAttribute('src') !== source) image.src = source;
  });
}

function renderObjectList(dataset, session) {
  const root = qs('[data-object-list]');
  if (!root) return;
  const dominant = getDominantEra(session.vector, dataset.eras);
  root.innerHTML = dataset.objects.map((object) => {
    const version = object.versions.find((item) => item.eraId === dominant.id) || object.versions[0];
    const selected = session.selectedObjects.includes(object.id);
    return `<article class="object-card ${selected ? 'is-selected' : ''}"><div class="object-thumb" data-slot="kitchen-object" data-object-id="${object.id}" data-era-id="${version.eraId}">${object.name.slice(0, 2)}</div><h3>${object.name}</h3><p>${object.function}</p><p><strong>${version.label}</strong></p><button type="button" data-toggle-object="${object.id}" aria-pressed="${selected}">${selected ? 'Remover' : 'Selecionar'}</button></article>`;
  }).join('');
}

function bindEvents(dataset, getSession, setSession, refresh) {
  document.addEventListener('click', async (event) => {
    if (event.target.closest('[data-credential-login]')) {
      const next = updateSession(getSession(), dataset, { credentialStatus: 'authenticated' });
      setSession(next);
      await localPersistence.saveCredentialStatus('authenticated');
      await localPersistence.saveSession(next);
      updateUrl(next);
      renderCredentialState('authenticated', { confirming: true });
      window.setTimeout(() => closeCredentialDialog({ keepReopen: false }), 780);
      return;
    }
    if (event.target.closest('[data-credential-dismiss]')) {
      const next = updateSession(getSession(), dataset, { credentialStatus: 'dismissed' });
      setSession(next);
      await localPersistence.saveCredentialStatus('dismissed');
      await localPersistence.saveSession(next);
      closeCredentialDialog({ keepReopen: true });
      return;
    }
    if (event.target.closest('[data-credential-reopen]')) {
      openCredentialDialog();
      return;
    }
    const toggle = event.target.closest('[data-toggle-object]');
    if (toggle) {
      const session = getSession(); const id = toggle.dataset.toggleObject;
      const selectedObjects = session.selectedObjects.includes(id) ? session.selectedObjects.filter((item) => item !== id) : [...session.selectedObjects, id];
      setSession(updateSession(session, dataset, { selectedObjects }));
      pulseNarrative('object');
      await localPersistence.saveSession(getSession()); updateUrl(getSession()); refresh();
    }
    if (event.target.closest('[data-complete-session]')) {
      setSession({ ...getSession(), completed: true, updatedAt: new Date().toISOString() });
      await localPersistence.saveSession(getSession()); await refresh();
    }
    if (event.target.closest('[data-reset-session]')) {
      setSession(createSession(dataset)); await localPersistence.saveSession(getSession()); updateUrl(getSession()); document.getElementById('machine')?.scrollIntoView({ behavior: 'smooth' }); await refresh();
    }
  });
}

async function refreshPublic(dataset) {
  const snapshot = createPublicSnapshot(await localPersistence.loadSessions(), dataset.eras);
  await localPersistence.savePublicSnapshot(snapshot);
  return snapshot;
}


function renderCredentialState(status, options = {}) {
  const dialog = qs('[data-credential-dialog]');
  const reopen = qs('[data-credential-reopen]');
  const timeline = qs('[data-individual-timeline]');
  const caption = qs('[data-credential-caption]');
  if (!dialog || !reopen || !timeline || !caption) return;
  timeline.classList.toggle('is-credential-authenticated', status === 'authenticated');
  if (options.confirming) {
    timeline.classList.add('is-confirming');
    window.setTimeout(() => timeline.classList.remove('is-confirming'), 820);
  }
  caption.textContent = status === 'authenticated' ? 'Acesso temporal restabelecido' : 'Interferência aguardando credenciais.';
  if (status === 'authenticated') {
    if (options.confirming) dialog.classList.add('is-visible'); else dialog.classList.remove('is-visible', 'is-closing');
    reopen.classList.remove('is-visible');
    return;
  }
  if (status === 'dismissed') {
    dialog.classList.remove('is-visible');
    reopen.classList.add('is-visible');
    return;
  }
  dialog.classList.add('is-visible');
  reopen.classList.remove('is-visible');
}

function openCredentialDialog() {
  qs('[data-credential-dialog]')?.classList.remove('is-closing');
  qs('[data-credential-dialog]')?.classList.add('is-visible');
  qs('[data-credential-reopen]')?.classList.remove('is-visible');
}

function closeCredentialDialog({ keepReopen }) {
  const dialog = qs('[data-credential-dialog]');
  const reopen = qs('[data-credential-reopen]');
  if (!dialog || !reopen) return;
  dialog.classList.add('is-closing');
  dialog.classList.remove('is-visible');
  window.setTimeout(() => {
    dialog.classList.remove('is-closing');
    reopen.classList.toggle('is-visible', keepReopen);
  }, 280);
}

function renderBars(root, vector, eras) {
  if (!root) return;
  root.innerHTML = eras.map((era) => `<div data-era="${era.id}"><span>${era.label}</span><i style="transform:scaleX(${vector[era.id] || 0})"></i><b>${Math.round((vector[era.id] || 0) * 100)}%</b></div>`).join('');
}

function heroLine(era, session) {
  const object = session.selectedObjects.at(-1) || 'forma';
  return era.id === 'past' ? `O ${object} lembra o bolo antes da chegada.` : era.id === 'future' ? `O ${object} chegou de uma fornada que ainda não aconteceu.` : `O ${object} mantém o presente aberto por alguns quadros.`;
}

function updateUrl(session) {
  const params = new URLSearchParams(location.search);
  params.set('parts', session.selectedParts.join(',')); params.set('objects', session.selectedObjects.join(','));
  history.replaceState(null, '', `${location.pathname}?${params.toString()}${location.hash}`);
}

function restoreFromUrl(dataset) {
  const params = new URLSearchParams(location.search);
  if (!params.has('parts') && !params.has('objects')) return null;
  const parts = (params.get('parts') || '').split(',').filter((id) => dataset.parts.some((part) => part.id === id));
  const objects = (params.get('objects') || '').split(',').filter((id) => dataset.objects.some((object) => object.id === id));
  return createSession(dataset, { parts: parts.length ? parts : ['sequence'], objects: objects.length ? objects : ['forma'] });
}

boot().catch((error) => { console.error(error); document.body.insertAdjacentHTML('afterbegin', '<p role="alert" style="padding:1rem;background:#401;color:white">A experiência carregou em modo básico porque um recurso falhou.</p>'); });


function initFloatingNarrative() {
  const flyer = qs('[data-floating-narrative]');
  const layer = flyer?.closest('.floating-narrative-layer');
  const kitchen = qs('[data-kitchen-scene]');
  if (!flyer || !layer || !kitchen) return;

  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const state = {
    mode: 'flying',
    busy: false,
    savedScroll: 0,
    lastPose: { x: 0, y: 0, rotation: -8, scale: 1 },
    startTime: performance.now(),
    width: 0,
    height: 0
  };

  const setPose = ({ x, y, rotation = -8, scale = 1, opacity = 0.88 }) => {
    state.lastPose = { x, y, rotation, scale };
    flyer.style.setProperty('--narrative-x', `${x}px`);
    flyer.style.setProperty('--narrative-y', `${y}px`);
    flyer.style.setProperty('--narrative-rotation', `${rotation}deg`);
    flyer.style.setProperty('--narrative-scale', scale.toFixed(3));
    flyer.style.opacity = opacity.toFixed(3);
  };

  const organicPose = (now) => {
    const maxScroll = Math.max(1, document.documentElement.scrollHeight - innerHeight);
    const progress = scrollY / maxScroll;
    const box = flyer.getBoundingClientRect();
    const width = box.width || state.width || 160;
    const height = box.height || state.height || 160;
    state.width = width; state.height = height;
    const t = (now - state.startTime) / 1000;
    const mobile = matchMedia('(max-width: 767px)').matches;
    const baseX = mobile ? innerWidth * (0.62 + progress * 0.16) : innerWidth * (0.08 + progress * 0.64);
    const baseY = mobile ? innerHeight * (0.09 + progress * 0.78) : innerHeight * (0.14 + progress * 0.62);
    const driftX = Math.sin(t * 0.37) * innerWidth * 0.035 + Math.sin(t * 0.113 + 1.8) * innerWidth * 0.026;
    const driftY = Math.cos(t * 0.29 + 0.7) * innerHeight * 0.032 + Math.sin(t * 0.071) * innerHeight * 0.025;
    return {
      x: Math.min(innerWidth - width - 12, Math.max(12, baseX + driftX)),
      y: Math.min(innerHeight - height - 12, Math.max(12, baseY + driftY)),
      rotation: -6 + Math.sin(t * 0.23) * 7 + Math.cos(t * 0.097) * 3,
      scale: 1 + Math.sin(t * 0.17) * 0.025,
      opacity: mobile ? 0.62 : 0.88
    };
  };

  const tick = (now) => {
    if (state.mode === 'flying' && !state.busy) setPose(organicPose(now));
    requestAnimationFrame(tick);
  };

  const animatePortal = (direction) => {
    if (reduceMotion.matches) return Promise.resolve();
    const sign = direction === 'out' ? 1 : -1;
    const duration = direction === 'out' ? 640 : 720;
    const opacity = direction === 'out' ? [0.88, 0.66, 0] : [0, 0.64, 0.88];
    const scale = direction === 'out' ? [1, 0.72, 0.18] : [0.18, 0.72, 1];
    const frames = [
      { transform: `translate3d(var(--narrative-x),var(--narrative-y),0) translate(0,0) rotate(${state.lastPose.rotation}deg) scale(${scale[0]})`, opacity: opacity[0] },
      { transform: `translate3d(var(--narrative-x),var(--narrative-y),0) translate(${18 * sign}px,${-16 * sign}px) rotate(${state.lastPose.rotation + 115 * sign}deg) scale(${scale[1]})`, opacity: opacity[1], offset: 0.58 },
      { transform: `translate3d(var(--narrative-x),var(--narrative-y),0) translate(0,0) rotate(${state.lastPose.rotation + 360 * sign}deg) scale(${scale[2]})`, opacity: opacity[2] }
    ];
    return flyer.animate(frames, { duration, easing: 'cubic-bezier(.22,1,.36,1)', fill: 'forwards' }).finished;
  };

  const dockPose = () => {
    const rect = kitchen.getBoundingClientRect();
    const box = flyer.getBoundingClientRect();
    return { x: rect.left + rect.width * 0.68 - box.width / 2, y: rect.top + rect.height * 0.28 - box.height / 2, rotation: -4, scale: 0.92, opacity: 0.9 };
  };

  const scrollToY = (top) => new Promise((resolve) => {
    scrollTo({ top, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
    if (reduceMotion.matches) { resolve(); return; }
    let stable = 0;
    const check = () => {
      stable = Math.abs(scrollY - top) < 2 ? stable + 1 : 0;
      if (stable > 4) resolve(); else requestAnimationFrame(check);
    };
    check();
  });

  const jump = async () => {
    if (state.busy) return;
    state.busy = true;
    layer.classList.add('is-transitioning');
    flyer.setAttribute('aria-busy', 'true');
    const goingToKitchen = state.mode === 'flying';
    if (goingToKitchen) state.savedScroll = scrollY;
    await animatePortal('out').catch(() => {});
    const target = goingToKitchen ? kitchen.getBoundingClientRect().top + scrollY - Math.max(72, innerHeight * 0.12) : state.savedScroll;
    await scrollToY(Math.max(0, target));
    setPose(goingToKitchen ? dockPose() : organicPose(performance.now()));
    await animatePortal('in').catch(() => {});
    state.mode = goingToKitchen ? 'docked' : 'flying';
    layer.classList.toggle('is-docked', state.mode === 'docked');
    flyer.setAttribute('aria-pressed', String(state.mode === 'docked'));
    flyer.alt = state.mode === 'docked' ? 'Navegador temporal: voltar ao ponto anterior' : 'Navegador temporal: ir para a cozinha pública';
    flyer.removeAttribute('aria-busy');
    layer.classList.remove('is-transitioning');
    state.busy = false;
  };

  flyer.addEventListener('click', jump);
  flyer.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      jump();
    }
  });
  requestAnimationFrame(tick);
}

function pulseNarrative(reason = 'state') {
  document.body.dataset.narrativePulse = reason;
  window.clearTimeout(pulseNarrative.timer);
  pulseNarrative.timer = window.setTimeout(() => { delete document.body.dataset.narrativePulse; }, 900);
}