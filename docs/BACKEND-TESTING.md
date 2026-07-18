# Testes do backend

## Ambiente limitado sem MySQL

Execute sempre:

```bash
php -l api/index.php
find server scripts-build tests/php -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/php/run.php
php scripts-build/migrate.php dry-run
```

O runner `tests/php/run.php` valida contagens dos manifests, limites dos slots, presença de `.htaccess`, hashing de token por análise estática e ausência de segredos comuns. Sem `TNA_API_BASE_URL`, a integração HTTP é marcada como ignorada e não deve ser declarada aprovada.

## Smoke test HTTP

Após publicar em staging com banco migrado e seed aplicado:

```bash
php scripts-build/api-smoke-test.php https://example.com/api/v1
```

O script cobre:

- `GET /health`;
- `GET /bootstrap`;
- `POST /sessions`;
- `GET /sessions/{id}`;
- `PUT /sessions/{id}/controls`;
- `PUT /sessions/{id}/widgets/timeline`;
- `POST /story-runs`;
- `GET /story-runs/{id}`;
- `GET /public-state`;
- `POST /events/batch`.

Ele mascara o token no output e valida HTTP, JSON, request ID, revisão, conflito, idempotência, 29 painéis e contagens do catálogo.

## Com MySQL disponível

```bash
php scripts-build/migrate.php up
php scripts-build/seed-database.php apply
php scripts-build/migrate.php verify
php scripts-build/verify-database.php
TNA_API_BASE_URL=https://example.com/api/v1 php tests/php/run.php
```

A integração só está aprovada quando o smoke HTTP passa contra um ambiente com MySQL real.
