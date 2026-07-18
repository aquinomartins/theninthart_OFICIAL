# Contratos narrativos

## Finalidade e relações

Esta camada declarativa é a fonte canônica, versionada e independente de interface da Nova Nona Arte. Controles fornecem sinais binários; Widgets agrupam parâmetros; versões descrevem famílias narrativas; quadrantes fixam funções; slots relacionam cada quadrante a cada versão. Nenhuma dessas entidades determina DOM, CSS, armazenamento ou banco.

IDs são estáveis: controles e Widgets usam `kebab-case`, versões `vNN`, quadrantes `qNN`, sarjetas `gNN` e slots `qNN-vNN`. Posição é explícita e nunca substitui o ID. `schemaVersion` governa a forma; `manifestVersion`, o conteúdo. Mudança incompatível exige versão principal; adição compatível incrementa versão secundária.

## Evolução

Para ampliar controles, Widgets, versões ou quadrantes: defina primeiro o ID, atualize o schema e referências, regenere slots quando a cardinalidade mudar, valide e documente a compatibilidade. Parâmetros de Widget têm tipo, limites, padrão e opções próprios. Versões permanecem famílias flexíveis, não roteiros fechados.

IDs, números, `stableKey`, relações quadrante-versão e `expectedPath` são campos estruturais imutáveis em uma versão principal. Em slots, `status`, `asset.path`, `asset.mimeType`, `caption`, `revision`, `checksum`, `dimensions` e `metadata` são editoriais e preservados pelo gerador padrão.

Angular futuramente consumirá os dados somente dentro do host de `#visual-system`; PHP validará entradas/saídas; MySQL poderá persistir entidades sem se tornar sua fonte semântica. Adapters devem respeitar IDs e versões, rejeitar propriedades desconhecidas nos estados e nunca inferir campos essenciais da ordem. O front-end legado e seus IDs permanecem contratos separados até migração explícita.

Compatibilidade requer JSON UTF-8 válido, referências resolvidas, posições contínuas e passagem do validador. Extensões livres pertencem apenas a `metadata`; alterações incompatíveis demandam migração e rollback documentados.
