# Plano executável de implementação

## Princípios e ordem global

A ordem obrigatória é **Fase 1 → 2 → 3 → 4 → 5 → 6 → 7**. Cada fase deve ser entregue em PRs pequenos; nenhuma pressupõe remoção/reorganização do front-end. O produto existente continua sendo o fallback. Os nomes abaixo para arquivos futuros são propostas, não arquivos criados nesta auditoria.

## Fase 1 — Contratos e dados

**Objetivo.** Definir, sem qualquer alteração visual, contratos canônicos para os 32 controles, quatro Widgets, sete versões, 29 quadrantes e exatamente 203 slots (29 × 7), além de schemas JSON versionados e fixtures mínimas.

**Ordem de execução.**

1. Congelar/inventariar IDs atuais dos 32 controles, quatro Widgets, eventos e `data-*`.
2. Definir entidades `Control`, `Widget`, `Quadrant`, `Version`, `ImageSlot`, `NarrativeState` e seus relacionamentos/cardinalidades.
3. Definir convenção estável de IDs, revisão e compatibilidade.
4. Criar JSON Schemas e exemplos; gerar manifesto que prove 29, 7 e 203 sem exigir assets reais.
5. Adicionar validação automatizada, referencial e de unicidade.

**Arquivos existentes envolvidos.** `data/dashboard-hub-demo.js`, `data/*.json`, `scripts/dashboard-hub.js`, `scripts/modules/state-store.js`, `scripts/modules/temporal-engine.js` e este inventário. Apenas leitura inicialmente; qualquer adaptação posterior deve ser compatível.

**Arquivos novos sugeridos.** `contracts/schemas/*.schema.json`, `contracts/examples/*.json`, `data/narrative-manifest.json`, `tests/contracts/*` e `docs/CONTRACTS.md`.

**Dependências.** Decisão editorial sobre os 29 quadrantes/sete versões; mapeamento dos 32 controles para regras; formato JSON Schema; nenhuma dependência visual ou Angular.

**Testes.** Parse/JSON Schema; exatamente 32 IDs de controles, quatro IDs de Widget, 29 quadrantes, sete versões por quadrante e 203 combinações; IDs únicos; referências válidas; snapshots dos contratos antigos.

**Riscos.** Modelar "versão" como era antiga; acoplar slot a nome de arquivo; mudar IDs persistidos; schema prematuro sem exemplos editoriais.

**Critérios de conclusão.** Schemas versionados validam exemplos; cardinalidades passam em CI; tabela de compatibilidade cobre storage/eventos atuais; diff visual e funcional é zero.

**Tarefa sugerida para o Codex.** "Crie somente os schemas e testes de contrato da Fase 1; preserve os arquivos de runtime e prove por teste as cardinalidades 32/4/29/7/203."

## Fase 2 — Estrutura visual dos quadrantes

**Objetivo.** Introduzir HTML e CSS do grid de 29 quadrantes com 203 placeholders, preservando identidade, seção `#visual-system`, conteúdo/fallback e elemento flutuante. Começar sem Angular se isso reduzir risco.

**Ordem de execução.**

1. Capturar baselines responsivos e comportamento do flutuante/carrossel.
2. Adicionar dentro de `#visual-system` um filho dedicado e semanticamente isolado; não substituir a section nem sua âncora.
3. Implementar um quadrante/placeholders em HTML/CSS progressivo; validar acessibilidade e performance.
4. Expandir deterministicamente para 29 × 7 usando dados da Fase 1, sem carregar 203 imagens reais.
5. Validar ambas as páginas ou decidir/documentar uma fonte compartilhada em tarefa separada.

**Arquivos existentes envolvidos.** `index.html`, `experience.html`, `styles.css`, `styles/tempo*.css`, `script.js`, `scripts/tempo.js`, `scripts/experience.js`.

**Arquivos novos sugeridos.** `styles/narrative-grid.css`, `scripts/narrative-grid.js` (somente se render dinâmico for necessário), fixtures e testes visuais/DOM.

**Dependências.** Contratos da Fase 1, layouts editoriais aprovados, budgets e breakpoints atuais. Sem Angular obrigatoriamente nesta fase.

**Testes.** Contagem DOM; HTML sem JS; desktop/tablet/mobile; teclado/leitor de tela; `prefers-reduced-motion`; ida/retorno de `imagem1.png`; carrossel atual; comparação visual; LCP/CLS e lazy loading.

**Riscos.** Regressão visual; 203 nós/assets pesados; CSS global; duplicidade de HTML; remoção acidental do anchor/fallback.

**Critérios de conclusão.** 29 quadrantes e 203 placeholders verificáveis; sem imagem final obrigatória; `#visual-system`/anchor/flutuante e todo o exterior inalterados; aprovação visual.

**Tarefa sugerida para o Codex.** "Implemente somente o primeiro incremento HTML/CSS progressivo do grid dentro de um filho de `#visual-system`, sem Angular e sem substituir o fallback; inclua testes DOM e screenshots responsivos."

## Fase 3 — Motor Angular isolado

