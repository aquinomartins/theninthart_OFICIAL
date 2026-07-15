export function initAdminPanel({ dialog, dataset, persistence, onRestore }) {
  if (!dialog) return;
  const exportBox = dialog.querySelector('[data-admin-export]');
  dialog.addEventListener('click', async (event) => {
    const action = event.target?.dataset?.adminAction;
    if (!action) return;
    if (action === 'close') dialog.close();
    if (action === 'export') exportBox.value = JSON.stringify({ dataset, sessions: await persistence.loadSessions(), publicSnapshot: await persistence.loadPublicSnapshot() }, null, 2);
    if (action === 'clear') { await persistence.clearSessions(); onRestore?.(); }
    if (action === 'restore') { localStorage.removeItem('tna:experience:sessions:v2'); localStorage.removeItem('tna:experience:current:v2'); onRestore?.(); }
  });
}
