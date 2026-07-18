# Release Test Report — The Ninth Art

## 1. Resumo executivo
Preparação de deploy para SuperDomínios/cPanel executada com pacote local em `release/`. A release foi gerada e verificada quanto a arquivos proibidos, checksums, PHP lint e contratos narrativos. A decisão é **REPROVADA** para implantação final porque o build/teste Angular não pôde ser executado por bloqueio HTTP 403 no registry npm, não há MySQL local ativo para migrations/seeds/API reais, e testes visuais/navegadores não foram executados neste ambiente.

## 2. Data e hora
2026-07-18 02:53 UTC.

## 3. Commit Git
21677bac66470d461b736d7c1ff591cba19ab483 antes deste conjunto de alterações; o pacote registra o commit disponível no momento da geração.

## 4. Ambiente
Codex local, Linux, Apache remoto não acessado, sem deploy real.

## 5. Versão PHP
PHP 8.4.22-dev CLI com `pdo_mysql` carregado.

## 6. Versão Node usada somente no build
Node v20.20.2 e npm 11.4.2; `npm ci` falhou por HTTP 403 no registry.

## 7. Versão Angular
Declarada no workspace: Angular 20.1.x. Build real não executado por falha de instalação.

## 8. Versão MySQL/MariaDB
Não executado: serviço MySQL local recusou conexão.

## 9. Extensões PHP
Extensões relevantes encontradas: `PDO`, `pdo_mysql`, `mysqli`, `mbstring`, `json`, `curl`, `openssl`, `intl`, `dom`, `xml`, `zip`, `gd`.

## 10. Arquivos criados
- `scripts-build/package-shared-hosting.php`
- `docs/DEPLOY-SUPERDOMINIOS.md`
- `docs/ROLLBACK.md`
- `docs/DEPLOY-CHECKLIST.md`
- `docs/POST-DEPLOY-TESTS.md`
- `docs/TROUBLESHOOTING.md`
- `docs/RELEASE-TEST-REPORT.md`
- `release/` completo, ignorado pelo Git.

## 11. Arquivos alterados
- `.htaccess`
- `api/.htaccess`
- `api/index.php`
- `server/config.example.php`
- `.gitignore`

## 12. Arquivos preservados
Não foram alterados os manifestos narrativos `data/story-controls.json`, `data/story-widgets.json`, `data/story-versions.json`, `data/quadrants.json` e `data/quadrant-slots.json`, nem o mecanismo narrativo ou o visual institucional.

## 13. Build Angular
`npm ci` falhou com HTTP 403 ao baixar `@angular/animations`; portanto `npm run test`, `npm run build` e `npm run build:host` não foram concluídos. O pacote contém o manifesto Angular já existente, que está vazio e deve ser regenerado antes de aprovação.

## 14. Build PHP
`php -l` passou para todos os arquivos PHP versionados.

## 15. Migrations
`dry-run` listou sete migrations e checksums em modo offline. `verify` falhou por ausência de MySQL local (`Connection refused`).

## 16. Seeds
`check` e `verify` dependentes de banco falharam por ausência de MySQL local.

## 17. API
Testes unitários PHP passaram e o smoke HTTP foi ignorado sem `TNA_API_BASE_URL`. API real com MySQL não foi aprovada.

## 18. Integração
Dashboard–Angular–API não pôde ser testado integralmente por falta de build Angular e banco/API HTTP real.

## 19. Testes visuais
Não executados neste ambiente. Necessário navegador automatizado ou validação manual nas resoluções solicitadas.

## 20. Viewports
Não executados; devem ser testados em produção/staging.

## 21. Navegadores
Não executados; Chrome, Firefox, Edge e Safari permanecem pendentes.

## 22. Segurança
`package-shared-hosting.php verify` aprovou ausência de arquivos proibidos em `public_html` e checksums. Teste PHP também não encontrou segredos óbvios de alta entropia. Testes HTTP de acesso negado a diretórios dependem de Apache/cPanel.

## 23. Performance
Release local: aproximadamente 9,4 MiB, 133 arquivos no checksum. Bundles Angular não foram medidos porque o build não foi regenerado.

## 24. Pacote
`release/` gerado com `public_html/`, `private/`, `database/`, `documentation/`, `release-manifest.json`, `checksums.sha256` e `DEPLOY-CHECKLIST.md`.

## 25. Checksums
`php scripts-build/package-shared-hosting.php verify` validou `release/checksums.sha256`.

## 26. Documentação
Documentação de deploy, rollback, pós-deploy, troubleshooting, checklist e relatório criada e copiada para a release após reconstrução.

## 27. Limitações
Sem deploy remoto, sem credenciais, sem MySQL local ativo, sem Apache/cPanel local, sem browsers executados e sem acesso npm autorizado para dependências Angular.

## 28. Testes não executados
Build/testes Angular, smoke HTTP completo da API, migrations/seeds com banco real, testes visuais, viewports e navegadores.

## 29. Falhas
- `npm ci`: HTTP 403 no registry npm.
- `php scripts-build/migrate.php verify`: conexão MySQL recusada.
- `php scripts-build/seed-database.php verify`: conexão MySQL recusada.

## 30. Decisão final
REPROVADA

## Cardinalidades
| Entidade | Esperado | Encontrado | Resultado |
|---|---:|---:|---|
| Controles | 32 | 32 | OK |
| Widgets | 4 | 4 | OK |
| Versões | 7 | 7 | OK |
| Quadrantes | 29 | 29 | OK |
| Slots | 203 | 203 | OK |
| Painéis por story run | 29 | não executado | Pendente |

## Matriz de testes
| Camada | Teste | Resultado |
|---|---|---|
| HTML/CSS | Preservação visual | Não executado |
| Dashboard | 32 controles | OK por contrato/manifests |
| Widgets | Quatro painéis exclusivos | OK por contrato/manifests |
| Angular | Build de produção | Falhou antes do build: `npm ci` HTTP 403 |
| Angular | Testes | Não executado |
| Angular | Fallback | Não executado em navegador |
| PHP | Sintaxe | OK |
| PHP | Testes | OK sem integração HTTP |
| MySQL | Migrations | Dry-run offline OK; verify falhou sem MySQL |
| MySQL | Seeds | Falhou sem MySQL |
| API | Smoke test | Não executado sem `TNA_API_BASE_URL` |
| Integração | Dashboard–Angular–API | Não executado |
| Release | Checksums | OK |
| Release | Arquivos proibidos | OK |
| Segurança | Segredos | OK por varredura estática básica |
| Segurança | Configuração privada | OK no pacote; real pendente no cPanel |

## Navegadores
| Navegador | Versão | Resultado |
|---|---|---|
| Chrome | não executado | Não executado |
| Firefox | não executado | Não executado |
| Edge | não executado | Não executado |
| Safari | não executado | Não executado |

## Viewports
| Viewport | Resultado |
|---|---|
| 1920 × 1080 | Não executado |
| 1440 × 900 | Não executado |
| 1024 × 768 | Não executado |
| 768 × 1024 | Não executado |
| 430 × 932 | Não executado |
| 390 × 844 | Não executado |
| 360 × 800 | Não executado |