**Objetivo.** Criar aplicação Angular standalone montada **somente dentro de um host filho de `#visual-system`**, mantendo o conteúdo existente como fallback. Ela não controlará cabeçalho, hero, Dashboard, manifesto, demais seções ou footer.

**Ordem de execução.**

1. Fixar versão/lockfile e budgets; gerar workspace mínimo fora do front-end estático.
2. Criar standalone root, serviço de contratos e render puramente derivado do manifesto.
3. Fazer bootstrap condicional no host filho; ocultar fallback apenas após evento de prontidão bem-sucedido.
4. Garantir falha/timeout de bundle que revele fallback intacto.
5. Produzir bundle com nomes hash e testar que Angular não seleciona/manipula DOM externo.

**Arquivos existentes envolvidos.** host criado na Fase 2, `#visual-system`, contratos, CSS visual e configuração de inclusão de assets no HTML (mudança mínima).

**Arquivos novos sugeridos.** `narrative-engine/angular.json`, `package.json`, lockfile, `tsconfig*.json`, `src/main.ts`, `src/app/*`, build output ignorado/versionado conforme estratégia, loader pequeno e testes.

**Dependências.** Node somente em desenvolvimento/CI; Angular standalone; Fases 1–2; decisão de `baseHref`/`deployUrl`. Evitar biblioteca visual externa.

**Testes.** Unitários do motor; 29×7; bootstrap/falha/timeout; boundary DOM; CSP; bundle budget; acessibilidade; screenshots; header/hero/footer/Dashboard/flutuante intocados.

**Riscos.** Angular apagar fallback, CSS vazar, bundle pesado, paths incorretos, dupla fonte de estado, incompatibilidade com browsers do público.

**Critérios de conclusão.** Standalone monta apenas no host; exterior não é controlado; fallback funciona sem bundle; build estático reproduzível; nenhuma exigência de Node em produção.

**Tarefa sugerida para o Codex.** "Crie o shell Angular standalone isolado e loader fail-safe; não conecte Dashboard/API e demonstre por teste que nenhum nó fora do host é alterado."

## Fase 4 — API PHP e MySQL

**Objetivo.** Implementar migrations, seeds, PDO, endpoints e persistência de sessões compatíveis com shared hosting. **Nenhuma autenticação Google nesta fase.**

**Ordem de execução.**

1. Confirmar matriz real de PHP/MySQL/Apache e limites do host.
2. Especificar OpenAPI/contratos de erro, IDs idempotentes e paginação.
3. Criar migrations/rollback/seeds idempotentes com `utf8mb4`, constraints e índices.
4. Criar configuração privada, conexão PDO segura e camada de repositório.
5. Entregar endpoints pequenos: health, leitura de manifesto/config e CRUD/sync de sessão anônima.
6. Testar concorrência, validação, falhas e backup/restore.

**Arquivos existentes envolvidos.** contratos da Fase 1 e `state-store.js` apenas como referência; não trocar persistência do cliente ainda.

**Arquivos novos sugeridos.** `api/public/index.php`, `api/src/*`, `api/migrations/*.sql`, `api/seeds/*.sql`, `api/config/config.example.php`, `api/tests/*`, `docs/openapi.yaml`.

**Dependências.** PHP suportado, PDO MySQL, MySQL/MariaDB e Apache; banco/staging; segredos fora do repositório/web root; HTTPS. Preferir implementação sem Composer se o deploy do host não o suportar, sem sacrificar estrutura/testes.

**Testes.** Migration up/down em banco descartável; seeds repetidos; PDO prepared statements; validação/JSON; status HTTP; SQL injection; XSS armazenado; concorrência/idempotência; limites/paginação; PHP lint.

**Riscos.** Versão do host, credenciais expostas, permissões, schema inconsistente, falta de transação, limites de conexão e CORS.

**Critérios de conclusão.** Banco nasce de migrations/seeds; API versionada responde no staging; PDO usa prepared statements; sessões anônimas persistem idempotentemente; nenhum segredo ou Google Login.

**Tarefa sugerida para o Codex.** "Implemente um primeiro slice PHP/MySQL (migration + health + repositório de sessão) conforme OpenAPI, com testes, sem tocar o cliente e sem autenticação."

## Fase 5 — Integração

**Objetivo.** Ligar Dashboard e Angular por eventos versionados, executar resolução narrativa, adotar persistência híbrida local/API e garantir continuidade entre quadrantes.

**Ordem de execução.**

1. Criar adaptador externo que escuta eventos atuais sem modificar o dono do Dashboard.
2. Traduzir estado dos 32 controles/quatro Widgets para comandos narrativos validados.
3. Implementar reducer determinístico e continuidade quadrante a quadrante no Angular.
4. Implementar adapter local/remote com fila, idempotência, retry/backoff e resolução de conflitos.
5. Migrar/copiar storage versionado de forma reversível e observável; só então ativar sync gradual.

**Arquivos existentes envolvidos.** `scripts/dashboard-hub.js` (contrato, mudança mínima se indispensável), `state-store.js`, Angular, API e schemas.

**Arquivos novos sugeridos.** `scripts/integration/dashboard-narrative-adapter.js`, serviços/reducer Angular, adapter de API, migration de storage e testes end-to-end.

