import { calculateIndividualVector } from './temporal-engine.js';

export const schemaVersion = '0.2.0';
const keys = { sessions: 'tna:experience:sessions:v2', current: 'tna:experience:current:v2', public: 'tna:experience:public:v2', credential: 'tnaCredentialStatus' };
let memory = { sessions: [], current: null, public: null, credential: null };
const safeStorage = () => { try { localStorage.setItem('__tna_test', '1'); localStorage.removeItem('__tna_test'); return localStorage; } catch { return null; } };
const read = (key, fallback) => { const storage = safeStorage(); if (!storage) return memory[key] || fallback; try { return JSON.parse(storage.getItem(keys[key])) || fallback; } catch { return fallback; } };
const write = (key, value) => { const storage = safeStorage(); memory[key] = value; if (storage) storage.setItem(keys[key], JSON.stringify(value)); };

export const localPersistence = {
  async loadDataset() { return null; },
  async saveSession(session) {
    const sessions = await this.loadSessions();
    const index = sessions.findIndex((item) => item.id === session.id);
    if (index >= 0) sessions[index] = session; else sessions.push(session);
    write('sessions', sessions.slice(-60));
    write('current', session);
    return session;
  },
  async loadSessions() { return migrateSessions(read('sessions', [])); },
  async savePublicSnapshot(snapshot) { write('public', snapshot); return snapshot; },
  async loadPublicSnapshot() { return read('public', null); },
  async saveCredentialStatus(status) { const payload = { status, updatedAt: new Date().toISOString() }; write('credential', payload); return payload; },
  async loadCredentialStatus() { return read('credential', null); },
  async clearSessions() { write('sessions', []); write('current', null); },
  async loadCurrentSession() { return read('current', null); }
};

export const remotePersistence = {
  async loadDataset() { return null; },
  async saveSession(session) { return session; },
  async loadPublicSnapshot() { return null; }
};

export function createSession(dataset, restored = {}) {
  const selectedParts = restored.parts || restored.selectedParts || ['sequence'];
  const selectedObjects = restored.objects || restored.selectedObjects || ['forma'];
  const session = { id: `session-${Date.now()}-${Math.random().toString(16).slice(2)}`, schemaVersion, createdAt: new Date().toISOString(), updatedAt: new Date().toISOString(), selectedParts, selectedObjects, completed: false, credentialStatus: restored.credentialStatus || null };
  session.vector = calculateIndividualVector({ ...dataset, selectedParts, selectedObjects });
  return session;
}

export function updateSession(session, dataset, patch) {
  const next = { ...session, ...patch, updatedAt: new Date().toISOString() };
  next.vector = calculateIndividualVector({ ...dataset, selectedParts: next.selectedParts, selectedObjects: next.selectedObjects });
  return next;
}

function migrateSessions(sessions) {
  if (!Array.isArray(sessions)) return [];
  return sessions.map((session) => ({ schemaVersion, completed: false, selectedParts: [], selectedObjects: [], ...session }));
}