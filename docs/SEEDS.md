# Seeds do catálogo narrativo

Os seeds leem diretamente os manifestos canônicos em `data/` e não duplicam registros em PHP.

## Comandos

```sh
php scripts-build/seed-database.php check
php scripts-build/seed-database.php apply
php scripts-build/seed-database.php verify
php scripts-build/verify-database.php
```

- `check`: valida manifestos e exibe contagens atuais, sem escrita no banco.
- `apply`: executa os seeds em transação, com prepared statements e operações idempotentes por `stable_key`/`slot_key`.
- `verify`: executa o verificador de tabelas, migrations, JSON, contagens, referências, ordem e duplicidades.

## Preservação editorial

Os seeds atualizam somente campos canônicos seguros. Campos editoriais existentes não são substituídos automaticamente por `NULL`: `asset_path`, `mime_type`, `width`, `height`, `aspect_ratio`, `caption` e `checksum`. Metadados e revisões editoriais existentes também não são reinicializados em atualizações.

O status nunca é regredido dentro da ordem editorial: `published`, `approved`, `review`, `draft`, `planned`, `empty`.

## Snapshot público inicial

`002_initial_public_snapshot.php` insere um snapshot público apenas quando `public_snapshots` está vazio. O payload inicial usa contagens zero e não inventa usuários, sessões, runs, eventos ou participação.
