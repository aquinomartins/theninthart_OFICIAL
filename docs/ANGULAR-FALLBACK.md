# Fallback Angular

O fallback estático (`TNAStoryGrid`) continua proprietário de `[data-story-grid-host]`. A ilha Angular (`TNAStoryEngine`) é carregada por `scripts/story-engine-loader.js` somente quando existem `#visual-system` e `tna-story-engine`.

## Quando aparece

- antes do carregamento Angular;
- sem bundles compilados;
- com erro/timeout no manifesto de bundles;
- com falha de bootstrap ou manifesto narrativo;
- com erro local de imagem, sem derrubar o restante.

## Quando é ocultado

Apenas após `tna:story-engine-ready`, 29 painéis renderizados, Q01 e Q29 presentes e slots ativos válidos. O shell recebe `data-story-engine-ready="true"` e o wrapper `[data-story-grid-fallback]` fica `hidden`.

## Depuração

Para simular falha, renomeie temporariamente `assets/story-engine/story-engine-assets.json` ou esvazie a lista de scripts. `window.TNAStoryGrid` e `window.TNAStoryEngine` não chamam uma à outra; a migração futura deve ser explícita e versionada.
