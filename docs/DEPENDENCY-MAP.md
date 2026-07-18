# Mapa de dependências

Escala: **baixo** = contrato local e fallback simples; **médio** = mais de um consumidor/rota; **alto** = estado, seletores ou fluxo transversal; **crítico** = alteração pode indisponibilizar a experiência ou bloquear a integração futura.

| Componente | Arquivo de origem | Dependências | Consumidores | Risco de alteração |
|---|---|---|---|---|
| Carregamento da home | `index.html` | três CSS; `script.js`; dois ES modules; servidor com MIME correto | `/`, `/index.html` | **Crítico**: concentra as três superfícies e ordem de scripts. |
| Entrada Experience | `experience.html` | CSS institucional/Tempo; `script.js`; `experience.js` | `/experience.html` | **Alto**: HTML duplicado pode divergir silenciosamente. |
| Compatibilidade `/tempo/` | `tempo/index.html` | `meta refresh`; caminho relativo `../index.html` | links/URLs históricos | **Médio**: canonical divergente e dependência de estrutura. |
| Dashboard shell | `index.html` | IDs/`data-*`; `dashboard-hub.css`; `dashboard-hub.js` | Dashboard renderer; acessibilidade; integração futura | **Crítico**: grids nascem vazios sem JS e seletores formam contrato. |
| 32 controles | `data/dashboard-hub-demo.js` | IDs estáveis; `createShortcut`; feature state | grid, pesquisa, localStorage, eventos, futura narrativa | **Crítico**: renomear ID quebra estado salvo e regras futuras. |
| Quatro ativadores | `index.html` | IDs alvo; `widgetActivationItems` | controlador exclusivo de Widgets | **Alto**: `aria-controls`/`data-widget-target` devem coincidir. |
| Quatro modelos de Widget | `data/dashboard-hub-demo.js` | renderers por `type`; parâmetros/ações/metadata | cards gerados, persistência, eventos | **Alto**: schema é implícito e sem validação formal. |
| Estado Dashboard | `scripts/dashboard-hub.js` | dados demo, DOM e repositório simulado | controles, Widgets, ARIA, eventos | **Crítico**: singleton mutável sem API pública/versionamento. |
| Persistência Dashboard | `scripts/dashboard-hub.js` | `localStorage`; chaves v1/v2; IDs | inicialização e todas as mutações | **Alto**: escrita sem fallback/quota handling; dois schemas. |
| Eventos Dashboard | `scripts/dashboard-hub.js` | `CustomEvent`, `window`, IDs | hoje nenhum; futuramente Angular/API adapter | **Alto**: contrato não versionado e sem consumidor/teste. |
| `#visual-system` | `index.html`, `experience.html` | `styles.css`; carrossel; seletor/destino flutuante | visitante, `script.js`, `tempo*.js`, futuro host Angular | **Crítico**: é conteúdo e boundary de integração. |
| Grid visual atual | ambos HTML principais | 12 imagens/figures; classes institucionais | CSS global e carrossel | **Alto**: regressão visual nas duas rotas. |
| Carrossel visual | `script.js` | três atributos `data-*`, layout CSS, `getComputedStyle` | ambas as páginas principais | **Médio**: cópia legada pode divergir. |
| Elemento flutuante | ambos HTML + `tempo*.js` | `imagem1.png`; camada CSS; destino/anchor; scroll/animation APIs | ambas as páginas, acessibilidade, eventos | **Crítico**: substituir a section/anchor quebra ida e retorno. |
| Imagens | `tempo/images/` | caminhos absolutos, manifest, HTML, bandwidth/cache | visual-system, Tempo, flutuante | **Alto**: ~8,6 MB e nomes legados; 404 na publicação em subdiretório. |
| Manifest/fallback de assets | `scripts/modules/asset-manifest.js` | `/tempo/images/`; chaves `imageKey` dos JSONs | `tempo*.js` | **Alto**: chave/caminho errado degrada múltiplos componentes. |
| Bootstrap Tempo | `scripts/tempo.js`, `scripts/experience.js` | sete módulos; quatro JSONs; DOM opcional; browser APIs | home e Experience, respectivamente | **Crítico**: cópias exatas exigem mudança dupla. |
| Fallback de carregamento | `scripts/tempo.js`, `scripts/experience.js` | validação de `fetch`; DOM; retry idempotente | visitante quando JSON/DOM falha | **Alto**: Angular deve manter fallback estático independente. |
| Dados temporais | quatro `data/*.json` | schemas implícitos; IDs cruzados; MIME JSON | bootstrap/motor/render | **Alto**: sem JSON Schema ou validação semântica. |
| Estado/sessões Tempo | `scripts/modules/state-store.js` | motor temporal; `localStorage`; schema 0.2.0 | `tempo*.js`, admin, snapshot | **Crítico**: futura migração servidor deve preservar dados locais. |
| Motor temporal | `scripts/modules/temporal-engine.js` | IDs/pesos de eras, partes e objetos | state-store e public-state | **Alto**: motor anterior não modela 29×7. |
| Snapshot público | `scripts/modules/public-state.js` | motor e sessões | bootstrap Tempo | **Médio**: agrega somente estado local do navegador. |
| Admin opcional | `scripts/modules/admin-panel.js` | dialog DOM opcional, storage e dataset | bootstrap Tempo | **Médio**: restaura apenas duas chaves e não é autenticação. |
| Reveal/scroll | `script.js`, `scroll-scenes.js` | `IntersectionObserver`, classes `.reveal`, `[data-panel]` | layout institucional/Tempo | **Médio**: Angular pode duplicar observação/animação. |
| CSS institucional | `styles.css` | classes/DOM globais | home e Experience | **Alto**: seletores globais podem vazar para host Angular. |
| CSS Tempo | `styles/tempo.css` + imports | tokens globais preexistentes; DOM Tempo | home e Experience | **Alto**: estilos globais e `@import`; tokens file inativo. |
| CSS Dashboard | `styles/dashboard-hub.css` | classes e atributos de estado | somente home | **Alto**: minificação e regras sobrepostas dificultam mudança segura. |
| Cópias legadas | `tempo/script/main.js`, `tempo/style/main.css` | nenhuma referência atual | possíveis URLs/histórico externo | **Médio**: parecem órfãs, mas remoção não foi autorizada. |
| Futuro host Angular | ainda inexistente | filho dedicado de `#visual-system`; eventos versionados; assets estáticos | motor narrativo futuro | **Crítico**: montar na section apagaria fallback/anchor. |
| Futura API PHP/MySQL | ainda inexistente | PDO, config privada, CORS/same-origin, migrations | adapter de persistência e Angular | **Crítico**: contrato e recursos do shared hosting ainda desconhecidos. |

## Grafo resumido

```text
index.html
 ├─ styles.css ─── visual-system + institucional
 ├─ styles/tempo.css ─┬─ tempo.components.css
 │                     └─ tempo.motion.css
 ├─ styles/dashboard-hub.css
 ├─ script.js ─── reveal + visual carousel
 ├─ dashboard-hub.js ── dashboard-hub-demo.js ── localStorage + CustomEvent
 └─ tempo.js ─┬─ four JSON files
             ├─ asset-manifest.js ── tempo/images/*
             ├─ state-store.js ── temporal-engine.js ── localStorage
             ├─ public-state.js ── temporal-engine.js
             ├─ carousel.js
             ├─ scroll-scenes.js
             └─ admin-panel.js

experience.html ── mesma cadeia, exceto Dashboard, usando experience.js
tempo/index.html ── redirect relativo para index.html
```
