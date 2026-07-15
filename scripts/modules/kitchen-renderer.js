import { getClosestVersion } from './temporal-engine.js';
import { assets } from './asset-manifest.js';

const layers = ['background','architecture','surfaces','ambient-light','characters','objects-behind','objects-main','objects-front','speech-bubbles','temporal-effects','ui-hotspots'];

export function ensureKitchenLayers(scene) {
  layers.forEach((layer) => {
    if (!scene.querySelector(`[data-layer="${layer}"]`)) {
      const node = document.createElement('div'); node.dataset.layer = layer; node.className = `kitchen-layer kitchen-layer--${layer}`; scene.append(node);
    }
  });
}

export function renderKitchen(scene, status, dataset, session, publicSnapshot, dominantEra, seed = 0) {
  ensureKitchenLayers(scene);
  scene.dataset.era = dominantEra.id;
  scene.dataset.selectionCount = session.selectedObjects.length;
  renderKitchenBase(scene, dominantEra);
  const selected = session.selectedObjects.map((id) => dataset.objects.find((object) => object.id === id)).filter(Boolean);
  layers.filter((layer) => layer.startsWith('objects')).forEach((layer) => { scene.querySelector(`[data-layer="${layer}"]`).innerHTML = ''; });
  selected.forEach((object, index) => {
    const version = getClosestVersion(object, dominantEra.id, dataset.eras);
    const layer = scene.querySelector(`[data-layer="${version.layer || 'objects-main'}"]`);
    layer?.insertAdjacentHTML('beforeend', objectMarkup(object, version, index, seed));
  });
  const bubbles = scene.querySelector('[data-layer="speech-bubbles"]');
  bubbles.innerHTML = `<p class="speech speech--hagia">Hagia: a sarjeta virou motor.</p><p class="speech speech--pio">Pio: ${selected.length ? 'isso acabou de mudar o quadro.' : 'eu já testei antes de começar.'}</p>`;
  const effects = scene.querySelector('[data-layer="temporal-effects"]');
  effects.innerHTML = '<span class="temporal-ring"></span><span class="temporal-grain"></span><span class="motion-lines"></span>';
  if (status) status.innerHTML = `<p class="kicker">Cozinha pública</p><h3>${dominantEra.label} dominante</h3><p>Minha escolha mudou a cozinha: ${selected.map((item) => item.name).join(', ') || 'nenhum objeto'} coexistem neste quadro.</p><button class="ghost-button" type="button" data-toggle-tech aria-expanded="false">Como isso funciona?</button><div class="technical-note" hidden data-tech-note>O sistema recalculou Vᵢ, combinou com o snapshot público local e escolheu a versão mais próxima de cada objeto pela ordem das eras.</div>`;
  scene.setAttribute('aria-label', `Cozinha temporal em ${dominantEra.label}, com ${selected.length} objetos selecionados.`);
}

function renderKitchenBase(scene, dominantEra) {
  const tint = dominantEra.id === 'past' ? 'kitchen-tint--past' : dominantEra.id === 'future' ? 'kitchen-tint--future' : 'kitchen-tint--present';
  scene.querySelector('[data-layer="background"]').innerHTML = `<img class="kitchen-plate kitchen-plate--bg ${tint}" src="${assets.kitchenBackground}" alt="" loading="lazy" decoding="async">`;
  scene.querySelector('[data-layer="architecture"]').innerHTML = `<img class="kitchen-plate kitchen-plate--architecture" src="${assets.kitchenArchitecture}" alt="" loading="lazy" decoding="async"><img class="kitchen-plate kitchen-plate--oven" src="${assets.kitchenOven}" alt="" loading="lazy" decoding="async">`;
  scene.querySelector('[data-layer="surfaces"]').innerHTML = `<img class="kitchen-plate kitchen-plate--table" src="${assets.kitchenTable}" alt="" loading="lazy" decoding="async">`;
}

function objectMarkup(object, version, index, seed) {
  const dx = ((seed + index * 17) % 7) - 3;
  const dy = ((seed + index * 11) % 5) - 2;
  const hue = version.eraId === 'past' ? 32 : version.eraId === 'future' ? 265 : 205;
  const image = object.id === 'forno' ? assets.kitchenOven : object.id === 'mesa' ? assets.kitchenTable : assets.kitchenProps;
  return `<button class="kitchen-object" style="--x:${version.position.x + dx}%;--y:${version.position.y + dy}%;--object-hue:${hue};--scale:${version.scale || 1}" data-slot="kitchen-object" data-object-id="${object.id}" data-era-id="${version.eraId}" aria-label="${version.label}: ${version.description}"><img src="${image}" alt="" loading="lazy" decoding="async"><span>${object.name}</span></button>`;
}