**Dependências.** Fases 1, 3 e 4 completas; eventos e API versionados; política editorial de resolução/conflito.

**Testes.** Todos os 32 toggles; quatro Widgets; ordem/replay de eventos; 29 quadrantes e sete versões; continuidade/reload; offline/online; API lenta/erro; duas abas; conflito; storage legado; rollback; acessibilidade.

**Riscos.** loops de eventos, corrida, narrativa não determinística, perda de localStorage, dupla gravação e falha parcial.

**Critérios de conclusão.** Um evento produz uma transição determinística; continuidade sobrevive a reload/offline; sync é idempotente; migração tem rollback; fallback continua funcional.

**Tarefa sugerida para o Codex.** "Integre um único evento versionado ponta a ponta por adaptador, com reducer e persistência híbrida testados; não altere markup/visual do Dashboard."

## Fase 6 — Produção compartilhada

**Objetivo.** Gerar build Angular estático e pacote reproduzível para cPanel com `.htaccess`, configuração privada e testes reais em PHP/MySQL; Node não roda em produção.

**Ordem de execução.**

1. Criar build de release com hashes/budgets e verificar paths/base URL.
2. Montar pacote cPanel preservando rotas/arquivos estáticos e separando `api/public`/config privada.
3. Criar `.htaccess` mínimo: roteamento API, HTTPS/headers e cache diferenciado; sem capturar assets/rotas existentes.
4. Aplicar migrations/seed no staging, configurar segredos fora do Git e executar smoke/e2e.
5. Ensaiar deploy atômico, purge e rollback com backup.

**Arquivos existentes envolvidos.** todo artefato publicável, rotas HTML, imagens e API; nenhuma reorganização arbitrária.

**Arquivos novos sugeridos.** scripts de empacotamento, manifesto/checksums, `.htaccess` no escopo correto, `DEPLOYMENT.md`, templates privados sem credencial e smoke tests.

**Dependências.** cPanel/Apache/SSL/PHP/PDO/MySQL reais, CI/build Node fora da produção, acesso a logs/backup e document root confirmado.

**Testes.** Conteúdo/HTTP/MIME de todas as rotas e bundles; PHP/MySQL staging; `.htaccess`; cache novo/antigo; deep links; upload limpo; ausência de source maps/segredos; rollback; desktop/mobile.

**Riscos.** rewrite loop/500, caminhos absolutos, cache misto, permissões, upload parcial, limite do host e config exposta.

**Critérios de conclusão.** Pacote sobe sem Node/Composer em runtime; rotas atuais continuam; API e banco passam smoke; config não é pública; cache/rollback documentados e ensaiados.

**Tarefa sugerida para o Codex.** "Crie o empacotamento cPanel e `.htaccess` mínimo em ambiente de staging, com checksums e smoke tests; não publique nem inclua segredos."

## Fase 7 — Evolução futura

**Objetivo.** Preparar, somente após estabilidade/medição, Google Login, WebSockets e infraestrutura de alta escala, com migração planejada para VPS ou serviço gerenciado.

**Ordem de execução.**

1. Instrumentar volume, latência, concorrência e necessidade real de tempo real.
2. Projetar Google OAuth/OIDC server-side e vinculação segura de sessões anônimas.
3. Separar contratos/serviços de estado do transporte e desenhar eventos idempotentes.
4. Fazer prova de conceito WebSocket fora do shared hosting, com autenticação, reconexão e fallback.
5. Planejar migração para VPS/PaaS/serviços gerenciados, banco gerenciado, object storage/CDN, filas/observabilidade.

**Arquivos existentes envolvidos.** contratos/API/adapters; nenhum segredo ou alteração visual necessária.

**Arquivos novos sugeridos.** ADRs de autenticação/tempo real/infra, serviço OAuth, gateway realtime, IaC, runbooks, testes de carga e migração.

**Dependências.** Fase 6 estável; provedor Google; domínio HTTPS; infraestrutura persistente fora do shared hosting; budget/SLO/privacidade aprovados.

**Testes.** OAuth state/nonce/CSRF e revogação; associação anônima; reconexão/replay/rate limit; carga/soak/failover; migração/rollback; segurança e privacidade.

**Riscos.** account takeover, vazamento de token, WebSocket sem escala, custo, vendor lock-in, migração de dados e complexidade antes da demanda.

**Critérios de conclusão.** Login validado no servidor; tempo real tem necessidade/SLO e fallback; infraestrutura suporta carga medida; migração e rollback ensaiados; shared hosting deixa de ser dependência do realtime.

**Tarefa sugerida para o Codex.** "Produza primeiro um ADR comparando OAuth server-side e opções realtime/hosting com dados medidos; não implemente login ou WebSockets sem aprovação do ADR."

## Estratégia de incrementos revisáveis

Cada tarefa futura deve conter: (1) um contrato ou slice; (2) testes automatizados; (3) validação das duas rotas principais; (4) evidência de preservação de `#visual-system`, elemento flutuante, 32 controles e quatro Widgets; (5) rollback; (6) nenhuma credencial. Não combinar grid, Angular, API e deploy em uma única alteração.
