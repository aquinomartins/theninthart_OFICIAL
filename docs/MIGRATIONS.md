# Migrations PHP/MySQL

Este repositório usa um executor de migrations sem framework e sem ORM para manter compatibilidade com hospedagem compartilhada PHP/MySQL.

## Arquivos

As migrations ficam em `database/migrations/` e são executadas em ordem lexicográfica pelo nome do arquivo.

Migrations iniciais:

- `001_schema_migrations.sql`: cria a tabela de controle `schema_migrations`.
- `002_story_catalog.sql`: cria o catálogo narrativo estrutural.

## Tabela de controle

`schema_migrations` registra:

- `version`: versão derivada do nome do arquivo sem `.sql`;
- `filename`: nome do arquivo aplicado;
- `checksum`: SHA-256 do conteúdo aplicado;
- `applied_at`: data/hora UTC da aplicação;
- `execution_time_ms`: tempo de execução.

O runner recusa executar quando uma migration já aplicada teve checksum alterado.

## Comandos

```sh
php scripts-build/migrate.php status
php scripts-build/migrate.php dry-run
php scripts-build/migrate.php up
php scripts-build/migrate.php verify
```

- `status`: lista migrations aplicadas e pendentes usando o banco configurado.
- `dry-run`: lista arquivos/checksums e mostra o plano de aplicação quando MySQL estiver disponível. Sem MySQL, permanece em modo offline e não marca nada como aplicado.
- `up`: aplica migrations pendentes, para na primeira falha e não registra migration malsucedida.
- `verify`: confere migrations aplicadas, checksums, tabelas do catálogo, índices únicos, foreign keys, charset `utf8mb4` e engine `InnoDB`.

Rollback automático não existe nesta fase.

## Concorrência

Durante `up`, o runner usa `GET_LOCK('tna_schema_migrations', 10)` e libera com `RELEASE_LOCK` ao concluir ou falhar. Isso evita execução concorrente em múltiplos processos PHP apontando para o mesmo banco.

## Configuração

O script lê a mesma configuração privada da fundação PHP:

1. `TNA_CONFIG_PATH`;
2. `TNA_PRIVATE_CONFIG_PATH`;
3. `../tna-config.php` na raiz do repositório;
4. `server/config.example.php` como fallback de desenvolvimento.

Não versione credenciais reais.

## Ambiente sem MySQL

Quando o MySQL não estiver disponível, execute pelo menos:

```sh
php -l server/src/Database/MigrationRunner.php
php -l scripts-build/migrate.php
php scripts-build/migrate.php dry-run
```

Para aplicação real em ambiente com MySQL configurado:

```sh
php scripts-build/migrate.php status
php scripts-build/migrate.php up
php scripts-build/migrate.php verify
```
