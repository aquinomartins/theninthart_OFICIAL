export const OBJECT_INFLUENCE = 0.25;
export const EPISODE_VERSION = 'hagia-pio-cassava-cake-0.2.0';

export function normalizeVector(vector, eras = []) {
  const ids = eras.map((era) => era.id);
  const base = Object.fromEntries(ids.map((id) => [id, Math.max(0, Number(vector[id]) || 0)]));
  const total = Object.values(base).reduce((sum, value) => sum + value, 0);
  if (!total) return Object.fromEntries(ids.map((id) => [id, 1 / Math.max(ids.length, 1)]));
  return Object.fromEntries(ids.map((id) => [id, Number((base[id] / total).toFixed(4))]));
}

export function addVectors(eras, ...vectors) {
  const result = Object.fromEntries(eras.map((era) => [era.id, 0]));
  vectors.forEach((vector) => eras.forEach((era) => { result[era.id] += Number(vector?.[era.id]) || 0; }));
  return result;
}

export function scaleVector(eras, vector, scale = 1) {
  return Object.fromEntries(eras.map((era) => [era.id, (Number(vector?.[era.id]) || 0) * scale]));
}

export function calculateIndividualVector({ eras, parts, objects, selectedParts, selectedObjects }) {
  const selectedPartVectors = selectedParts.map((id) => parts.find((part) => part.id === id)?.temporalWeights).filter(Boolean);
  const selectedObjectVectors = selectedObjects.map((id) => objects.find((object) => object.id === id)?.temporalWeights).filter(Boolean).map((vector) => scaleVector(eras, vector, OBJECT_INFLUENCE));
  return normalizeVector(addVectors(eras, ...selectedPartVectors, ...selectedObjectVectors), eras);
}

export function getDominantEra(vector, eras) {
  return [...eras].sort((a, b) => (vector[b.id] || 0) - (vector[a.id] || 0) || a.order - b.order)[0] || eras[0];
}

export function getClosestVersion(object, eraId, eras) {
  const direct = object.versions.find((version) => version.eraId === eraId);
  if (direct) return direct;
  const target = eras.find((era) => era.id === eraId) || eras[0];
  return [...object.versions].sort((a, b) => Math.abs((eras.find((era) => era.id === a.eraId)?.order || 0) - target.order) - Math.abs((eras.find((era) => era.id === b.eraId)?.order || 0) - target.order))[0];
}

export function hashString(value) {
  let hash = 2166136261;
  for (let index = 0; index < value.length; index += 1) {
    hash ^= value.charCodeAt(index);
    hash = Math.imul(hash, 16777619);
  }
  return hash >>> 0;
}

export function createSeed(publicState) {
  const day = new Date().toISOString().slice(0, 10);
  return hashString(`${JSON.stringify(publicState)}:${day}:${EPISODE_VERSION}`);
}
