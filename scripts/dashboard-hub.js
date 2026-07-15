import { dashboardHubDemoData, IS_DEMO_DATA } from '../data/dashboard-hub-demo.js';

const state = { shortcuts: [], widgets: [], query: '', status: 'idle', userId: null };
const selectors = {
  root: '[data-dashboard-hub]', search: '[data-dashboard-search]', shortcutGrid: '[data-shortcut-grid]', widgetSection: '[data-widget-section]', widgetGrid: '[data-widget-grid]', toggle: '[data-widget-toggle]', menu: '[data-widget-menu]', live: '[data-dashboard-live]'
};
const widgetRenderers = { comparison: renderComparisonWidget, list: renderListWidget, timer: renderTimerWidget, tasks: renderTasksWidget };

async function loadDashboardHubData() {
  await new Promise((resolve) => window.setTimeout(resolve, 120));
  return typeof structuredClone === 'function' ? structuredClone(dashboardHubDemoData) : JSON.parse(JSON.stringify(dashboardHubDemoData));
}
async function saveDashboardInteraction(payload) {
  window.dispatchEvent(new CustomEvent(payload.eventName || 'dashboard:widget-action', { detail: payload.detail }));
  return { ok: true, demo: IS_DEMO_DATA };
}

const normalize = (value) => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim().replace(/\s+/g, ' ');
const debounce = (fn, wait = 150) => { let timer; return (...args) => { window.clearTimeout(timer); timer = window.setTimeout(() => fn(...args), wait); }; };
const isSafeUrl = (url) => { if (!url || url === '#') return false; try { const parsed = new URL(url, window.location.href); return ['http:', 'https:', 'mailto:'].includes(parsed.protocol) || parsed.origin === window.location.origin; } catch { return false; } };
const emit = (eventName, detail) => saveDashboardInteraction({ eventName, detail: { ...detail, timestamp: new Date().toISOString() } });

function text(value) { return document.createTextNode(String(value ?? '')); }
function el(tag, className, attrs = {}) { const node = document.createElement(tag); if (className) node.className = className; Object.entries(attrs).forEach(([key, value]) => { if (value === null || value === undefined || value === false) return; if (key === 'text') node.textContent = value; else if (key === 'dataset') Object.assign(node.dataset, value); else node.setAttribute(key, value === true ? '' : value); }); return node; }
function initials(title) { return String(title || '?').split(/\s+/).slice(0,2).map((part) => part[0]).join('').toUpperCase(); }

function applyShortcutIcon({ imageElement, placeholderElement, iconUrl, alt }) {
  if (!iconUrl || !isSafeUrl(iconUrl)) { imageElement.removeAttribute('src'); imageElement.hidden = true; placeholderElement.hidden = false; return; }
  imageElement.onload = () => { imageElement.hidden = false; placeholderElement.hidden = true; };
  imageElement.onerror = () => { imageElement.removeAttribute('src'); imageElement.hidden = true; placeholderElement.hidden = false; };
  imageElement.alt = alt || '';
  imageElement.loading = 'lazy';
  imageElement.src = iconUrl;
}

function createShortcut(shortcut) {
  const enabled = shortcut.status !== 'disabled' && shortcut.status !== 'unavailable';
  const tag = isSafeUrl(shortcut.href) && enabled ? 'a' : 'button';
  const item = el(tag, 'shortcut-item', { 'data-shortcut-item': true, 'data-shortcut-id': shortcut.id, 'aria-label': `Abrir ${shortcut.title}` });
  if (tag === 'a') { item.href = shortcut.href; item.target = shortcut.target || '_self'; if (item.target === '_blank') item.rel = 'noopener noreferrer'; } else item.type = 'button';
  if (!enabled) item.setAttribute('aria-disabled', 'true');
  item.dataset.status = shortcut.status || 'active';
  if (shortcut.isFavorite) item.dataset.favorite = 'true';
  const frame = el('span', 'shortcut-item__icon-frame');
  const placeholder = el('span', 'shortcut-item__icon-placeholder', { 'data-icon-slot': true, 'aria-hidden': 'true', text: initials(shortcut.shortTitle || shortcut.title) });
  const img = el('img', 'shortcut-item__icon', { 'data-shortcut-icon': true, alt: shortcut.iconAlt || '', width: 38, height: 38 });
  img.hidden = true;
  frame.append(placeholder, img);
  const title = el('span', 'shortcut-item__title', { text: shortcut.shortTitle || shortcut.title });
  const badge = el('span', 'shortcut-item__badge', { 'data-shortcut-badge': true, text: shortcut.badge });
  badge.hidden = !shortcut.badge;
  item.append(frame, title, badge);
  if (shortcut.status === 'loading') frame.classList.add('skeleton');
  applyShortcutIcon({ imageElement: img, placeholderElement: placeholder, iconUrl: shortcut.iconUrl, alt: shortcut.iconAlt });
  return item;
}

