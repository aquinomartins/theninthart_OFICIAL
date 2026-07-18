# Fundação do backend PHP

Esta fundação cria uma API PHP mínima, sem framework, sem ORM e sem alterações no front-end existente. O escopo implementado é apenas infraestrutura: bootstrap, configuração segura, autoload, roteador simples, envelope JSON, tratamento centralizado de erros, request ID, logger estruturado, middlewares básicos e um endpoint temporário de diagnóstico sem banco.

## Estrutura criada

```text
api/
  index.php
  .htaccess
server/
  bootstrap.php
  config.example.php
  composer.json
  src/
    Config/
    Database/
    Http/
    Middleware/
    Security/
    Support/
```

`api/index.php` é o ponto público. `server/` deve permanecer fora do document root quando a hospedagem permitir. Em hospedagem onde o repositório inteiro fique sob `public_html`, bloqueie acesso direto a `server/` nas regras do servidor.

## Configuração

Apenas `server/config.example.php` é versionado. Ele contém placeholders não sensíveis para ambiente, debug, URL-base, timezone UTC, tamanho máximo de corpo, conexão MySQL, origens permitidas e segredo da aplicação.

A configuração real deve ser carregada nesta ordem operacional:

1. variável de ambiente `TNA_CONFIG_PATH`, apontando para um arquivo PHP privado que retorna array;
2. variável de ambiente `TNA_PRIVATE_CONFIG_PATH`, quando o provedor exigir outro nome;
3. arquivo privado padrão `../tna-config.php`, fora de `server/` e preferencialmente fora de `public_html`;
4. `server/config.example.php` somente como fallback de desenvolvimento/documentação.

A API não expõe configuração por rota. Credenciais reais, tokens e segredos não devem ser versionados.

## Autoload

`server/composer.json` declara PSR-4:

```json
{
  "Tna\\": "src/"
}
```

Em desenvolvimento, execute `composer install` dentro de `server/` para gerar `server/vendor/autoload.php`. Se Composer não estiver disponível na hospedagem compartilhada, `server/bootstrap.php` registra um fallback PSR-4 mínimo para classes `Tna\` em `server/src/`.

## PDO e banco de dados

`Tna\Database\ConnectionFactory` cria a conexão PDO sob demanda. O endpoint `/api/v1/ping` não solicita conexão e retorna `databaseChecked: false`.

Opções obrigatórias configuradas:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`;
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`;
- `PDO::ATTR_EMULATE_PREPARES => false`;
- DSN MySQL com `charset=utf8mb4`.

Não há migrations, seeds, tabelas ou consultas SQL nesta tarefa.

## HTTP e envelope JSON

Todas as respostas JSON seguem o envelope:

```json
{
  "data": {},
  "meta": {
    "requestId": "...",
    "schemaVersion": "1.0.0",
    "mechanismVersion": "1.0.0",
    "timestamp": "..."
  },
  "error": null
}
```

O roteador implementa 404 para rota inexistente e 405 para método incorreto. Middlewares implementam limite de corpo 413 e validação de `Content-Type: application/json` para métodos com corpo, retornando 415 quando inválido.

## Segurança e erros

Headers enviados:

- `X-Content-Type-Options: nosniff`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`.

CORS com `*` não é configurado. O logger escreve JSON estruturado no `error_log` do PHP e redige chaves sensíveis como senha, token, segredo, autorização e cookie. Stack trace só aparece quando `debug` estiver ativo fora de produção.

## Endpoint temporário

Rota criada:

```http
GET /api/v1/ping
```

Resposta `data`:

```json
{
  "status": "ok",
  "application": "the-ninth-art-api",
  "databaseChecked": false
}
```

Fallback para hospedagens sem rewrite:

```http
GET /api/index.php?route=/v1/ping
```

Ainda não existe `/health`.

## Sessões anônimas

Foram adicionados apenas os endpoints de estado anônimo abaixo:

```http
POST /api/v1/sessions
GET /api/v1/sessions/{sessionId}
PUT /api/v1/sessions/{sessionId}/controls
PUT /api/v1/sessions/{sessionId}/widgets/{widgetId}
```

As rotas privadas exigem o header `X-TNA-Session-Token`. A criação gera um `public_id` público e um token bruto com `random_bytes`; somente o hash SHA-256 do token é armazenado. O token bruto é retornado apenas na resposta de criação e não deve ser registrado em logs.

Cada sessão inicia, dentro de uma transação, com os 32 controles canônicos desligados e os quatro Widgets com parâmetros padrão derivados do catálogo. As escritas exigem `revision` e aplicam controle otimista na linha da sessão. Conflitos retornam HTTP 409 com `code: REVISION_CONFLICT`, a revisão atual e o estado seguro, sem token.

As atualizações de controles são parciais e aceitam somente IDs canônicos com valores booleanos. As atualizações de Widgets são parciais, preservam parâmetros omitidos e validam tipo, opções, mínimo, máximo e step pelo catálogo. As transações registram eventos `session.created`, `session.controls.updated` e `session.widget.updated` na outbox, sem processamento nesta etapa.
