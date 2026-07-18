# Inventário integral do projeto

## Escopo e conclusão executiva

Auditoria estática de todos os 42 arquivos versionados no estado atual. O produto é um site estático, sem framework, gerenciador de pacotes, build, PHP, banco ou servidor de aplicação. A página principal combina o site institucional, um Dashboard inserido antes do manifesto e uma experiência Tempo modular. `experience.html` preserva quase toda a página, mas não inclui o Dashboard. `/tempo/` é apenas um redirecionamento HTML.

Os requisitos futuros (29 quadrantes, sete versões por quadrante e 203 slots) **ainda não existem**. Existem 32 controles e quatro Widgets, bem como um modelo narrativo anterior de 12 painéis, cinco peças, oito objetos com três versões temporais e três eras.

## Árvore resumida

```text
/
├── index.html                  # entrada principal; Dashboard + site + Tempo
├── experience.html             # site + Tempo, sem Dashboard
├── script.js                   # reveal e carrossel institucional
├── styles.css                  # visual institucional
├── data/
│   ├── dashboard-hub-demo.js   # 32 controles e quatro Widgets
│   ├── eras.json
│   ├── machine-parts.json
│   ├── story-panels.json
│   └── visual-objects.json
├── scripts/
│   ├── dashboard-hub.js        # renderiza/gerencia Dashboard
│   ├── tempo.js                # bootstrap principal da experiência
│   ├── experience.js           # cópia exata de tempo.js
│   └── modules/                # estado, motor, carrossel, scroll, admin, assets
├── styles/
│   ├── dashboard-hub.css
│   ├── tempo.css               # agrega components e motion
│   ├── tempo.components.css
│   ├── tempo.motion.css
│   └── tempo.tokens.css        # não importado (import comentado)
├── tempo/
│   ├── index.html              # redirect para ../index.html
│   ├── images/                 # 10 imagens + README
│   ├── script/main.js          # cópia exata de script.js, órfã
│   └── style/main.css           # cópia exata de styles.css, órfã
└── docs/                       # documentos de auditoria preexistentes e este conjunto
```

## Páginas públicas e pontos de entrada

| Rota esperada | HTML | CSS direto | JavaScript direto | Observação |
|---|---|---|---|---|
| `/`, `/index.html` | `index.html` | `styles.css`, `styles/tempo.css`, `styles/dashboard-hub.css` | `script.js`, `scripts/dashboard-hub.js`, `scripts/tempo.js` | Única entrada com Dashboard. |
| `/experience.html` | `experience.html` | `styles.css`, `styles/tempo.css` | `script.js`, `scripts/experience.js` | Mesmo institucional/visual-system, sem Dashboard. |
| `/tempo/` | `tempo/index.html` | nenhum | nenhum | `meta refresh` e link para `../index.html`; canonical aponta para `/tempo.html`, arquivo que não existe. |

`styles/tempo.css` importa `tempo.components.css` e `tempo.motion.css`; seu import de `tempo.tokens.css` está comentado. Os módulos `tempo.js`/`experience.js` importam `asset-manifest`, `carousel`, `scroll-scenes`, `admin-panel`, `state-store`, `public-state` e `temporal-engine`. O Dashboard importa somente seus dados demo.

## Dados JSON

| Arquivo | Conteúdo atual | Consumidor |
|---|---|---|
| `data/eras.json` | 3 eras (`past`, `present`, `future`), ordem, intervalo e tema | bootstrap Tempo, motor temporal e snapshot público |
| `data/machine-parts.json` | 5 peças, pesos temporais, resumo e chave de imagem | sessão/motor e renderização Tempo |
| `data/story-panels.json` | 12 painéis narrativos lineares | renderização Tempo |
| `data/visual-objects.json` | 8 objetos; cada um tem pesos e 3 versões (24 versões), todas com `asset: null` | motor, carrossel/objetos e renderização Tempo |

`data/dashboard-hub-demo.js` é módulo JavaScript, não JSON: possui exatamente 32 `shortcuts` e quatro `widgets`.

## JavaScript e módulos