function baseWidget(widget) { return el('article', 'widget-card', { 'data-widget-card': true, 'data-widget-id': widget.id, 'data-widget-type': widget.type, tabindex: '0' }); }
function appendWidgetTitle(card, widget) { const h = el('h3', '', { text: widget.title || 'Widget' }); card.append(h); if (widget.subtitle) card.append(el('div', 'widget-meta', { text: widget.subtitle })); }
function renderComparisonWidget(widget) { const card = baseWidget(widget); appendWidgetTitle(card, widget); const tabs = el('div','widget-tabs'); (widget.data.tabs || []).forEach((tab) => tabs.append(el('button','widget-tab',{ type:'button', text:tab, 'aria-pressed': tab === widget.data.activeTab }))); card.append(tabs); const body = el('div','comparison-body'); (widget.data.participants || []).slice(0,2).forEach((p, i) => { if (i) body.append(el('strong','', { text: '×' })); const part = el('div','participant'); part.append(el('span','participant-mark',{ text: initials(p.name) }), el('strong','',{ text:p.name }), el('small','widget-meta',{ text:p.detail })); body.append(part); }); card.append(body, el('div','widget-meta',{ text: `${widget.data.status || ''} · ${widget.data.time || ''} · ${widget.data.date || ''}` }), el('button','widget-button',{ type:'button', 'data-widget-action':'view-all', text:'Ver tudo' })); return card; }
function renderListWidget(widget) { const card = baseWidget(widget); appendWidgetTitle(card, widget); const list = el('ul','widget-list'); (widget.data.items || []).forEach((item) => { const row = el('li','widget-row'); const left = el('span'); left.append(text(item.label), el('small','',{ text:item.secondary || '' })); row.append(left, el('strong','',{ text:item.value || '' })); list.append(row); }); card.append(list); return card; }
function renderTimerWidget(widget) { const card = baseWidget(widget); appendWidgetTitle(card, widget); const face = el('div','timer-face'); face.style.setProperty('--progress', Number(widget.data.progress || 0)); face.append(el('span','',{ text:widget.data.remaining || '00:00' })); const actions = el('div','timer-actions'); actions.append(el('button','widget-button',{ type:'button','data-widget-action':'decrease', text:'−' }), el('button','widget-button',{ type:'button','data-widget-action':'start', text:'Iniciar' }), el('button','widget-button',{ type:'button','data-widget-action':'pause', text:'Pausar' }), el('button','widget-button',{ type:'button','data-widget-action':'increase', text:'+' })); card.append(face, el('div','widget-meta',{ text:`${widget.data.mode || 'Modo'} · ${widget.data.duration || 0} min · ${widget.data.state || 'idle'}` }), actions); return card; }
function renderTasksWidget(widget) { const card = baseWidget(widget); const header = el('div','task-header'); header.append(el('h3','',{ text:widget.title || 'Tarefas' }), el('button','widget-button',{ type:'button','data-widget-action':'add-task','aria-label':'Adicionar tarefa', text:'+' })); card.append(header); const items = widget.data.items || []; if (!items.length) card.append(el('p','widget-meta',{ text:'Nenhuma tarefa disponível ainda.' })); const list = el('ul','task-list'); items.forEach((task) => { const row = el('li','task-item'); if (task.completed) row.classList.add('is-complete'); row.append(el('span','task-check',{ 'aria-hidden':'true' })); const body = el('span'); body.append(text(task.title), el('div','task-meta',{ text:`${task.priority || 'normal'} · ${task.assignee || 'sem responsável'} · ${task.dueDate || 'sem data'}` })); row.append(body); list.append(row); }); card.append(list); return card; }
function renderGenericWidget(widget) { const card = baseWidget(widget); appendWidgetTitle(card, widget); card.append(el('p','widget-meta',{ text:'Tipo de widget ainda não registrado.' })); return card; }
function renderWidgetError(widget, error) { console.error('[Dashboard widget]', { widgetId: widget.id, type: widget.type, error }); const card = baseWidget(widget); appendWidgetTitle(card, widget); card.append(el('p','widget-meta',{ text:'Não foi possível carregar este widget.' })); return card; }
function renderWidget(widget) { try { return (widgetRenderers[widget.type] || renderGenericWidget)(widget); } catch (error) { return renderWidgetError(widget, error); } }

function searchableShortcut(item) { return normalize([item.title,item.shortTitle,item.description,item.category,...(item.metadata?.tags || [])].join(' ')); }
function searchableWidget(item) { return normalize([item.title,item.subtitle,JSON.stringify(item.data || {}),...(item.metadata?.tags || [])].join(' ')); }
function filterData() { const q = normalize(state.query); if (!q) return { shortcuts: state.shortcuts, widgets: state.widgets }; return { shortcuts: state.shortcuts.filter((s) => searchableShortcut(s).includes(q)), widgets: state.widgets.filter((w) => searchableWidget(w).includes(q)) }; }

