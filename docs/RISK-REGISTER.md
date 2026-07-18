# Registro de riscos

## Critério

- **Crítico:** pode causar perda de dados, indisponibilidade ampla ou inviabilizar a arquitetura pretendida; bloqueia release sem mitigação.
- **Alto:** regressão relevante/provável em fluxo principal; exige teste e rollback explícitos.
- **Médio:** impacto localizado ou contornável; deve entrar no planejamento.
- **Baixo:** baixa probabilidade/impacto, monitorável com processo normal.

| ID | Nível | Risco / evidência atual | Impacto | Mitigação e verificação |
|---|---|---|---|---|
| R01 | **Crítico** | Angular montado diretamente em `section#visual-system` pode substituir seis slots, carrossel e âncora flutuante. | Perda do fallback, visual e navegação. | Criar filho host dedicado; nunca dar bootstrap na section; teste sem bundle e teste ida/volta do flutuante. |
| R02 | **Crítico** | Conflito Angular × JS existente: ambos podem manipular DOM, estado, eventos e animações na mesma área. | listeners duplicados, corrida e DOM inconsistente. | Boundary estrito; Angular só no host; adaptador de eventos unidirecional/versionado; teardown e testes de reload. |
| R03 | **Crítico** | Migração de `localStorage` para API pode sobrescrever sessões anônimas ou associá-las ao usuário errado. | perda/vazamento de dados e quebra de continuidade. | IDs idempotentes, schema versions, merge explícito, consentimento, backup local e testes de conflito/offline. |
| R04 | **Crítico** | IDs e atributos `data-*` sustentam Dashboard, Widgets, flutuante, carrossel e integração futura. | renomeação silenciosa quebra seletores, ARIA, storage e eventos. | Inventário contratual, testes DOM, depreciação/versionamento; manter IDs dos 32 controles e quatro Widgets. |
| R05 | **Alto** | Regressão visual em duas páginas duplicadas e CSS global/minificado. | layout, responsividade ou identidade divergentes. | Baselines visuais desktop/mobile, alterações aditivas e escopadas, validar `/` e `/experience.html`. |
| R06 | **Alto** | Caminhos misturam imagens absolutas (`/tempo/...`), JSON relativo (`data/...`) e redirect relativo. | 404 em subdiretório, addon domain ou base URL diferente. | Definir document root final; manifest de assets; smoke test de cada rota/recurso no pacote cPanel. |
| R07 | **Alto** | Shared hosting pode limitar PHP/MySQL, cron, memória, upload, CPU, conexões e regras Apache. | API lenta/indisponível e deploy não reproduzível. | Levantar versões/limites antes da Fase 4; same-origin; health check; timeouts e paginação; ambiente staging. |
| R08 | **Alto** | Build Angular depende de Node no CI/desenvolvimento, enquanto produção não terá Node. | deploy incompleto ou tentativa de build no cPanel. | Produzir artefatos estáticos antes do upload; lockfile; documentar base href e budgets; servidor entrega somente arquivos. |
| R09 | **Alto** | PHP futuro: nenhuma versão, roteamento, validação, tratamento de erro ou política CORS definidos. | vulnerabilidades, incompatibilidade e contrato instável. | PHP suportado pelo host, `strict_types`, validação, respostas JSON padronizadas, same-origin, logs sem segredos e testes HTTP. |
| R10 | **Alto** | MySQL futuro: schema/migrations, charset, índices, transações e concorrência inexistentes. | inconsistência narrativa e consultas lentas. | InnoDB/`utf8mb4`, migrations reversíveis, constraints, índices, transações, seeds idempotentes e backup. |
| R11 | **Alto** | Autenticação Google futura não pode confiar em `tnaCredentialStatus` local. | impersonação e associação indevida de dados. | OAuth/OIDC server-side, validar token/nonce/state, sessão segura, CSRF, política de vinculação; não antecipar na Fase 4. |
| R12 | **Alto** | WebSockets futuros raramente são adequados a hospedagem compartilhada/processos PHP efêmeros. | conexões terminadas e falta de escala. | Manter eventos/API desacoplados; polling/SSE apenas se suportado; migrar para VPS/serviço gerenciado antes de tempo real real. |
| R13 | **Alto** | 29 × 7 = 203 imagens podem exceder bandwidth, storage e memória; acervo atual já soma ~8,6 MB. | LCP ruim, custo móvel e limites do host. | dimensões/budgets, WebP/AVIF com fallback, thumbnails, lazy loading, preload seletivo, cache imutável e CDN futura. |
| R14 | **Médio** | Cache do host/CDN/browser pode misturar HTML novo, bundles antigos e JSON incompatível; não há service worker atual. | erros intermitentes pós-deploy. | assets com hash, HTML/JSON com revalidação, versionar schema/API, purge e deploy atômico/rollback. |
| R15 | **Médio** | Quatro JSONs são sequenciais, schemas implícitos e dependem de MIME correto. | bootstrap lento ou fallback global por um arquivo. | JSON Schema/validação em CI, paralelizar com cuidado, mensagens diagnósticas e manter fallback. |
| R16 | **Médio** | `scripts/tempo.js` e `scripts/experience.js` são cópias exatas. | correção aplicada em uma rota e esquecida na outra. | Enquanto duplicado, teste de hash/paridade; consolidar somente em tarefa autorizada e com regressão. |
| R17 | **Médio** | Dashboard grava `localStorage` diretamente sem fallback de quota; store Tempo tem fallback apenas em memória. | ação falha em modo privado/quota e dados somem ao reload. | adapter comum tolerante a falha, retorno de erro ao UI e testes com storage bloqueado/cheio. |
| R18 | **Médio** | Ausência de CSP, headers documentados e separação de config privada. | superfície de XSS/leak cresce com API/login. | headers Apache compatíveis, escaping, config fora do web root, variáveis de ambiente quando suportadas. |
| R19 | **Médio** | Canonical `/tempo.html` não corresponde a arquivo/rota versionada; `/tempo/` redireciona no cliente. | SEO e links inconsistentes. | Confirmar contrato de URLs e implementar redirect de servidor apenas na fase de deploy autorizada. |
| R20 | **Baixo** | Arquivos aparentemente órfãos (`tempo/script/main.js`, `tempo/style/main.css`, tokens inativos) confundem manutenção. | edição do arquivo errado ou peso editorial. | Marcar no inventário; não remover sem validação de logs/URLs e autorização. |

## Gates obrigatórios antes de produção

1. Contratos de IDs, eventos, schemas e API versionados.
2. Testes de regressão visual/funcional em `/`, `/experience.html` e `/tempo/`.
3. Fallback de `#visual-system` operante sem Angular/JS e ida/retorno do flutuante preservados.
4. Migração de storage ensaiada com dados anônimos, conflito e rollback.
5. Matriz real do host (Apache, PHP, PDO MySQL, MySQL/MariaDB, `.htaccess`, SSL, limites e backups).
6. Build Angular reproduzível e pacote cPanel sem Node/segredos.
