# API backend PHP/MySQL

Base recomendada em produção: `/api/v1` quando `api/.htaccess` estiver ativo. Sem `mod_rewrite`, use `/api/index.php/v1` ou configure o servidor para encaminhar o path ao `api/index.php`.

Todas as respostas JSON usam envelope:

```json
{
  "data": {},
  "meta": {
    "requestId": "req_...",
    "schemaVersion": "1.0.0",
    "mechanismVersion": "1.0.0",
    "timestamp": "2026-07-18T00:00:00+00:00"
  },
  "error": null
}
```

## Endpoints validados

| Método | Endpoint | Autenticação | Finalidade | Resultado esperado |
|---|---|---|---|---|
| GET | `/v1/health` | pública | Verifica banco, migrations e contagens canônicas. | 200 quando íntegro; 503 quando indisponível. |
| GET | `/v1/bootstrap` | pública | Entrega catálogo de controles, widgets, versões, quadrantes e variantes. | 200 + ETag. |
| POST | `/v1/sessions` | pública | Cria sessão anônima. | 201 + `sessionId`, `revision` e token bruto uma única vez. |
| GET | `/v1/sessions/{id}` | `X-TNA-Session-Token` | Lê sessão. | 200 ou 401/404. |
| PUT | `/v1/sessions/{id}/controls` | `X-TNA-Session-Token` | Atualiza controles com revisão otimista. | 200; 409 em conflito. |
| PUT | `/v1/sessions/{id}/widgets/timeline` | `X-TNA-Session-Token` | Atualiza Widget Timeline com revisão otimista. | 200; 409 em conflito. |
| POST | `/v1/story-runs` | `X-TNA-Session-Token` + `Idempotency-Key` | Resolve uma execução de história. | 201 + 29 painéis. Repetição da chave retorna o mesmo run. |
| GET | `/v1/story-runs/{id}` | `X-TNA-Session-Token` | Lê execução de história. | 200 + 29 painéis. |
| GET | `/v1/public-state` | pública | Lê snapshot público inicial/mais recente. | 200 + ETag. |
| POST | `/v1/events/batch` | `X-TNA-Session-Token` | Registra eventos permitidos em lote. | 200 + contagens `accepted`, `duplicated`, `rejected`. |

## Contratos quantitativos

| Entidade | Esperado |
|---|---:|
| Controles | 32 |
| Widgets | 4 |
| Versões | 7 |
| Quadrantes | 29 |
| Variantes | 203 |
| Assets | 203 |
| Painéis por story run | 29 |

## Exemplos mínimos

```bash
curl -sS https://example.com/api/v1/health
curl -sS -X POST https://example.com/api/v1/sessions -H 'Content-Type: application/json' -d '{}'
curl -sS https://example.com/api/v1/sessions/ses_xxx -H 'X-TNA-Session-Token: <token>'
```

Nunca publique tokens em logs, tickets ou documentação.
