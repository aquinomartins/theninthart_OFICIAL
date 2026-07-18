# Banco de dados operacional

O banco MySQL recebe um catálogo operacional sincronizado a partir dos manifestos canônicos. A fonte semântica continua sendo `data/story-controls.json`, `data/story-widgets.json`, `data/story-versions.json`, `data/quadrants.json` e `data/quadrant-slots.json`.

## Entidades esperadas

| Entidade | Esperado |
|---|---:|
| Controles | 32 |
| Widgets | 4 |
| Versões | 7 |
| Quadrantes | 29 |
| Variantes | 203 |
| Assets | 203 |

## Verificação de integridade

`php scripts-build/verify-database.php` confere:

- tabelas e migrations aplicadas;
- validade JSON dos manifestos;
- contagens esperadas;
- referências entre quadrantes, versões, variantes e assets;
- primeiro slot `q01-v01` e último slot `q29-v07`;
- sete slots por quadrante e 29 slots por versão;
- ausência de órfãos e duplicados.

## Limitações desta fase

Esta fase não cria endpoints, controllers ou integração com front-end. A sincronização é executada por scripts CLI e depende de MySQL configurado conforme `docs/MIGRATIONS.md`.
