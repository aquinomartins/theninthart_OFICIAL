export const IS_DEMO_DATA = true;

const now = null;

export const dashboardHubDemoData = {
  shortcuts: [
    'Arquivo','Pesquisa','Comunidade','Oficina','História','Coleção','Sistemas','Laboratório',
    'Mapa','Agenda','Notas','Painéis','Rascunhos','Referências','Leituras','Estudos',
    'Acervo','Curadoria','Métricas','Fluxos','Autores','Projetos','Ensaios','Caderno',
    'Índice','Trilhas','Arquivo Vivo','Protótipos','Observatório','Ferramentas','Memória','Ateliê'
  ].map((title, index) => ({
    id: title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-'),
    title,
    shortTitle: title,
    description: `Área demonstrativa para ${title.toLowerCase()} e futuras integrações.`,
    iconUrl: null,
    iconAlt: '',
    href: null,
    target: '_self',
    order: index + 1,
    group: index < 8 ? 'primary' : 'default',
    category: index % 3 === 0 ? 'research' : index % 3 === 1 ? 'community' : 'collection',
    status: index === 11 ? 'loading' : index === 27 ? 'unavailable' : index === 30 ? 'disabled' : 'active',
    visibility: 'public',
    badge: index === 2 ? '4' : index === 18 ? 'Novo' : null,
    badgeType: index === 18 ? 'new' : 'count',
    isFavorite: index === 0 || index === 5,
    isPinned: index < 4,
    interactionCount: 0,
    unreadCount: index === 2 ? 4 : 0,
    userState: {},
    metadata: { tags: [title, 'demo', index % 2 ? 'visual' : 'dados'] },
    createdAt: now,
    updatedAt: now
  })),
  widgets: [
    { id: 'comparison-overview', type: 'comparison', title: 'Comparação', subtitle: 'Visão neutra', order: 1, enabled: true, collapsed: false, visibility: 'public', userId: null, status: 'ready', data: { tabs: ['Hoje','Semana'], activeTab: 'Hoje', status: 'Preparado', time: '14:30', date: 'Sessão demonstrativa', participants: [{ name: 'Núcleo A', detail: 'Entrada' }, { name: 'Núcleo B', detail: 'Resposta' }] }, permissions: {}, metadata: { tags: ['comparação'] }, interactions: [], createdAt: now, updatedAt: now },
    { id: 'state-list', type: 'list', title: 'Estados', subtitle: null, order: 2, enabled: true, collapsed: false, visibility: 'public', userId: null, status: 'ready', data: { items: [
      { id: 's1', label: 'Arquivo', value: '08:41', secondary: 'Sincronizado', iconUrl: null, status: 'active', metadata: {} },
      { id: 's2', label: 'Coleção', value: '12 itens', secondary: 'Atualizado', iconUrl: null, status: 'active', metadata: {} },
      { id: 's3', label: 'Pesquisa', value: '3 notas', secondary: 'Em revisão', iconUrl: null, status: 'pending', metadata: {} },
      { id: 's4', label: 'Oficina', value: 'Aberta', secondary: 'Participação livre', iconUrl: null, status: 'active', metadata: {} },
      { id: 's5', label: 'Laboratório', value: '2 ciclos', secondary: 'Fila longa demonstrativa', iconUrl: null, status: 'active', metadata: {} }
    ] }, permissions: {}, metadata: { tags: ['lista','estado'] }, interactions: [], createdAt: now, updatedAt: now },
    { id: 'focus-timer', type: 'timer', title: 'Tempo', subtitle: 'Sessão', order: 3, enabled: true, collapsed: false, visibility: 'public', userId: null, status: 'ready', data: { progress: 68, remaining: '18:20', duration: 30, mode: 'Foco', state: 'paused' }, permissions: {}, metadata: { tags: ['tempo','controle'] }, interactions: [], createdAt: now, updatedAt: now },
    { id: 'task-list', type: 'tasks', title: 'Tarefas', subtitle: null, order: 4, enabled: true, collapsed: false, visibility: 'public', userId: null, status: 'ready', data: { items: [
      { id: 't1', title: 'Revisar painel', completed: true, priority: 'média', assignee: 'Equipe', dueDate: 'Hoje', iconUrl: null },
      { id: 't2', title: 'Organizar referências longas sem quebrar o layout', completed: false, priority: 'alta', assignee: 'Curadoria', dueDate: 'Amanhã', iconUrl: null },
      { id: 't3', title: 'Preparar metadados', completed: false, priority: 'baixa', assignee: 'Dados', dueDate: 'Semana' }
    ] }, permissions: {}, metadata: { tags: ['tarefas'] }, interactions: [], createdAt: now, updatedAt: now }
  ]
};