| Arquivo | Responsabilidade |
|---|---|
| `script.js` | `IntersectionObserver` para `.reveal`; avanço/recuo do carrossel de `[data-visual-carousel]`. |
| `scripts/dashboard-hub.js` | Estado em memória, render dinâmico, pesquisa, 32 switches, ativação exclusiva de Widget, parâmetros, acessibilidade, persistência e eventos. |
| `scripts/tempo.js` | Carrega quatro JSONs, restaura/cria sessão, renderiza partes da experiência, trata fallback e controla o elemento flutuante. |
| `scripts/experience.js` | Cópia byte a byte de `scripts/tempo.js`, usada somente por `experience.html`. |
| `modules/asset-manifest.js` | Mapa central de chaves para imagens em `/tempo/images/` e fallback por chave. |
| `modules/carousel.js` | Carrossel detalhado opcional da experiência. |
| `modules/scroll-scenes.js` | Observa elementos `[data-panel]` e sinaliza cena ativa. |
| `modules/admin-panel.js` | Diálogo administrativo opcional; exporta/importa JSON e limpa parte do storage. |
| `modules/state-store.js` | Contrato assíncrono de persistência local, fallback em memória, sessões e migração rasa. |
| `modules/public-state.js` | Agrega sessões em vetor/snapshot público. |
| `modules/temporal-engine.js` | Normaliza/combina vetores, resolve era/versão e produz seed determinística. |
| `tempo/script/main.js` | Cópia órfã de `script.js`; nenhum HTML atual a carrega. |

## Dashboard, 32 controles e quatro Widgets

O contêiner existe apenas em `index.html` como `#dashboard-hub`/`[data-dashboard-hub]`. O HTML fornece busca, `[data-shortcut-grid]` vazio, barra com quatro botões de ativação e `[data-widget-grid]` vazio. `scripts/dashboard-hub.js` preenche os dois grids no `DOMContentLoaded`.

- Os **32 controles** vêm de `dashboardHubDemoData.shortcuts`; tornam-se botões `role="switch"` com `[data-feature-toggle]`, `[data-feature-id]` e `aria-checked`. São independentes dos Widgets: ligar um recurso não abre painel.
- Os **quatro Widgets** são Comparação (`comparison-widget`), Estados (`states-widget`), Tempo (`time-widget`) e Tarefas (`tasks-widget`). A barra está no HTML; os cards/modelos vêm dos dados demo. Somente um fica visível por vez.
- O estado do Dashboard é um objeto privado em memória. A camada `dashboardRepository` simula uma API, mas atualmente sempre lê dados demo e grava localmente.
- CSS e seletores `data-*`, IDs dos Widgets e IDs dos 32 recursos são contratos de integração, não detalhes descartáveis.

## Estrutura de `#visual-system`

Existe tanto em `index.html` quanto em `experience.html`, depois do manifesto (e, em `index.html`, depois do Dashboard). O contrato externo inclui:

1. `section#visual-system.visual-system.section-shell.reveal` e `[data-floating-destination="visual-system"]`;
2. `[data-floating-guide-destination-anchor]`, âncora invisível para o pouso do elemento flutuante;
3. heading `#visual-system-title`;
4. `.apple-like-grid` com seis slots fixos (um grande, um largo e quatro metades);
5. carrossel com seis itens, botões `[data-carousel-prev|next]` e trilho `[data-visual-carousel]`.

Hoje há 12 ocorrências visuais (seis slots + seis itens), não 29 quadrantes ou 203 slots. O carrossel é controlado por `script.js`. A seção também é destino do controlador flutuante de `tempo.js`.

## Elemento flutuante `imagem1.png`

`index.html` e `experience.html` criam uma camada fixa e uma imagem `/tempo/images/imagem1.png` marcada por `[data-floating-narrative]`, teclado, ARIA e `role="button"`. `tempo.components.css` define dimensão, sombra, posição e variáveis CSS; `tempo.motion.css` trata movimento reduzido/mobile.

`initFloatingNarrative()` em ambas as entradas Tempo:

- procura o destino prioritariamente por `[data-floating-destination="visual-system"]`, depois `#visual-system`;
- move a imagem organicamente por `requestAnimationFrame` enquanto livre;
- em clique, Enter ou Espaço, salva scroll/pose, anima saída, rola até o destino, pousa na âncora e muda ARIA;
- no segundo acionamento retorna ao scroll/pose anterior;
- respeita `prefers-reduced-motion`, reage a resize/scroll e expõe mensagens `aria-live`;
- emite `floating-guide-travel-to-visual-system` e `floating-guide-return-to-origin`.

Se destino ou elemento faltarem, registra aviso e evita derrubar o restante. Angular não pode substituir a própria `section` ou remover a âncora; deve montar em um filho dedicado.

## Armazenamento local

| Chave | Dono | Conteúdo/limite |
|---|---|---|
| `dashboard-hub:v1:user-state` | Dashboard | Widget ativo, expansão, dirty flags, valores de parâmetros e último payload. |
| `dashboard-hub:v2:feature-state` | Dashboard | mapa booleano dos 32 controles. |
| `tna:experience:sessions:v2` | state-store | até 60 sessões Tempo. |
| `tna:experience:current:v2` | state-store | sessão corrente. |
| `tna:experience:public:v2` | state-store | snapshot/vetor coletivo. |
| `tnaCredentialStatus` | state-store | estado futuro/simulado de credencial e timestamp. |

