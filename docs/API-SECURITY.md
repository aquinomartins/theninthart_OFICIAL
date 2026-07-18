# Segurança da API

## Validações consolidadas

| Controle | Como verificar | Estado esperado |
|---|---|---|
| Prepared statements | Revisar `server/src/Service`, `server/src/Repository`, migrations/seeds e executar `tests/php/run.php`. | Entradas externas passam por `prepare()`/`execute()`; consultas sem parâmetros são estáticas. |
| Ausência de SQL concatenado com input | Verificar usos de `query()`/`exec()` e concatenação. | Aceitável apenas para SQL estático ou sufixos constantes como `FOR UPDATE`. |
| Token apenas como hash | Criar sessão e inspecionar banco. | `sessions.anonymous_token_hash` contém SHA-256; token bruto só aparece no `POST /sessions`. |
| Erros sem stack trace | Testar erro em produção (`debug=false`). | Sem `trace`; mensagem genérica para 500. |
| Ausência de segredos | Rodar scanner estático do runner e revisar `server/config.example.php`. | Apenas placeholders versionados. Config real fica fora do web root. |
| CORS restrito | Verificar configuração do host e headers. | Não usar `Access-Control-Allow-Origin: *`; liberar somente origens esperadas. |
| Limites de corpo | Enviar `Content-Length` maior que `max_body_bytes`. | HTTP 413. |
| Rate limit | Verificar migration `007_rate_limits.sql` e política operacional. | Tabela existe; middleware aplicativo deve ser ligado antes de exposição pública ampla. |
| IDs públicos | Revisar respostas. | APIs retornam `ses_`, `run_`, `evt_`; IDs internos não são expostos. |
| Logs seguros | Forçar erro com token no contexto controlado. | Logger redige chaves sensíveis. |
| `.htaccess` | Revisar `api/.htaccess`. | `Options -Indexes` e fallback para `index.php`. |

## Recomendações de implantação segura

- Mantenha HTTPS obrigatório no painel do host.
- Coloque `tna-config.php` fora do web root quando o provedor permitir.
- Configure `debug=false` em produção.
- Não habilite CORS amplo. Preferir same-origin; se necessário, permitir apenas domínio canônico.
- Restrinja permissões: arquivos `0644`, diretórios `0755`, configuração privada com menor permissão aceita pelo host.
- Rotacione o `app_secret` quando houver suspeita de exposição.
- Monitore `error_log` para 401/413/429/500 sem registrar tokens brutos.
