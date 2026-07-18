# Angular Story Engine

A ilha Angular `tna-story-engine` é montada dentro de `#visual-system`, antes do fallback estático. O componente raiz carrega os manifestos locais, valida as cardinalidades 32/4/7/29/203, indexa slots por chave `quadrantId:versionId` e renderiza apenas os 29 slots ativos da versão inicial `v01`.

## Arquitetura

- `StoryEngineComponent`: componente standalone raiz, sem Router, SSR, PWA ou controle de seções externas.
- `ManifestLoaderService`: busca e valida `story-manifest-index.json`, controles, Widgets, versões, quadrantes e slots.
- `StoryStateService`: estado com Angular Signals, mapas indexados e cópias serializáveis.
- `StoryEngineEventsService`: eventos públicos seguros.
- `AssetPathValidatorService`: aceita apenas caminhos internos e extensões permitidas.
- `LocalManifestStoryEngineApi`: adapter local para JSON; métodos remotos permanecem abstratos e não chamam `/api/`.
- `DefaultStoryInputAdapter` e `NoopRealtimeTransport`: contratos futuros sem subscrição ativa, polling, EventSource ou WebSocket.

## Eventos e API

Eventos: `tna:story-engine-loading`, `tna:story-engine-ready`, `tna:story-panel-changed`, `tna:story-engine-reset` e `tna:story-engine-error`.

API pública: `window.TNAStoryEngine.getState()`, `getActiveSelections()`, `setQuadrantVersion()`, `setAllQuadrantsVersion()`, `applySelections()`, `resetToDefault()`, `reloadManifests()` e `isReady()`.

## Fallback, acessibilidade e segurança

O fallback legado permanece visível até `tna:story-engine-ready`; erros restauram o fallback. A renderização usa `<figure>`/`<figcaption>`, ordem Q01–Q29 e uma região `aria-live`. Não há `innerHTML`, `eval`, `document.write`, `bypassSecurityTrustHtml`, APIs PHP, MySQL ou integração automática com Dashboard/Widgets.