O state-store testa disponibilidade e usa memória quando `localStorage` falha. O Dashboard envolve leitura em `try/catch`, mas as escritas diretas não têm fallback; quota/bloqueio pode interromper uma ação. Não há sincronização entre abas, expiração, usuário real, criptografia ou persistência remota.

## Eventos customizados

Todos são despachados em `window`; não há consumidores internos atuais.

- Dashboard: `dashboard:feature-toggle`, `dashboard:search`, `dashboard:widget-activate`, `dashboard:widget-close`, `dashboard:parameter-change`, `dashboard:widget-save`, `dashboard:widget-reset`, `dashboard:widget-action`, `dashboard:widget-error`.
- Flutuante: `floating-guide-travel-to-visual-system`, `floating-guide-return-to-origin`.

Eventos do Dashboard incluem `detail` com timestamp e `userId` nulo, mais dados específicos. Os eventos flutuantes não incluem `detail`. Esses nomes devem ser versionados/documentados antes de Angular tornar-se consumidor.

## Componentes e dependências externas

Componentes existentes: header/nav, hero, composição sequencial, Dashboard/search/switch grid/widget bar/cards/forms, manifesto, visual-system/slots/carrossel, missão, pilares, áreas, projetos, CTA, footer, camada flutuante, fallback de erro e componentes Tempo opcionais gerados por JS.

Não há dependência runtime de terceiros, CDN, npm ou fonte remota identificada. Há somente APIs nativas do navegador (`fetch`, ES modules, `IntersectionObserver`, Web Animations, `requestAnimationFrame`, `matchMedia`, `localStorage`, `CustomEvent`, `structuredClone` com fallback e `CSS.escape`), links canonical e `mailto:`. Compatibilidade dos navegadores e servidor HTTP com MIME correto para módulos/JSON são dependências operacionais.

## Imagens e fallback de recursos

Existem dez imagens (nove PNG e um JPG), aproximadamente 8,6 MB no total; `imagem010.png` sozinha tem cerca de 3,2 MB. HTML usa sete nomes; o manifest usa todos os dez. Imagens repetidas, especialmente `imagem4.png`, funcionam como placeholders. O manifest resolve chaves e oferece imagem fallback; `hydrateAssetImages()` e tratadores de erro evitam que uma imagem ausente derrube todo o bootstrap. Os caminhos de imagem são absolutos (`/tempo/images/...`), enquanto JSON é carregado relativamente ao `document.baseURI` (`data/...`).

## Carregamento e fallback inicial

O HTML e CSS institucional aparecem sem JavaScript. `script.js` ativa reveals/carrossel; Dashboard inicia separadamente e degrada apenas sua área. O bootstrap Tempo hidrata imagens, inicia flutuante/scroll, baixa os quatro JSONs sequencialmente, valida HTTP/MIME/JSON, restaura estado e renderiza. Uma falha essencial cai no fallback de experiência com diagnóstico no console e tentativa novamente. Conteúdo estático de `#visual-system` permanece no HTML.

## Duplicação e itens legados

- `scripts/tempo.js` = `scripts/experience.js` (duplicação exata de ~24 KB).
- `script.js` = `tempo/script/main.js` (duplicação exata; segundo aparentemente órfão).
- `styles.css` = `tempo/style/main.css` (duplicação exata; segundo aparentemente órfão).
- `index.html` e `experience.html` repetem quase toda a estrutura institucional e `#visual-system`; divergem no Dashboard e assets carregados.
- CSS do Dashboard está minificado/em linhas muito longas e contém camadas incrementais de regras, elevando custo de revisão.

Nada deve ser consolidado nesta auditoria: as cópias podem sustentar rotas históricas.

## Divergências do contexto futuro

| Contexto pretendido | Estado encontrado |
|---|---|
| 29 quadrantes / 7 versões / 203 slots | inexistentes; há 12 painéis JSON, 12 slots visuais no HTML e 3 versões para cada um de 8 objetos |
| 32 controles | presente no dado demo e renderizado dinamicamente apenas na home |
| quatro Widgets | presentes: Comparação, Estados, Tempo e Tarefas |
| Angular isolado | Angular/package/build inexistentes |
| API PHP e MySQL | inexistentes |
| shared hosting | site estático é compatível, mas não há configuração de deploy/API |
| Google Login/WebSockets/alta escala | inexistentes; `tnaCredentialStatus` é apenas estado local |
