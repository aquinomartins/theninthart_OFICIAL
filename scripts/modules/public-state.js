import { normalizeVector } from './temporal-engine.js';

export function calculateSessionWeight(session) {
  const ageHours = Math.max(0, (Date.now() - new Date(session.updatedAt || session.createdAt || Date.now()).getTime()) / 36e5);
  const recency = Math.max(0.15, Math.exp(-ageHours / 72));
  const completion = session.completed ? 1 : 0.6;
  const picks = [...(session.selectedParts || []), ...(session.selectedObjects || [])];
  const diversity = picks.length ? Math.min(1, new Set(picks).size / picks.length + 0.25) : 0.7;
  return recency * completion * diversity;
}

export function calculatePublicVector(sessions, eras) {
  const aggregate = Object.fromEntries(eras.map((era) => [era.id, 0]));
  sessions.forEach((session) => {
    const weight = calculateSessionWeight(session);
    eras.forEach((era) => { aggregate[era.id] += (Number(session.vector?.[era.id]) || 0) * weight; });
  });
  return normalizeVector(aggregate, eras);
}

export function createPublicSnapshot(sessions, eras) {
  return { schemaVersion: '0.2.0', updatedAt: new Date().toISOString(), sessions: sessions.length, vector: calculatePublicVector(sessions, eras) };
}
