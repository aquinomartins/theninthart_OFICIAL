# Grid visual da história em 29 quadrantes

## Ponto de montagem e arquivos

O grid é montado apenas na página principal, dentro de `section#visual-system.visual-system.section-shell.reveal`, que preserva `data-floating-destination="visual-system"` e a âncora `[data-floating-guide-destination-anchor]`. A implementação adiciona um filho isolado `[data-story-grid-host]` antes do conteúdo visual antigo.

Arquivos envolvidos:

- `index.html`: host, estado de carregamento, região acessível e carregamento de `styles/story-grid.css` e `scripts/story-grid.js`.
- `styles/story-grid.css`: CSS escopado em `[data-story-grid-host]`.
- `scripts/story-grid.js`: carregamento dos manifestos, validação runtime, renderização e API local.
- `docs/STORY-GRID.md`: este documento.

`experience.html` também possui `#visual-system`, mas a primeira implementação permanece somente na home porque ela contém simultaneamente `#dashboard-hub` e `#visual-system`.

## Estrutura dos 29 quadrantes

Cada quadrante é renderizado como `<figure class="story-panel">` com `role="listitem"`, `<figcaption>`, dados de quadrante, versão, slot, bloco narrativo, classe de aspecto e sarjetas. O grid pai usa `role="list"`. A ordem do DOM é Q01 a Q29, derivada de `data/quadrants.json`.

A primeira renderização usa a versão padrão `v01`, portanto os slots ativos são `q01-v01` até `q29-v01`. Os outros 174 slots permanecem apenas no manifesto e não são inseridos no DOM.

## Mapa desktop

Acima de 1100 px o grid usa 12 colunas com áreas declarativas por `data-quadrant-id`:

- linha 1: Q01–Q06, cada um com duas colunas;
- linha 2: Q07 em largura total e altura reduzida;
- linha 3: Q08–Q10, cada um com quatro colunas;
- linha 4: Q11 e Q12, cada um com seis colunas;
- linha 5: Q13 em panorama de largura total;
- linhas 6 e 7: Q14 ocupa colunas 1–5 em duas linhas; Q15 e Q16 ocupam colunas 6–12;
- linha 8: Q17 e Q18, cada um com seis colunas;
- linhas 9 e 10: Q19 e Q20 em largura total;
- linha 11: Q21–Q23, cada um com quatro colunas;
- linha 12: Q24–Q29, cada um com duas colunas.

Q11 e Q12 recebem uma diagonal decorativa com pseudo-elemento e melhoria progressiva via `clip-path`, sem cortar legenda ou conteúdo essencial.

## Mapa tablet

Entre 768 e 1099 px o grid usa seis colunas. Q01–Q06 e Q21–Q29 usam blocos de duas colunas; Q07, Q13, Q19 e Q20 ocupam largura total; Q11/Q12 e Q17/Q18 usam três colunas; Q14 ocupa três colunas e duas linhas, com Q15/Q16 ao lado quando há espaço.

## Mapa mobile

Abaixo de 768 px o grid mantém seis trilhos estreitos para preservar ritmo sem scroll horizontal. Q01–Q06 e Q24–Q29 usam três colunas visuais por linha quando legível; os demais ficam em largura total. A ordem narrativa permanece Q01–Q29.

## Classes de aspecto

As classes vêm de `data/quadrants.json` e são aplicadas em `data-aspect-class`:

- `narrow-vertical`: `2 / 3`;
- `title-strip`: altura mínima reduzida;
- `medium`: `4 / 3`;
- `wide`: `16 / 9`;
- `panorama`: `21 / 9`;
- `tall`: `3 / 4`;
- `full-width`: `16 / 7`;
- `consequence-narrow`: `2 / 3`.

## Placeholders

Quando `slot.asset.path` é `null`, `renderPanelMedia(slot)` cria um placeholder HTML/CSS com QNN, VNN e o código `qNN-vNN`. Nenhum `<img>` é criado, nenhum `expectedPath` é requisitado e não há arquivos de placeholder.

## Carregamento de manifestos

O script carrega via `fetch`:

- `/data/story-manifest-index.json`;
- `/data/story-versions.json`;
- `/data/quadrants.json`;
- `/data/quadrant-slots.json`.

A validação runtime confere cardinalidades do índice, IDs `q01` a `q29`, versões `v01` a `v07`, unicidade, referências, sete slots por quadrante e slot padrão para cada quadrante.

## Fallback

A estratégia adotada é a A: o conteúdo visual anterior permanece em `[data-story-static-fallback]` e só é ocultado por CSS quando o host recebe `data-story-grid-ready="true"`. Se o grid falhar, o fallback antigo continua visível e o erro fica restrito ao host.

## Eventos

- `tna:story-grid-ready`: emitido após renderizar 29 painéis.
- `tna:story-grid-error`: emitido em falha localizada.
- `tna:story-panel-changed`: emitido quando uma versão de quadrante é trocada pela API local.

## API `window.TNAStoryGrid`

Métodos disponíveis:

- `getState()`;
- `getActiveSelections()`;
- `setQuadrantVersion(quadrantId, versionId)`;
- `setAllQuadrantsVersion(versionId)`;
- `applySelections(selections)`;
- `resetToDefault()`;
- `reload()`.

Exemplo:

```js
TNAStoryGrid.setQuadrantVersion('q10', 'v03');
TNAStoryGrid.setAllQuadrantsVersion('v07');
TNAStoryGrid.resetToDefault();
```

## Inserir imagem futura

Quando houver arte final, o slot poderá receber um asset real. Exemplo:

```json
{
  "slotId": "q13-v02",
  "asset": {
    "path": "/assets/story/quadrants/q13/v02/q13-v02.png",
    "expectedPath": "/assets/story/quadrants/q13/v02/q13-v02.png",
    "mimeType": "image/png"
  },
  "status": "published"
}
```

`renderPanelMedia(slot)` aceitará caminhos internos relativos/absolutos em `/assets/` ou HTTPS futuro, rejeitando `javascript:`, `data:`, caminhos vazios e `..`.

## Integração futura com Angular

Angular deve continuar isolado dentro de `#visual-system`, preferencialmente como outro filho controlado ou como consumidor da API/eventos deste host. Não deve controlar cabeçalho, hero, Dashboard, manifesto, seções institucionais ou footer.

## Limitações atuais

- Não há mecanismo narrativo automático.
- Não há integração com os 32 controles nem com os quatro Widgets.
- Não há upload, API PHP, MySQL, autenticação ou persistência remota.
- Todos os assets reais permanecem ausentes; os 29 painéis mostram placeholders ativos.