function render(root) {
  const shortcutGrid = root.querySelector(selectors.shortcutGrid), widgetGrid = root.querySelector(selectors.widgetGrid), live = root.querySelector(selectors.live);
  const { shortcuts, widgets } = filterData();
  shortcutGrid.replaceChildren(); widgetGrid.replaceChildren();
  if (!shortcuts.length && !widgets.length) { live.textContent = state.query ? 'Nenhum item corresponde à pesquisa.' : 'Nenhum item disponível ainda.'; live.hidden = false; } else { live.textContent = `${shortcuts.length} atalhos e ${widgets.length} widgets disponíveis.`; live.hidden = true; }
  const shortcutFrag = document.createDocumentFragment(); shortcuts.sort((a,b)=>(a.order||0)-(b.order||0)).forEach((s) => shortcutFrag.append(createShortcut(s))); shortcutGrid.append(shortcutFrag);
  const widgetFrag = document.createDocumentFragment(); widgets.sort((a,b)=>(a.order||0)-(b.order||0)).forEach((w) => widgetFrag.append(renderWidget(w))); widgetGrid.append(widgetFrag);
  root.dataset.state = shortcuts.length || widgets.length ? 'ready' : 'empty'; root.setAttribute('aria-busy','false'); widgetGrid.style.maxHeight = widgetGrid.scrollHeight + 'px';
}

function renderSkeleton(root) {
  root.setAttribute('aria-busy','true'); root.dataset.state = 'loading';
  const shortcutGrid = root.querySelector(selectors.shortcutGrid), widgetGrid = root.querySelector(selectors.widgetGrid);
  shortcutGrid.replaceChildren(...Array.from({length:16},(_,i)=>{ const s = createShortcut({ id:`loading-${i}`, title:'Carregando', status:'loading' }); s.setAttribute('aria-hidden','true'); return s; }));
  widgetGrid.replaceChildren(...Array.from({length:4},(_,i)=>{ const card = el('article','widget-card skeleton',{ 'data-widget-card':true, 'aria-hidden':'true' }); card.append(el('h3','',{ text:'Carregando' })); return card; }));
}

async function initDashboardHub() {
  const root = document.querySelector(selectors.root); if (!root) return;
  const search = root.querySelector(selectors.search), input = search?.querySelector('input'), widgetSection = root.querySelector(selectors.widgetSection), widgetGrid = root.querySelector(selectors.widgetGrid), toggle = root.querySelector(selectors.toggle);
  renderSkeleton(root);
  try { const [dataResult] = await Promise.allSettled([loadDashboardHubData()]); if (dataResult.status !== 'fulfilled') throw dataResult.reason; state.shortcuts = Array.isArray(dataResult.value.shortcuts) ? dataResult.value.shortcuts : []; state.widgets = Array.isArray(dataResult.value.widgets) ? dataResult.value.widgets : []; render(root); } catch (error) { console.error('[Dashboard hub]', error); root.dataset.state = 'error'; root.setAttribute('aria-busy','false'); const live = root.querySelector(selectors.live); live.textContent = 'Não foi possível carregar esta área.'; live.hidden = false; }
  const onSearch = debounce(() => { state.query = input.value; emit('dashboard:search', { query: state.query, userId: state.userId }); render(root); }, 150);
  input?.addEventListener('input', onSearch); search?.addEventListener('submit', (event) => { event.preventDefault(); onSearch(); });
  root.addEventListener('click', (event) => { const shortcut = event.target.closest('[data-shortcut-item]'); if (shortcut) { const id = shortcut.dataset.shortcutId; if (shortcut.getAttribute('aria-disabled') === 'true') { event.preventDefault(); return; } shortcut.classList.add('is-selected'); window.setTimeout(() => shortcut.classList.remove('is-selected'), 180); emit('dashboard:shortcut-select', { shortcutId: id, userId: state.userId }); if (shortcut.tagName !== 'A') event.preventDefault(); } const action = event.target.closest('[data-widget-action]'); if (action) { const card = action.closest('[data-widget-card]'); const eventName = action.dataset.widgetAction === 'add-task' ? 'dashboard:task-create' : action.dataset.widgetAction === 'start' ? 'dashboard:timer-start' : 'dashboard:widget-action'; emit(eventName, { widgetId: card?.dataset.widgetId, action: action.dataset.widgetAction, userId: state.userId }); } });
  const preferred = sessionStorage.getItem('dashboard-widgets-expanded'); if (preferred === 'false') setExpanded(false);
  function setExpanded(expanded) { widgetSection.classList.toggle('is-collapsed', !expanded); toggle.setAttribute('aria-expanded', String(expanded)); toggle.setAttribute('aria-label', expanded ? 'Recolher widgets' : 'Expandir widgets'); sessionStorage.setItem('dashboard-widgets-expanded', String(expanded)); widgetGrid.style.maxHeight = expanded ? widgetGrid.scrollHeight + 'px' : '0px'; emit('dashboard:widgets-toggle', { action: expanded ? 'expand' : 'collapse', userId: state.userId }); }
  toggle?.addEventListener('click', () => setExpanded(toggle.getAttribute('aria-expanded') !== 'true'));
  root.querySelector(selectors.menu)?.addEventListener('click', () => emit('dashboard:widget-action', { action:'open-menu', userId: state.userId }));
}

document.addEventListener('DOMContentLoaded', initDashboardHub);
